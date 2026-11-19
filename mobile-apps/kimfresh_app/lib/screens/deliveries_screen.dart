import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_blue_plus/flutter_blue_plus.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import '../services/api.dart';
import '../services/ble_service.dart';
import 'package:geolocator/geolocator.dart';
import '../services/foreground_service.dart';
import '../services/retry_queue.dart';
import '../theme/app_theme.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:flutter_ringtone_player/flutter_ringtone_player.dart';
import 'package:image_picker/image_picker.dart';
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

class SlideToActionButton extends StatefulWidget {
  final String label;
  final Color color;
  final VoidCallback onConfirmed;

  const SlideToActionButton({
    super.key,
    required this.label,
    required this.color,
    required this.onConfirmed,
  });

  @override
  State<SlideToActionButton> createState() => _SlideToActionButtonState();
}

class _SlideToActionButtonState extends State<SlideToActionButton> {
  double _dragValue = 0.0;
  final double _thumbSize = 48.0;
  final double _padding = 4.0;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final maxWidth = constraints.maxWidth;
        final dragWidth = maxWidth - _thumbSize - (_padding * 2);

        return Container(
          height: _thumbSize + (_padding * 2),
          decoration: BoxDecoration(
            color: widget.color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(_thumbSize),
            border: Border.all(color: widget.color.withOpacity(0.3)),
          ),
          child: Stack(
            children: [
              Center(
                child: AnimatedOpacity(
                  opacity: _dragValue > 0.1 ? 0.0 : 1.0,
                  duration: const Duration(milliseconds: 200),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.keyboard_double_arrow_right_rounded, color: widget.color, size: 20),
                      const SizedBox(width: 8),
                      Text(
                        widget.label,
                        style: TextStyle(
                          color: widget.color,
                          fontWeight: FontWeight.w700,
                          fontSize: 15,
                          letterSpacing: 0.5,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Positioned(
                left: _padding + (_dragValue * dragWidth),
                top: _padding,
                child: GestureDetector(
                  onHorizontalDragUpdate: (details) {
                    setState(() {
                      _dragValue += details.primaryDelta! / dragWidth;
                      _dragValue = _dragValue.clamp(0.0, 1.0);
                    });
                  },
                  onHorizontalDragEnd: (details) {
                    if (_dragValue > 0.85) {
                      _dragValue = 1.0;
                      widget.onConfirmed();
                      Future.delayed(const Duration(milliseconds: 300), () {
                        if (mounted) setState(() => _dragValue = 0.0);
                      });
                    } else {
                      setState(() => _dragValue = 0.0);
                    }
                  },
                  child: Container(
                    width: _thumbSize,
                    height: _thumbSize,
                    decoration: BoxDecoration(
                      color: widget.color,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: widget.color.withOpacity(0.4),
                          blurRadius: 8,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.power_settings_new,
                      color: Colors.white,
                      size: 24,
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _DeliveriesScreenState extends State<DeliveriesScreen> {
  List<dynamic> deliveries = [];
  int? currentDeliveryId;
  Timer? _directGpsTimer;

  bool _isOnline = false;
  bool _isUpdatingStatus = false;

  final BleService ble = BleService.instance;

  String bleStatus = 'Not connected';
  double? currentTemp;
  double? currentHum;
  bool tempAlert = false;

  String _lastAlertLevel = 'safe';
  DateTime _lastBeepTime = DateTime(0);

  Timer? _refreshTimer;
  Timer? _bannerTimer;
  List<dynamic> _previousDeliveries = [];
  String _bannerMessage = '';
  Color _bannerColor = AppColors.warning;

  DateTime _lastFgUpdate = DateTime(0);
  bool _isFetchingDeliveries = false;

  int? _gpsOrderId;
  int? _justDeliveredOrderId;
  int? _updatingDeliveryId;
  final Map<int, String> _optimisticStatus = {};

  late final RetryQueue _gpsQueue;
  late final RetryQueue _sensorQueue;
  StreamSubscription<List<ConnectivityResult>>? _connectivitySub;
  StreamSubscription? _bleSub;

  @override
  void initState() {
    super.initState();
    _initializeApp();
  }

  Future<void> _initializeApp() async {
    await ApiService.updateDriverStatus(widget.driverId, 'offline');

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

    _directGpsTimer?.cancel();
    _directGpsTimer = Timer.periodic(const Duration(seconds: 10), (timer) async {
      if (_gpsOrderId == null) return;

      try {
        final pos = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.high,
        );
        debugPrint('📡 DIRECT GPS: ${pos.latitude}, ${pos.longitude} for order $_gpsOrderId');

        await ApiService.saveDriverLocation(
            _gpsOrderId!,
            widget.driverId,
            pos.latitude,
            pos.longitude
        );
      } catch (e) {
        debugPrint('📡 DIRECT GPS error: $e');
      }
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
          widget.driverId,
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

    super.dispose();
  }

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
      _directGpsTimer?.cancel();
      _stopGpsUpdates();
      return;
    }

    final orderId = _parseInt(active['order_id']);
    if (orderId != null) {
      _startGpsUpdates(orderId);

      if (_directGpsTimer == null || !_directGpsTimer!.isActive) {
        debugPrint('📡 Starting background GPS timer for order $orderId');
      }
    }
  }

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

          final alertLevel = data['alert_level'] ?? 'safe';

          setState(() {
            currentTemp = data['temperature'];
            currentHum = data['humidity'];
            tempAlert = alertLevel != 'safe';
          });

          if (alertLevel != 'safe' && alertLevel != _lastAlertLevel) {
            _lastAlertLevel = alertLevel;
            _playAlarm(alertLevel);
          } else if (alertLevel == 'safe') {
            _lastAlertLevel = 'safe';
          }

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

          if (currentTemp != null && currentHum != null) {
            final activeDeliveries = deliveries.where((d) {
              if (d is! Map) return false;
              final status = _effectiveStatus(Map<String, dynamic>.from(d));
              return status == 'picked_up' || status == 'in_transit';
            }).toList();

            for (var delivery in activeDeliveries) {
              final dId = _parseInt(delivery['delivery_id']);
              if (dId != null) {
                _sensorQueue.enqueue({
                  'delivery_id': dId,
                  'temperature': currentTemp!,
                  'humidity': currentHum!,
                  'is_alert': tempAlert,
                  'dedup_key': 'sensor:$dId:${DateTime.now().millisecondsSinceEpoch ~/ 5000}',
                });
              }
            }
          }
        }
      });
    } catch (e) {
      debugPrint('BLE Error: $e');
    }
  }

  Future<void> _playAlarm(String level) async {
    if (DateTime.now().difference(_lastBeepTime).inSeconds < 5) return;
    _lastBeepTime = DateTime.now();

    if (level == 'warning') {
      for (int i = 0; i < 3; i++) {
        FlutterRingtonePlayer().playAlarm(looping: false, volume: 1.0, asAlarm: true);
        HapticFeedback.heavyImpact();
        await Future.delayed(const Duration(milliseconds: 1000));
      }
    } else if (level == 'critical') {
      for (int i = 0; i < 10; i++) {
        FlutterRingtonePlayer().playAlarm(looping: false, volume: 1.0, asAlarm: true);
        HapticFeedback.heavyImpact();
        await Future.delayed(const Duration(milliseconds: 1000));
      }
    }
  }

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
                          return const Center(child: Text('Starting scan...'));
                        }

                        final results = snapshot.data!;

                        if (results.isNotEmpty) {
                          debugPrint("📡 PHONE SEES ${results.length} DEVICES");
                          for (var r in results) {
                            debugPrint("   -> Name: '${r.advertisementData.advName}' | MAC: ${r.device.remoteId}");
                          }
                        }

                        if (results.isEmpty) {
                          return const Center(child: Text('Scanning for nearby devices...'));
                        }

                        return ListView.builder(
                          itemCount: results.length,
                          itemBuilder: (context, i) {
                            final device = results[i].device;
                            final rawName = results[i].advertisementData.advName;
                            final displayName = rawName.isNotEmpty ? rawName : 'Unknown Device (${device.remoteId})';

                            return ListTile(
                              leading: const Icon(Icons.bluetooth, color: AppColors.burgundy),
                              title: Text(displayName),
                              subtitle: Text(device.remoteId.toString()),
                              onTap: () async {
                                ble.stopScan();
                                Navigator.pop(context);
                                await Future.delayed(const Duration(milliseconds: 500));
                                final messenger = ScaffoldMessenger.of(this.context);
                                messenger.showSnackBar(
                                  SnackBar(content: Text('Connecting to $displayName...')),
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

      final active = findActive(activeData);

      if (active != null) {
        _gpsOrderId = _parseInt(active['order_id']);
        debugPrint('📍 GPS Order ID set to: $_gpsOrderId');
      }

      if (_previousDeliveries.isEmpty) {
        _previousDeliveries = List.from(activeData);
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
        if (_justDeliveredOrderId != null && removed.contains(_justDeliveredOrderId.toString())) {
          _showBanner('Order #$_justDeliveredOrderId delivered successfully!', AppColors.success);
          _justDeliveredOrderId = null;
        } else {
          _showBanner('Order #${removed.first} was reassigned', AppColors.warning);
        }
      } else if (added.isNotEmpty) {
        _showBanner('New order #${added.first} assigned', AppColors.success);
      }

      _previousDeliveries = List.from(activeData);
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

  Future<void> updateStatus(dynamic rawDeliveryId, String status, {int? orderId}) async {
    final deliveryId = _parseIntOrZero(rawDeliveryId);
    if (deliveryId == 0) return;

    if (_updatingDeliveryId == deliveryId) return;

    final messenger = ScaffoldMessenger.of(context);

    setState(() {
      _updatingDeliveryId = deliveryId;
      _optimisticStatus[deliveryId] = status;
    });

    final now = DateTime.now().toIso8601String();
    final result = await ApiService.updateDeliveryStatus(deliveryId, status, timestamp: now);
    if (!mounted) return;

    if (result['success'] == true) {
      if (status == 'picked_up' || status == 'in_transit') {
        if (orderId != null) {
          _startGpsUpdates(orderId);
        }

        _directGpsTimer?.cancel();
        _directGpsTimer = Timer.periodic(const Duration(seconds: 10), (timer) async {
          if (_gpsOrderId == null) return;

          try {
            final pos = await Geolocator.getCurrentPosition(
              desiredAccuracy: LocationAccuracy.high,
            );
            debugPrint('📡 DIRECT GPS: ${pos.latitude}, ${pos.longitude} for order $_gpsOrderId');

            await ApiService.saveDriverLocation(
                _gpsOrderId!,
                widget.driverId,
                pos.latitude,
                pos.longitude
            );
          } catch (e) {
            debugPrint('📡 DIRECT GPS error: $e');
          }
        });

      } else if (status == 'delivered' || status == 'rejected') {
        _directGpsTimer?.cancel();
        _stopGpsUpdates();
        ForegroundService.stop();

        if (status == 'delivered' && orderId != null) {
          _justDeliveredOrderId = orderId;
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

  Future<void> _markDelivered(int deliveryId, int? orderId) async {
    if (deliveryId == 0 || _updatingDeliveryId == deliveryId) return;

    final uploaded = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => ProofOfDeliverySheet(deliveryId: deliveryId),
    );

    if (uploaded != true || !mounted) return;

    _directGpsTimer?.cancel();
    _stopGpsUpdates();
    ForegroundService.stop();

    if (orderId != null) _justDeliveredOrderId = orderId;

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Proof of delivery saved — order completed'),
        backgroundColor: AppColors.success,
      ),
    );
    await loadDeliveries();
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        titleSpacing: 16,
        centerTitle: false,
        backgroundColor: AppColors.canvas,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Deliveries', style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: AppColors.textPrimary)),
            Text(widget.driverName, style: const TextStyle(fontSize: 13, color: AppColors.textSecondary, fontWeight: FontWeight.w500)),
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
                    valueColor: AlwaysStoppedAnimation<Color>(AppColors.brand),
                  ),
                ),
              ),
            ),
        ],
      ),
      body: RefreshIndicator(
        color: AppColors.burgundy,
        onRefresh: loadDeliveries,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.only(bottom: 100),
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

              Container(
                margin: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: _isOnline ? AppColors.success.withOpacity(0.1) : AppColors.surface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                    color: _isOnline ? AppColors.success : AppColors.border,
                    width: 2,
                  ),
                ),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 12,
                          height: 12,
                          decoration: BoxDecoration(
                            color: _isOnline ? AppColors.success : AppColors.textSecondary,
                            shape: BoxShape.circle,
                            boxShadow: [
                              if (_isOnline)
                                BoxShadow(
                                  color: AppColors.success.withOpacity(0.5),
                                  blurRadius: 8,
                                  spreadRadius: 2,
                                ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _isOnline ? 'You are Online' : 'You are Offline',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w700,
                                  color: _isOnline ? AppColors.success : AppColors.textPrimary,
                                ),
                              ),
                              Text(
                                _isOnline
                                    ? 'Ready to receive delivery orders'
                                    : 'Go online to receive orders',
                                style: const TextStyle(
                                  fontSize: 12,
                                  color: AppColors.textSecondary,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    if (_isUpdatingStatus)
                      const Center(child: CircularProgressIndicator())
                    else
                      SlideToActionButton(
                        label: _isOnline ? 'Slide to Go Offline' : 'Slide to Go Online',
                        color: _isOnline ? AppColors.danger : AppColors.success,
                        onConfirmed: _toggleOnlineStatus,
                      ),
                  ],
                ),
              ),

              deliveries.isEmpty
                  ? Container(
                constraints: BoxConstraints(minHeight: MediaQuery.of(context).size.height * 0.5),
                child: _buildEmpty(),
              )
                  : ListView.builder(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: deliveries.length,
                itemBuilder: (context, i) => _buildDeliveryCard(deliveries[i]),
              ),
            ],
          ),
        ),
      ),

      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(30),
              boxShadow: [
                BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 24, offset: const Offset(0, 8)),
              ],
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _buildDockIcon(
                  icon: Icons.local_shipping_outlined,
                  label: 'History',
                  onTap: () => Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => DeliveryHistoryScreen(driverId: widget.driverId)),
                  ),
                ),
                _buildDockIcon(
                  icon: Icons.sensors_rounded,
                  label: 'Sensors',
                  onTap: () => Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => SensorHistoryScreen(
                        driverId: widget.driverId,
                        deliveryId: currentDeliveryId,
                      ),
                    ),
                  ),
                ),
                _buildDockIcon(
                  icon: Icons.person_outline_rounded,
                  label: 'Profile',
                  onTap: () => Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => ProfileScreen(
                        driverId: widget.driverId,
                        driverName: widget.driverName,
                        role: 'driver',
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDockIcon({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: SizedBox(
        width: 80,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: const Color(0xFFB0B3B8), size: 24),
            const SizedBox(height: 4),
            Text(
              label,
              style: const TextStyle(color: Color(0xFFB0B3B8), fontSize: 11, fontWeight: FontWeight.w500),
            ),
          ],
        ),
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
                          : () {
                        if (nextStatus == 'delivered') {
                          _markDelivered(_parseIntOrZero(deliveryId), orderIdInt);
                        } else {
                          updateStatus(
                            deliveryId,
                            nextStatus!,
                            orderId: orderIdInt,
                          );
                        }
                      },
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

  Future<void> _toggleOnlineStatus() async {
    if (_isUpdatingStatus) return;

    final goingOnline = !_isOnline;
    final newStatus = goingOnline ? 'available' : 'offline';

    setState(() => _isUpdatingStatus = true);

    final res = await ApiService.updateDriverStatus(widget.driverId, newStatus);

    if (!mounted) return;

    if (res['success'] == true) {
      setState(() {
        _isOnline = goingOnline;
        _isUpdatingStatus = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_isOnline ? 'You are now ONLINE' : 'You are now OFFLINE'),
          backgroundColor: _isOnline ? AppColors.success : AppColors.textSecondary,
        ),
      );

      if (_isOnline) loadDeliveries();
    } else {
      setState(() => _isUpdatingStatus = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message']?.toString() ?? 'Failed to update status'),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }
}

/// Bottom sheet that forces the driver to attach a photo before the delivery
/// can be marked as delivered.
class ProofOfDeliverySheet extends StatefulWidget {
  final int deliveryId;

  const ProofOfDeliverySheet({super.key, required this.deliveryId});

  @override
  State<ProofOfDeliverySheet> createState() => _ProofOfDeliverySheetState();
}

class _ProofOfDeliverySheetState extends State<ProofOfDeliverySheet> {
  final ImagePicker _picker = ImagePicker();
  XFile? _photo;
  bool _uploading = false;
  String? _error;

  Future<void> _pick(ImageSource source) async {
    try {
      final picked = await _picker.pickImage(
        source: source,
        imageQuality: 70,
        maxWidth: 1600,
      );
      if (picked == null) return;
      if (!mounted) return;
      setState(() {
        _photo = picked;
        _error = null;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'Could not open ${source == ImageSource.camera ? 'camera' : 'gallery'}: $e');
    }
  }

  Future<void> _submit() async {
    if (_photo == null || _uploading) return;
    setState(() {
      _uploading = true;
      _error = null;
    });

    final res = await ApiService.uploadDeliveryProof(widget.deliveryId, _photo!.path);

    if (!mounted) return;

    if (res['success'] == true) {
      Navigator.pop(context, true);
      return;
    }

    setState(() {
      _uploading = false;
      _error = res['message']?.toString() ?? 'Upload failed. Please try again.';
    });
  }

  @override
  Widget build(BuildContext context) {
    final hasPhoto = _photo != null;

    return Container(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      decoration: const BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: SafeArea(
        top: false,
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppColors.border,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              const Text(
                'Proof of Delivery',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 4),
              const Text(
                'A photo of the handover is required before this order can be completed.',
                style: TextStyle(fontSize: 13, color: AppColors.textSecondary, height: 1.4),
              ),
              const SizedBox(height: 16),
              Container(
                height: 220,
                decoration: BoxDecoration(
                  color: AppColors.canvas,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppColors.border),
                ),
                clipBehavior: Clip.antiAlias,
                child: hasPhoto
                    ? Image.file(File(_photo!.path), fit: BoxFit.cover)
                    : Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: const [
                    Icon(Icons.add_a_photo_outlined, size: 44, color: AppColors.textSecondary),
                    SizedBox(height: 10),
                    Text(
                      'No photo yet',
                      style: TextStyle(fontSize: 14, color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _uploading ? null : () => _pick(ImageSource.camera),
                      icon: const Icon(Icons.photo_camera_outlined, size: 18),
                      label: Text(hasPhoto ? 'Retake' : 'Take photo'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.burgundy,
                        side: const BorderSide(color: AppColors.burgundy),
                        padding: const EdgeInsets.symmetric(vertical: 13),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _uploading ? null : () => _pick(ImageSource.gallery),
                      icon: const Icon(Icons.photo_library_outlined, size: 18),
                      label: const Text('Gallery'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.textPrimary,
                        side: const BorderSide(color: AppColors.border),
                        padding: const EdgeInsets.symmetric(vertical: 13),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                  ),
                ],
              ),
              if (_error != null) ...[
                const SizedBox(height: 12),
                Text(_error!, style: const TextStyle(fontSize: 13, color: AppColors.danger)),
              ],
              const SizedBox(height: 16),
              SizedBox(
                height: 50,
                child: ElevatedButton(
                  onPressed: (!hasPhoto || _uploading) ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.burgundy,
                    disabledBackgroundColor: AppColors.border,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  child: _uploading
                      ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.5,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  )
                      : const Text(
                    'Submit & complete delivery',
                    style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}