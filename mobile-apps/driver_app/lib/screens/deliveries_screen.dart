import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_blue_plus/flutter_blue_plus.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import '../services/api.dart';
import '../services/ble_service.dart';
import '../services/foreground_service.dart';
import '../services/retry_queue.dart';
import '../theme/app_theme.dart';
import 'package:permission_handler/permission_handler.dart';
import 'sensor_history_screen.dart';
import 'profile_screen.dart';
import 'delivery_history_screen.dart';
import 'driver_map_screen.dart';

class DeliveriesScreen extends StatefulWidget {
  final int driverId;
  final String driverName;

  const DeliveriesScreen({
    super.key,
    required this.driverId,
    required this.driverName,
  });

  @override
  State<DeliveriesScreen> createState() => _DeliveriesScreenState();
}

class _DeliveriesScreenState extends State<DeliveriesScreen> {
  List<dynamic> deliveries = [];
  int? currentDeliveryId;

  final BleService ble = BleService();
  String bleStatus = 'Not connected';
  double? currentTemp;
  double? currentHum;
  bool tempAlert = false;

  Timer? _refreshTimer;
  Timer? _bannerTimer;
  List<dynamic> _previousDeliveries = [];
  String _bannerMessage = '';
  Color _bannerColor = AppColors.warning;

  DateTime _lastFgUpdate = DateTime(0);
  bool _isFetchingDeliveries = false;

  // ── GPS State ──
  int? _gpsOrderId;

  // ── Status-update state (fix #1) ──
  int? _updatingDeliveryId;
  final Map<int, String> _optimisticStatus = {};

  // ── Retry queues (fix #2) ──
  late final RetryQueue _gpsQueue;
  late final RetryQueue _sensorQueue;
  StreamSubscription<List<ConnectivityResult>>? _connectivitySub;
  StreamSubscription? _bleSub;

  @override
  void initState() {
    super.initState();
    ForegroundService.init();
    _initQueues();
    _requestPermissions();
    loadDeliveries();
    initBle();
    _refreshTimer = Timer.periodic(const Duration(seconds: 10), (_) {
      if (mounted && !_isFetchingDeliveries) loadDeliveries();
      _gpsQueue.flush();
      _sensorQueue.flush();
    });

    _connectivitySub = Connectivity().onConnectivityChanged.listen((results) {
      final online = results.any((r) => r != ConnectivityResult.none);
      if (online) {
        debugPrint('Connectivity restored — flushing retry queues');
        _gpsQueue.flush();
        _sensorQueue.flush();
      }
    });
  }

  void _initQueues() {
    _gpsQueue = RetryQueue(
      name: 'gps',
      send: (item) async {
        return await ApiService.saveDriverLocation(
          item['order_id'] as int,
          (item['lat'] as num).toDouble(),
          (item['lng'] as num).toDouble(),
        );
      },
    );

    _sensorQueue = RetryQueue(
      name: 'sensor',
      send: (item) async {
        return await ApiService.saveSensorData(
          item['delivery_id'] as int,
          (item['temperature'] as num).toDouble(),
          (item['humidity'] as num).toDouble(),
          item['is_alert'] == true,
        );
      },
    );

    unawaited(_gpsQueue.init());
    unawaited(_sensorQueue.init());
  }

  Future<void> _requestPermissions() async {
    final statuses = await [
      Permission.bluetoothScan,
      Permission.bluetoothConnect,
      Permission.locationWhenInUse,
      Permission.notification,
    ].request();

    if (statuses[Permission.bluetoothScan] != PermissionStatus.granted) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Bluetooth permission is required to connect to the sensor.'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _bannerTimer?.cancel();
    _stopGpsUpdates();
    _connectivitySub?.cancel();
    _bleSub?.cancel();
    _gpsQueue.dispose();
    _sensorQueue.dispose();
    ble.disconnect();
    super.dispose();
  }

  // ─────────────────────────────────────────────────────────
  // GPS LOCATION TRACKING (delegated to foreground service)
  // ─────────────────────────────────────────────────────────
  void _startGpsUpdates(int orderId) {
    if (_gpsOrderId == orderId) return;
    _gpsOrderId = orderId;
    debugPrint('GPS: delegating to foreground service for order $orderId');

    ForegroundService.start().then((_) {
      ForegroundService.setActiveOrder(orderId);
    });
  }

