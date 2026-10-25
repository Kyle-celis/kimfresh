import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../services/api.dart';
import '../theme/colors.dart';

class OrderTrackingMapScreen extends StatefulWidget {
  final int orderId;
  final String orderStatus;
  final String? courierName;
  final double? destLat;
  final double? destLng;
  final String? productName;
  final int? productQuantity;

  const OrderTrackingMapScreen({
    super.key,
    required this.orderId,
    required this.orderStatus,
    this.courierName,
    this.destLat,
    this.destLng,
    this.productName,
    this.productQuantity,
  });

  @override
  State<OrderTrackingMapScreen> createState() => _OrderTrackingMapScreenState();
}

class _OrderTrackingMapScreenState extends State<OrderTrackingMapScreen> {
  final MapController _mapController = MapController();
  final Distance _distance = const Distance();

  final DraggableScrollableController _sheetController = DraggableScrollableController();

  LatLng? _dest;
  LatLng? _driverLocation;

  bool _isLoading = true;
  bool _isFetching = false;
  bool _hasDriver = false;
  bool _hasError = false;
  Timer? _pollingTimer;
  bool _autoFitDone = false;

  @override
  void initState() {
    super.initState();
    _dest = (widget.destLat != null && widget.destLng != null)
        ? LatLng(widget.destLat!, widget.destLng!)
        : null;

    if (widget.orderStatus.toLowerCase() == 'delivered') {
      _isLoading = false;
    } else {
      _startPolling();
    }
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    _sheetController.dispose();
    super.dispose();
  }

  void _startPolling() {
    _fetchDriverLocation();
    _pollingTimer = Timer.periodic(const Duration(seconds: 10), (_) {
      _fetchDriverLocation();
    });
  }

