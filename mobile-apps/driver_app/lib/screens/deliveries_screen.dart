import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import '../services/api.dart';
import '../services/ble_service.dart';
import '../services/foreground_service.dart';
import '../theme/app_theme.dart';
import 'sensor_history_screen.dart';
import 'profile_screen.dart';
import 'delivery_history_screen.dart';
import 'driver_map_screen.dart';

class DeliveriesScreen extends StatefulWidget {
  final int driverId;
  final String driverName;
  DeliveriesScreen({required this.driverId, required this.driverName});

  @override
  _DeliveriesScreenState createState() => _DeliveriesScreenState();
}

class _DeliveriesScreenState extends State<DeliveriesScreen> {
  List<dynamic> deliveries = [];
  int? currentDeliveryId;
  int? _currentOrderId;

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
  Timer? _locationTimer;
  bool _isSendingGps = false;
  int? _gpsOrderId;

  @override
  void initState() {
    super.initState();
    loadDeliveries();
    initBle();
    _refreshTimer = Timer.periodic(const Duration(seconds: 10), (_) {
      if (mounted) loadDeliveries();
    });
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _bannerTimer?.cancel();
    _stopGpsUpdates();
    ble.disconnect();
    super.dispose();
  }

  // ─────────────────────────────────────────────────────────
  // GPS LOCATION TRACKING
  // ─────────────────────────────────────────────────────────
  void _startGpsUpdates(int orderId) {
    if (_gpsOrderId == orderId && _locationTimer != null) return;
    _stopGpsUpdates();
    _gpsOrderId = orderId;
    debugPrint('GPS: starting updates for order $orderId');

    _sendGpsPing(orderId);
    _locationTimer = Timer.periodic(const Duration(seconds: 10), (_) {
      _sendGpsPing(orderId);
    });
  }

  void _stopGpsUpdates() {
    if (_locationTimer != null) {
      debugPrint('GPS: stopping updates for order $_gpsOrderId');
    }
    _locationTimer?.cancel();
    _locationTimer = null;
    _gpsOrderId = null;
  }