  void _stopGpsUpdates() {
    if (_gpsOrderId != null) {
      debugPrint('GPS: stopping foreground GPS for order $_gpsOrderId');
    }
    _gpsOrderId = null;
    ForegroundService.setActiveOrder(0);
    // NOTE: do NOT call ForegroundService.stop() here — updateStatus()
    // and _rejectOrder() call it only after a successful delivery/rejection,
    // so the service doesn't die mid-transit if the UI rebuilds.
  }

  void _syncGpsWithDeliveries(List<dynamic> activeData) {
    Map<String, dynamic>? active;
    for (var item in activeData) {
      if (item is Map) {
        final status = (item['delivery_status'] ?? '').toString();
        if (status == 'picked_up' || status == 'in_transit') {
          active = Map<String, dynamic>.from(item);
          break;
        }
      }
    }

    if (active == null) {
      _stopGpsUpdates();
      return;
    }

    final orderId = _parseInt(active['order_id']);
    if (orderId != null) {
      _startGpsUpdates(orderId);
    }
  }

  // ─────────────────────────────────────────────────────────
  // BLE
  // ─────────────────────────────────────────────────────────
  void initBle() {
    try {
      _bleSub = ble.readingStream.listen((data) {
        if (data['type'] == 'disconnected') {
          if (mounted) setState(() => bleStatus = 'Not connected');
          return;
        }
        if (data['type'] == 'connected') {
          if (mounted) setState(() => bleStatus = 'Connected');
          return;
        }

        if (data['type'] == 'live') {
          if (!mounted) return;
          setState(() {
            currentTemp = data['temperature'];
            currentHum = data['humidity'];
            tempAlert = data['is_alert'] ?? false;
          });

          if (currentTemp != null && currentHum != null) {
            if (DateTime.now().difference(_lastFgUpdate) > const Duration(seconds: 5)) {
              _lastFgUpdate = DateTime.now();
              ForegroundService.updateTemp(
                currentTemp!,
                currentHum!,
                alert: tempAlert,
              );
            }
          }

          if (currentDeliveryId != null && currentTemp != null && currentHum != null) {
            _sensorQueue.enqueue({
              'delivery_id': currentDeliveryId!,
              'temperature': currentTemp!,
              'humidity': currentHum!,
              'is_alert': tempAlert,
              'dedup_key': 'sensor:$currentDeliveryId:${DateTime.now().millisecondsSinceEpoch ~/ 5000}',
            });
          }
        }
      });
    } catch (e) {
      debugPrint('BLE Error: $e');
    }
  }