  Future<void> _fetchDriverLocation() async {
    if (_isFetching) return;
    _isFetching = true;

    final loc = await ApiService.getDriverLocation(widget.orderId);

    if (!mounted) {
      _isFetching = false;
      return;
    }

    setState(() {
      if (loc != null) {
        final dLat = loc['driver_lat'] as double?;
        final dLng = loc['driver_lng'] as double?;
        if (dLat != null && dLng != null) {
          _driverLocation = LatLng(dLat, dLng);
          _hasDriver = true;
        }

        final rLat = loc['retailer_lat'] as double?;
        final rLng = loc['retailer_lng'] as double?;
        if (rLat != null && rLng != null) {
          _dest = LatLng(rLat, rLng);
        }

        _hasError = false;
      } else if (!_hasDriver) {
        _hasError = false;
      }
      _isLoading = false;
    });

    if (_hasDriver && !_autoFitDone) {
      _autoFitDone = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _fitCameraToMarkers();
      });
    }

    _isFetching = false;
  }

  void _fitCameraToMarkers() {
    final dest = _dest;
    if (dest == null) return;

    if (_driverLocation == null) {
      _mapController.move(dest, 14);
      return;
    }

    try {
      _mapController.fitCamera(
        CameraFit.bounds(
          bounds: LatLngBounds(_driverLocation!, dest),
          padding: const EdgeInsets.only(top: 100, bottom: 300, left: 50, right: 50),
        ),
      );
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final dest = _dest;
    if (dest == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Tracking')),
        body: const Center(child: Text('No destination on file')),
      );
    }

    return Scaffold(
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppColors.brand))
          : Stack(
        children: [
          GestureDetector(
            onTap: () {
              if (_sheetController.isAttached) {
                _sheetController.animateTo(
                  0.1,
                  duration: const Duration(milliseconds: 300),
                  curve: Curves.easeOut,
                );
              }
            },
            child: _buildMap(dest),
          ),
          _buildTopBar(),
          _buildBottomSheet(dest),
        ],
      ),
    );
  }

  Widget _buildMap(LatLng dest) {
    return FlutterMap(
      mapController: _mapController,
      options: MapOptions(
        initialCenter: dest,
        initialZoom: 14,
        minZoom: 4,
        maxZoom: 18,
        interactionOptions: const InteractionOptions(
          flags: InteractiveFlag.all & ~InteractiveFlag.rotate,
        ),
      ),
      children: [
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'com.kimfresh.retailer_app',
        ),
        if (_driverLocation != null)
          PolylineLayer(
            polylines: [
              Polyline(
                points: [_driverLocation!, dest],
                strokeWidth: 4,
                color: AppColors.brand.withOpacity(0.5),
                pattern: StrokePattern.dashed(segments: const [10, 10]),
              ),
            ],
          ),
        MarkerLayer(
          markers: [
            Marker(
              point: dest,
              width: 44,
              height: 44,
              child: const Icon(Icons.store, color: Colors.green, size: 40),
            ),
            if (_driverLocation != null)
              Marker(
                point: _driverLocation!,
                width: 44,
                height: 44,
                child: Container(
                  decoration: BoxDecoration(
                    color: const Color(0xFFD35400),
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 3),
                    boxShadow: const [BoxShadow(color: Colors.black26, blurRadius: 4)],
                  ),
                  child: const Icon(Icons.delivery_dining, color: Colors.white, size: 22),
                ),
              ),
          ],
        ),
      ],
    );
  }

  Widget _buildTopBar() {
    return Positioned(
      top: MediaQuery.of(context).padding.top + 16,
      left: 16,
      right: 16,
      child: Row(
        children: [
          Material(
            color: Colors.white,
            elevation: 2,
            shape: const CircleBorder(),
            child: IconButton(
              icon: const Icon(Icons.arrow_back, color: Colors.black),
              onPressed: () => Navigator.pop(context),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(30),
                boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 10)],
              ),
              child: Row(
                children: [
                  Icon(
                    _hasDriver ? Icons.local_shipping_rounded : Icons.access_time,
                    size: 20,
                    color: _hasDriver ? const Color(0xFFD35400) : Colors.black87,
                  ),
                  const SizedBox(width: 8),
                  Text(
                    _topStatusText(),
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  const Spacer(),
                  IconButton(
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(),
                    icon: const Icon(Icons.my_location, size: 20, color: AppColors.brand),
                    onPressed: _fitCameraToMarkers,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _topStatusText() {
    if (_hasError) return 'Signal lost — retrying…';
    if (!_hasDriver) return 'Locating driver…';
    switch (widget.orderStatus.toLowerCase()) {
      case 'delivered':
        return 'Delivered';
      case 'in_transit':
        return 'On the way';
      case 'picked_up':
        return 'Picked up';
      case 'assigned':
        return 'Preparing';
      default:
        return 'Tracking';
    }
  }

  Widget _buildBottomSheet(LatLng dest) {
    double distanceMeters = 0;
    int etaMinutes = 0;

    if (_driverLocation != null) {
      final d = _distance.as(LengthUnit.Meter, _driverLocation!, dest);
      if (d.isFinite && d >= 0 && d < 100000) {
        distanceMeters = d;
        etaMinutes = (distanceMeters / 8.33 / 60).round();
      }
    }

    final s = widget.orderStatus.toLowerCase();
    final isPending = s == 'pending';
    final isAssigned = s == 'assigned' || s == 'confirmed' || s == 'approved';
    final isPickedUp = s == 'picked_up';
    final isInTransit = s == 'in_transit';
    final isDelivered = s == 'delivered';

    final activePending = isPending;
    final activeKitchen = isAssigned;
    final activePickedUp = isPickedUp;
    final activeTransit = isInTransit;
    final activeDelivered = isDelivered;

    return DraggableScrollableSheet(
      controller: _sheetController,
      initialChildSize: 0.55,
      minChildSize: 0.1,
      maxChildSize: 0.85,
      snap: true,
      snapSizes: const [0.1, 0.55, 0.85],
      builder: (context, scrollController) {
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
            boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 20)],
          ),
          child: SingleChildScrollView(
            controller: scrollController,
            padding: const EdgeInsets.only(top: 12, left: 24, right: 24, bottom: 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  _etaLabel(),
                  style: TextStyle(
                    color: _hasDriver ? Colors.grey[600] : Colors.orange[700],
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  _etaValue(etaMinutes, distanceMeters),
                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 24),

                // ── DYNAMIC TIMELINE ──
                _buildTimelineRow('Order confirmed', true, activePending),
                _buildTimelineRow(
                  'Order sent to kitchen',
                  isAssigned || isPickedUp || isInTransit || isDelivered,
                  activeKitchen,
                ),
                _buildTimelineRow(
                  'Rider has picked up order',
                  isPickedUp || isInTransit || isDelivered,
                  activePickedUp,
                ),
                _buildTimelineRow(
                  'Out for delivery',
                  isInTransit || isDelivered,
                  activeTransit,
                ),
                _buildTimelineRow(
                  'Delivered',
                  isDelivered,
                  activeDelivered,
                ),

                const SizedBox(height: 24),
                const Divider(),
                const SizedBox(height: 16),

                // ── ORDER DETAILS SECTION ──
                const Text(
                  'Order Details',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 12),
                _buildDetailRow(Icons.receipt_long, 'Order ID', '#${widget.orderId}'),
                const SizedBox(height: 8),
                _buildDetailRow(
                  Icons.local_shipping,
                  'Driver',
                  widget.courierName ?? 'Waiting for assignment...',
                ),
                const SizedBox(height: 12),

                // ── PRODUCT LIST LAYOUT ──
                _buildProductList(),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildProductList() {
    final String rawNames = widget.productName ?? 'Kimchi Product';

    // Splits the string by comma in case there are multiple products
    final List<String> items = rawNames.split(',');

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.inventory_2_outlined, size: 18, color: Colors.grey[500]),
              const SizedBox(width: 12),
              const Text(
                'Order Items',
                style: TextStyle(fontSize: 12, color: Colors.grey, fontWeight: FontWeight.w600),
              ),
            ],
          ),
          const SizedBox(height: 8),
          // Loop through each product and show it
          ...items.map((item) {
            final cleanName = item.trim();
            if (cleanName.isEmpty) return const SizedBox.shrink();

            return Padding(
              padding: const EdgeInsets.only(bottom: 6.0, left: 30.0),
              child: Text(
                cleanName,
                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
              ),
            );
          }).toList(),
        ],
      ),
    );
  }

  Widget _buildDetailRow(IconData icon, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(top: 2.0),
          child: Icon(icon, size: 18, color: Colors.grey[500]),
        ),
        const SizedBox(width: 12),
        Text(
          label,
          style: TextStyle(fontSize: 14, color: Colors.grey[600]),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Text(
            value,
            textAlign: TextAlign.right,
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
          ),
        ),
      ],
    );
  }

  String _etaLabel() {
    if (_hasError) return 'Signal lost — retrying…';
    if (!_hasDriver) return 'Waiting for driver GPS…';
    return 'Estimated delivery time';
  }

  String _etaValue(int etaMinutes, double distanceMeters) {
    if (!_hasDriver) return '--:--';

    if (etaMinutes < 1) {
      return '< 1 min · ${distanceMeters.toStringAsFixed(0)} m away';
    }
    if (distanceMeters >= 1000) {
      return '$etaMinutes min · ${(distanceMeters / 1000).toStringAsFixed(1)} km away';
    }
    return '$etaMinutes min · ${distanceMeters.toStringAsFixed(0)} m away';
  }

  Widget _buildTimelineRow(String title, bool isCompleted, bool isActive) {
    final color = isCompleted
        ? const Color(0xFFD35400)
        : isActive
        ? const Color(0xFFD35400)
        : Colors.grey[400];

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 16,
              height: 16,
              decoration: BoxDecoration(
                color: isCompleted ? const Color(0xFFD35400) : Colors.white,
                border: Border.all(color: color!, width: 2),
                shape: BoxShape.circle,
              ),
              child: isCompleted
                  ? const Icon(Icons.check, size: 10, color: Colors.white)
                  : null,
            ),
            if (title != 'Delivered')
              Container(
                width: 2,
                height: 30,
                color: isCompleted
                    ? const Color(0xFFD35400)
                    : Colors.grey[300],
              ),
          ],
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: Text(
              title,
              style: TextStyle(
                fontWeight: (isActive || isCompleted) ? FontWeight.bold : FontWeight.normal,
                color: (isActive || isCompleted) ? Colors.black : Colors.black54,
              ),
            ),
          ),
        ),
      ],
    );
  }
}