  Future<void> _sendGpsPing(int orderId) async {
    if (_isSendingGps) return;
    _isSendingGps = true;

    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) return;

      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) return;
      }
      if (permission == LocationPermission.deniedForever) return;

      final Position pos = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      ).timeout(const Duration(seconds: 12));

      await ApiService.saveDriverLocation(
        orderId,
        pos.latitude,
        pos.longitude,
      );
    } catch (e) {
      debugPrint('GPS ping failed: $e');
    } finally {
      _isSendingGps = false;
    }
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
      ble.scanAndConnect();
      ble.readingStream.listen((data) {
        if (data['type'] == 'disconnected') {
          if (mounted) setState(() => bleStatus = 'Disconnected');
          return;
        }

        if (data['type'] == 'live') {
          setState(() {
            currentTemp = data['temperature'];
            currentHum = data['humidity'];
            tempAlert = data['is_alert'] ?? false;
            bleStatus = 'Connected';
          });

          if (currentTemp != null && currentHum != null) {
            if (DateTime.now().difference(_lastFgUpdate) >
                const Duration(seconds: 5)) {
              _lastFgUpdate = DateTime.now();
              ForegroundService.updateTemp(currentTemp!, currentHum!);
            }
          }

          if (currentDeliveryId != null &&
              currentTemp != null &&
              currentHum != null) {
            ApiService.saveSensorData(
                currentDeliveryId!, currentTemp!, currentHum!, tempAlert);
          }
        }
      });
    } catch (e) {
      debugPrint('BLE Error: $e');
    }
  }

  int? _parseInt(dynamic val) {
    if (val == null) return null;
    if (val is int) return val;
    if (val is String) return int.tryParse(val);
    return null;
  }

  int _parseIntOrZero(dynamic val) {
    return _parseInt(val) ?? 0;
  }

  // ─────────────────────────────────────────────────────────
  // LOAD DELIVERIES
  // ─────────────────────────────────────────────────────────
  Future<void> loadDeliveries() async {
    if (_isFetchingDeliveries) return;
    _isFetchingDeliveries = true;

    try {
      final data = await ApiService.getDeliveries(widget.driverId);
      if (!mounted) return;

      // 🔥 NULL CHECK ADDED
      if (data == null || data is! List) {
        debugPrint('Error: Data is not a list');
        _isFetchingDeliveries = false;
        return;
      }

      final activeData = data.where((item) {
        if (item is Map) {
          return item['delivery_status'] != 'delivered';
        }
        return true;
      }).toList();

      Map<String, dynamic>? findActive(List<dynamic> list) {
        for (var item in list) {
          if (item is Map) {
            final status = (item['delivery_status'] ?? '').toString();
            if (status != 'delivered') {
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
          currentDeliveryId =
          active != null ? _parseInt(active['delivery_id']) : null;
          _currentOrderId =
          active != null ? _parseInt(active['order_id']) : null;
        });
        _syncGpsWithDeliveries(activeData);
        return;
      }

      final oldIds = _previousDeliveries
          .whereType<Map>()
          .map((d) => d['order_id']?.toString())
          .where((id) => id != null)
          .toSet();
      final newIds = activeData
          .whereType<Map>()
          .map((d) => d['order_id']?.toString())
          .where((id) => id != null)
          .toSet();
      final removed = oldIds.difference(newIds);
      final added = newIds.difference(oldIds);

      if (removed.isNotEmpty) {
        _showBanner(
            'Order #${removed.first} was reassigned or delivered',
            AppColors.warning);
      } else if (added.isNotEmpty) {
        _showBanner('New order #${added.first} assigned',
            AppColors.success);
      }

      _previousDeliveries = List.from(activeData);
      final active = findActive(activeData);
      setState(() {
        deliveries = activeData;
        currentDeliveryId =
        active != null ? _parseInt(active['delivery_id']) : null;
        _currentOrderId =
        active != null ? _parseInt(active['order_id']) : null;
      });
      _syncGpsWithDeliveries(activeData);
    } catch (e) {
      debugPrint('Error loading deliveries: $e');
    } finally {
      _isFetchingDeliveries = false;
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
  // UPDATE STATUS
  // ─────────────────────────────────────────────────────────
  void updateStatus(dynamic rawDeliveryId, String status,
      {int? orderId}) async {
    final deliveryId = _parseIntOrZero(rawDeliveryId);
    final result =
    await ApiService.updateDeliveryStatus(deliveryId, status);
    if (!mounted) return;

    if (result['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Updated to $status')),
      );

      if (orderId != null) {
        if (status == 'picked_up' || status == 'in_transit') {
          _startGpsUpdates(orderId);
        } else if (status == 'delivered') {
          _stopGpsUpdates();
        }
      }

      loadDeliveries();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']?.toString() ?? 'Update failed'),
          backgroundColor: AppColors.danger,
        ),
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
      default:
        return AppColors.textSecondary;
    }
  }

  String _label(String s) => s.replaceAll('_', ' ').toUpperCase();

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
          IconButton(
            icon: const Icon(Icons.local_shipping_outlined),
            tooltip: 'Delivery History',
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) =>
                    DeliveryHistoryScreen(driverId: widget.driverId),
              ),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.sensors_rounded),
            tooltip: 'Sensor history',
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) =>
                    SensorHistoryScreen(driverId: widget.driverId),
              ),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: loadDeliveries,
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
      body: Column(
        children: [
          if (_bannerMessage.isNotEmpty)
            Container(
              margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
              padding:
              const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
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
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          if (_locationTimer != null)
            Container(
              margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
              padding:
              const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: AppColors.burgundy.withOpacity(0.08),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: AppColors.burgundy.withOpacity(0.25),
                ),
              ),
              child: Row(
                children: [
                  const Icon(Icons.gps_fixed_rounded,
                      size: 16, color: AppColors.burgundy),
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
          Expanded(
            child: deliveries.isEmpty
                ? _buildEmpty()
                : RefreshIndicator(
              color: AppColors.burgundy,
              onRefresh: loadDeliveries,
              child: ListView.builder(
                padding:
                const EdgeInsets.fromLTRB(16, 4, 16, 24),
                itemCount: deliveries.length,
                itemBuilder: (context, i) =>
                    _buildDeliveryCard(deliveries[i]),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMonitorCard() {
    final alert = tempAlert;
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: alert
            ? AppColors.danger.withOpacity(0.06)
            : AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: alert
              ? AppColors.danger.withOpacity(0.4)
              : AppColors.border,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.sensors_rounded,
                  size: 18, color: AppColors.burgundy),
              const SizedBox(width: 8),
              const Text(
                'IoT Monitor',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                  color: AppColors.textPrimary,
                ),
              ),
              const Spacer(),
              _statusPill(bleStatus, alert),
            ],
          ),
          const SizedBox(height: 14),
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
          if (alert) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding:
              const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: AppColors.danger,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Row(
                children: [
                  Icon(Icons.warning_rounded,
                      color: Colors.white, size: 16),
                  SizedBox(width: 6),
                  Text(
                    'Temperature out of range',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                      fontSize: 12,
                    ),
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
            style: const TextStyle(
              fontSize: 11,
              color: AppColors.textSecondary,
              letterSpacing: 0.4,
            ),
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
              Text(
                unit,
                style: const TextStyle(
                  fontSize: 13,
                  color: AppColors.textSecondary,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _statusPill(String status, bool alert) {
    final connected = status.toLowerCase().contains('connected');
    final color = alert
        ? AppColors.danger
        : connected
        ? AppColors.success
        : AppColors.textSecondary;
    final label = alert
        ? 'ALERT'
        : connected
        ? 'LIVE'
        : 'OFFLINE';
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration:
            BoxDecoration(color: color, shape: BoxShape.circle),
          ),
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
    final status = (cardMap['delivery_status'] ?? 'assigned').toString();
    final color = statusColor(status);
    final orderId = cardMap['order_id']?.toString() ?? '-';
    final orderIdInt = _parseInt(cardMap['order_id']);
    final deliveryId = cardMap['delivery_id'];

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

                // ── MAP BUTTON ──
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
                // ── END MAP BUTTON ──

                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 10, vertical: 5),
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
            _infoRow(Icons.storefront_rounded,
                (cardMap['retailer_name'] ?? '-').toString()),
            const SizedBox(height: 8),
            _infoRow(Icons.location_on_outlined,
                (cardMap['retailer_address'] ?? '-').toString()),
            const SizedBox(height: 8),
            _infoRow(Icons.phone_outlined,
                (cardMap['retailer_phone'] ?? '-').toString()),
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
                      'Products',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: AppColors.textSecondary,
                        letterSpacing: 0.4,
                      ),
                    ),
                    const SizedBox(height: 6),
                    ...items.map(
                          (it) => Padding(
                        padding:
                        const EdgeInsets.symmetric(vertical: 2),
                        child: Row(
                          children: [
                            Container(
                              width: 4,
                              height: 4,
                              decoration: const BoxDecoration(
                                color: AppColors.burgundy,
                                shape: BoxShape.circle,
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                (it is Map
                                    ? (it['product_name'] ?? '')
                                    : '')
                                    .toString(),
                                style: const TextStyle(
                                  fontSize: 13,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                            ),
                            Text(
                              '×${it is Map ? (it['quantity'] ?? '') : ''}',
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
            if (nextStatus != null) ...[
              const SizedBox(height: 14),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => updateStatus(
                    deliveryId,
                    nextStatus!,
                    orderId: orderIdInt,
                  ),
                  child: Text(nextLabel!),
                ),
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
            child: const Icon(Icons.inbox_rounded,
                size: 40, color: AppColors.burgundy),
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
            'New orders will appear here automatically',
            style: TextStyle(fontSize: 13, color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }
}