  // ─────────────────────────────────────────────────────────
  // BLUETOOTH SCANNER MODAL
  // ─────────────────────────────────────────────────────────
  void _showBluetoothScanner() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Container(
              height: MediaQuery.of(context).size.height * 0.6,
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Container(
                    width: 40, height: 4,
                    decoration: BoxDecoration(color: Colors.grey[300], borderRadius: BorderRadius.circular(2)),
                  ),
                  const SizedBox(height: 16),
                  const Text('Select ESP32 Sensor', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 16),
                  Expanded(
                    child: StreamBuilder<List<ScanResult>>(
                      stream: ble.scanForDevices(),
                      builder: (context, snapshot) {
                        if (!snapshot.hasData) {
                          return const Center(child: CircularProgressIndicator());
                        }
                        final results = snapshot.data!;
                        if (results.isEmpty) {
                          return const Center(child: Text('Scanning for nearby devices...'));
                        }
                        return ListView.builder(
                          itemCount: results.length,
                          itemBuilder: (context, i) {
                            final device = results[i].device;
                            final name = results[i].advertisementData.advName;
                            if (name.isEmpty) return const SizedBox.shrink();
                            return ListTile(
                              leading: const Icon(Icons.bluetooth, color: AppColors.burgundy),
                              title: Text(name),
                              subtitle: Text(device.remoteId.toString()),
                              onTap: () async {
                                ble.stopScan();
                                Navigator.pop(context);
                                // Use the screen messenger after the modal is dismissed.
                                final messenger = ScaffoldMessenger.of(this.context);
                                messenger.showSnackBar(
                                  SnackBar(content: Text('Connecting to $name...')),
                                );

                                final success = await ble.connectToDevice(device);
                                if (!mounted) return;

                                if (success) {
                                  setState(() => bleStatus = 'Connected');
                                } else {
                                  messenger.showSnackBar(
                                    const SnackBar(
                                      content: Text('Failed to connect'),
                                      backgroundColor: Colors.red,
                                    ),
                                  );
                                }
                              },
                            );
                          },
                        );
                      },
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  int? _parseInt(dynamic val) {
    if (val == null) return null;
    if (val is int) return val;
    if (val is String) return int.tryParse(val);
    return null;
  }

  int _parseIntOrZero(dynamic val) => _parseInt(val) ?? 0;

  // ─────────────────────────────────────────────────────────
  // LOAD DELIVERIES
  // ─────────────────────────────────────────────────────────
  Future<void> loadDeliveries() async {
    if (_isFetchingDeliveries) return;
    setState(() => _isFetchingDeliveries = true);

    try {
      final List<dynamic> data = await ApiService.getDeliveries(widget.driverId);
      if (!mounted) return;

      final activeData = data.where((item) {
        if (item is Map) {
          return item['delivery_status'] != 'delivered' && item['delivery_status'] != 'rejected';
        }
        return true;
      }).toList();

      Map<String, dynamic>? findActive(List<dynamic> list) {
        for (var item in list) {
          if (item is Map) {
            final status = (item['delivery_status'] ?? '').toString();
            if (status != 'delivered' && status != 'rejected') {
              return Map<String, dynamic>.from(item);
            }
          }
        }
        return null;
      }

      if (_previousDeliveries.isEmpty) {
        _previousDeliveries = List.from(activeData);
        final active = findActive(activeData);
        setState(() {
          deliveries = activeData;
          currentDeliveryId = active != null ? _parseInt(active['delivery_id']) : null;
        });
        _syncGpsWithDeliveries(activeData);
        return;
      }

      final oldIds = _previousDeliveries.whereType<Map>().map((d) => d['order_id']?.toString()).where((id) => id != null).toSet();
      final newIds = activeData.whereType<Map>().map((d) => d['order_id']?.toString()).where((id) => id != null).toSet();
      final removed = oldIds.difference(newIds);
      final added = newIds.difference(oldIds);

      if (removed.isNotEmpty) {
        _showBanner('Order #${removed.first} was reassigned or delivered', AppColors.warning);
      } else if (added.isNotEmpty) {
        _showBanner('New order #${added.first} assigned', AppColors.success);
      }

      _previousDeliveries = List.from(activeData);
      final active = findActive(activeData);
      setState(() {
        deliveries = activeData;
        currentDeliveryId = active != null ? _parseInt(active['delivery_id']) : null;
      });
      _syncGpsWithDeliveries(activeData);
    } catch (e) {
      debugPrint('Error loading deliveries: $e');
    } finally {
      if (mounted) setState(() => _isFetchingDeliveries = false);
    }
  }

  void _showBanner(String message, Color color) {
    setState(() {
      _bannerMessage = message;
      _bannerColor = color;
    });
    _bannerTimer?.cancel();
    _bannerTimer = Timer(const Duration(seconds: 5), () {
      if (mounted) setState(() => _bannerMessage = '');
    });
  }

  // ─────────────────────────────────────────────────────────
  // UPDATE STATUS (optimistic + in-flight guard)
  // ─────────────────────────────────────────────────────────
  Future<void> updateStatus(dynamic rawDeliveryId, String status, {int? orderId}) async {
    final deliveryId = _parseIntOrZero(rawDeliveryId);
    if (deliveryId == 0) return;

    if (_updatingDeliveryId == deliveryId) return;

    final messenger = ScaffoldMessenger.of(context);

    setState(() {
      _updatingDeliveryId = deliveryId;
      _optimisticStatus[deliveryId] = status;
    });

    final result = await ApiService.updateDeliveryStatus(deliveryId, status);
    if (!mounted) return;

    if (result['success'] == true) {
      if (orderId != null) {
        if (status == 'picked_up' || status == 'in_transit') {
          _startGpsUpdates(orderId);
        } else if (status == 'delivered' || status == 'rejected') {
          _stopGpsUpdates();
          ForegroundService.stop();
        }
      }
      messenger.showSnackBar(
        SnackBar(content: Text('Updated to ${_label(status)}')),
      );
      await loadDeliveries();
    } else {
      messenger.showSnackBar(
        SnackBar(
          content: Text(result['message']?.toString() ?? 'Update failed'),
          backgroundColor: AppColors.danger,
        ),
      );
    }

    if (mounted) {
      setState(() {
        _updatingDeliveryId = null;
        _optimisticStatus.remove(deliveryId);
      });
    }
  }

  Future<void> _rejectOrder(int deliveryId) async {
    if (deliveryId == 0) return;
    if (_updatingDeliveryId == deliveryId) return;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Reject Order?'),
        content: const Text('Are you sure you cannot deliver this order? It will be returned to the admin for reassignment.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Reject', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );

    if (confirm != true) return;
    if (!mounted) return;

    final messenger = ScaffoldMessenger.of(context);

    setState(() {
      _updatingDeliveryId = deliveryId;
      _optimisticStatus[deliveryId] = 'rejected';
    });

    final res = await ApiService.updateDeliveryStatus(deliveryId, 'rejected');
    if (!mounted) return;

    final success = res['success'] == true;

    setState(() {
      _updatingDeliveryId = null;
      if (!success) _optimisticStatus.remove(deliveryId);
    });

    if (success) {
      _stopGpsUpdates();
      ForegroundService.stop();
      messenger.showSnackBar(
        const SnackBar(content: Text('Order rejected. Returning to admin.'), backgroundColor: Colors.red),
      );
      await loadDeliveries();
    } else {
      messenger.showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Failed to reject')),
      );
    }
  }

  Color statusColor(String status) {
    switch (status) {
      case 'assigned':
        return AppColors.warning;
      case 'picked_up':
        return const Color(0xFF1565C0);
      case 'in_transit':
        return AppColors.burgundy;
      case 'delivered':
        return AppColors.success;
      case 'rejected':
        return AppColors.danger;
      default:
        return AppColors.textSecondary;
    }
  }

  String _label(String s) => s.replaceAll('_', ' ').toUpperCase();

  String _effectiveStatus(Map<String, dynamic> card) {
    final id = _parseInt(card['delivery_id']);
    if (id != null && _optimisticStatus.containsKey(id)) {
      return _optimisticStatus[id]!;
    }
    return (card['delivery_status'] ?? 'assigned').toString();
  }

  // ─────────────────────────────────────────────────────────
  // BUILD
  // ─────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Deliveries'),
            Text(
              widget.driverName,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w400,
                color: Colors.white70,
              ),
            ),
          ],
        ),
        actions: [
          if (_isFetchingDeliveries)
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 16.0),
              child: Center(
                child: SizedBox(
                  width: 18, height: 18,
                  child: CircularProgressIndicator(
                    strokeWidth: 2.5,
                    valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                  ),
                ),
              ),
            ),
          IconButton(
            icon: const Icon(Icons.local_shipping_outlined),
            tooltip: 'Delivery History',
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => DeliveryHistoryScreen(driverId: widget.driverId)),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.sensors_rounded),
            tooltip: 'Sensor history',
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => SensorHistoryScreen(driverId: widget.driverId)),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.person_outline_rounded),
            tooltip: 'Profile',
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => ProfileScreen(
                  driverId: widget.driverId,
                  driverName: widget.driverName,
                ),
              ),
            ),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: RefreshIndicator(
        color: AppColors.burgundy,
        onRefresh: loadDeliveries,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            children: [
              if (_bannerMessage.isNotEmpty)
                Container(
                  margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  decoration: BoxDecoration(
                    color: _bannerColor,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        _bannerColor == AppColors.warning
                            ? Icons.warning_amber_rounded
                            : Icons.check_circle_outline_rounded,
                        color: Colors.white,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          _bannerMessage,
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ],
                  ),
                ),
              if (_gpsOrderId != null)
                Container(
                  margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: AppColors.burgundy.withOpacity(0.08),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: AppColors.burgundy.withOpacity(0.25)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.gps_fixed_rounded, size: 16, color: AppColors.burgundy),
                      const SizedBox(width: 8),
                      Text(
                        'Sharing live GPS for order #$_gpsOrderId',
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: AppColors.burgundy,
                        ),
                      ),
                    ],
                  ),
                ),
              _buildMonitorCard(),
              deliveries.isEmpty
                  ? Container(
                constraints: BoxConstraints(minHeight: MediaQuery.of(context).size.height * 0.5),
                child: _buildEmpty(),
              )
                  : ListView.builder(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: deliveries.length,
                itemBuilder: (context, i) => _buildDeliveryCard(deliveries[i]),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMonitorCard() {
    final alert = tempAlert;
    final isConnected = bleStatus == 'Connected';

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: alert ? AppColors.danger.withOpacity(0.06) : AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: alert ? AppColors.danger.withOpacity(0.4) : AppColors.border,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.sensors_rounded, size: 18, color: AppColors.burgundy),
              const SizedBox(width: 8),
              const Text(
                'IoT Monitor',
                style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: AppColors.textPrimary),
              ),
              const Spacer(),
              _statusPill(bleStatus, alert),
            ],
          ),
          const SizedBox(height: 14),
          if (!isConnected)
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                icon: const Icon(Icons.bluetooth_searching, size: 18),
                label: const Text('Connect ESP32 Sensor'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.burgundy,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                onPressed: _showBluetoothScanner,
              ),
            ),
          if (isConnected) ...[
            Row(
              children: [
                Expanded(
                  child: _statBlock(
                    label: 'Temperature',
                    value: currentTemp?.toStringAsFixed(1) ?? '--',
                    unit: '°C',
                    alert: alert,
                  ),
                ),
                Container(width: 1, height: 44, color: AppColors.border),
                Expanded(
                  child: _statBlock(
                    label: 'Humidity',
                    value: currentHum?.toStringAsFixed(1) ?? '--',
                    unit: '%',
                    alert: alert,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            TextButton.icon(
              icon: const Icon(Icons.bluetooth_disabled, color: Colors.red, size: 16),
              label: const Text('Disconnect Sensor', style: TextStyle(color: Colors.red)),
              onPressed: () async {
                await ble.disconnect();
                if (!mounted) return;
                setState(() => bleStatus = 'Not connected');
              },
            ),
          ],
          if (alert) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(color: AppColors.danger, borderRadius: BorderRadius.circular(8)),
              child: const Row(
                children: [
                  Icon(Icons.warning_rounded, color: Colors.white, size: 16),
                  SizedBox(width: 6),
                  Text(
                    'Temperature out of range',
                    style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 12),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _statBlock({
    required String label,
    required String value,
    required String unit,
    required bool alert,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(fontSize: 11, color: AppColors.textSecondary, letterSpacing: 0.4),
          ),
          const SizedBox(height: 4),
          Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Text(
                value,
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.w700,
                  color: alert ? AppColors.danger : AppColors.textPrimary,
                ),
              ),
              const SizedBox(width: 4),
              Text(unit, style: const TextStyle(fontSize: 13, color: AppColors.textSecondary)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _statusPill(String status, bool alert) {
    final connected = status.toLowerCase().contains('connected');
    final color = alert ? AppColors.danger : connected ? AppColors.success : AppColors.textSecondary;
    final label = alert ? 'ALERT' : connected ? 'LIVE' : 'OFFLINE';
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(width: 6, height: 6, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.6,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDeliveryCard(dynamic d) {
    if (d is! Map) return const SizedBox.shrink();
    final cardMap = Map<String, dynamic>.from(d);
    final items = cardMap['items'] as List? ?? [];
    final status = _effectiveStatus(cardMap);
    final color = statusColor(status);
    final orderId = cardMap['order_id']?.toString() ?? '-';
    final orderIdInt = _parseInt(cardMap['order_id']);
    final deliveryId = cardMap['delivery_id'];
    final isUpdating = _updatingDeliveryId == _parseInt(deliveryId);

    String? nextStatus;
    String? nextLabel;
    if (status == 'assigned') {
      nextStatus = 'picked_up';
      nextLabel = 'Mark picked up';
    } else if (status == 'picked_up') {
      nextStatus = 'in_transit';
      nextLabel = 'Start transit';
    } else if (status == 'in_transit') {
      nextStatus = 'delivered';
      nextLabel = 'Confirm delivered';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Order #$orderId',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ),
                Material(
                  color: Colors.transparent,
                  child: InkWell(
                    borderRadius: BorderRadius.circular(20),
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => DriverMapScreen(
                            orderId: orderIdInt ?? 0,
                            orderStatus: status,
                            retailerName: cardMap['retailer_name']?.toString(),
                            retailerAddress: cardMap['retailer_address']?.toString(),
                          ),
                        ),
                      );
                    },
                    child: Container(
                      margin: const EdgeInsets.only(right: 8),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: AppColors.burgundy.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.map_outlined, size: 13, color: AppColors.burgundy),
                          SizedBox(width: 4),
                          Text(
                            'Map',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: AppColors.burgundy,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    _label(status),
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.4,
                      color: color,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            _infoRow(Icons.storefront_rounded, (cardMap['retailer_name'] ?? '-').toString()),
            const SizedBox(height: 8),
            _infoRow(Icons.location_on_outlined, (cardMap['retailer_address'] ?? '-').toString()),
            const SizedBox(height: 8),
            _infoRow(Icons.phone_outlined, (cardMap['retailer_phone'] ?? '-').toString()),

            if (items.isNotEmpty) ...[
              const SizedBox(height: 14),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.canvas,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Order Items',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: AppColors.textSecondary,
                        letterSpacing: 0.4,
                      ),
                    ),
                    const SizedBox(height: 8),
                    ...items.map((it) {
                      if (it is! Map) return const SizedBox.shrink();
                      final productName = it['product_name']?.toString() ?? it['name']?.toString() ?? 'Unknown Product';
                      final qty = it['quantity']?.toString() ?? '1';
                      return Padding(
                        padding: const EdgeInsets.symmetric(vertical: 4),
                        child: Row(
                          children: [
                            Container(
                              width: 6,
                              height: 6,
                              decoration: const BoxDecoration(
                                color: AppColors.burgundy,
                                shape: BoxShape.circle,
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                productName,
                                style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w500,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                            ),
                            Text(
                              'x$qty',
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      );
                    }),
                  ],
                ),
              ),
            ],

            if (nextStatus != null) ...[
              const SizedBox(height: 14),
              Row(
                children: [
                  if (status == 'assigned') ...[
                    Expanded(
                      child: OutlinedButton(
                        onPressed: isUpdating ? null : () => _rejectOrder(_parseIntOrZero(deliveryId)),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.red,
                          side: const BorderSide(color: Colors.red),
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                        child: const Text('Reject', style: TextStyle(fontWeight: FontWeight.bold)),
                      ),
                    ),
                    const SizedBox(width: 10),
                  ],
                  Expanded(
                    flex: 2,
                    child: ElevatedButton(
                      onPressed: isUpdating
                          ? null
                          : () => updateStatus(
                        deliveryId,
                        nextStatus!,
                        orderId: orderIdInt,
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: isUpdating
                            ? AppColors.textSecondary
                            : AppColors.burgundy,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: isUpdating
                          ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2.5,
                          valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                        ),
                      )
                          : Text(
                        nextLabel!,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _infoRow(IconData icon, String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 16, color: AppColors.textSecondary),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: const TextStyle(
              fontSize: 13,
              color: AppColors.textPrimary,
              height: 1.4,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: AppColors.burgundy.withOpacity(0.08),
              shape: BoxShape.circle,
            ),
            alignment: Alignment.center,
            child: const Icon(Icons.inbox_rounded, size: 40, color: AppColors.burgundy),
          ),
          const SizedBox(height: 16),
          const Text(
            'No deliveries assigned',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Pull down to refresh',
            style: TextStyle(
              fontSize: 13,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}
