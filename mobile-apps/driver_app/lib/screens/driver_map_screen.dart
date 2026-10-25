import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../services/api.dart';
import '../theme/app_theme.dart';

class DriverMapScreen extends StatefulWidget {
  final int orderId;
  final String orderStatus;
  final String? retailerName;
  final String? retailerAddress;

  const DriverMapScreen({
    super.key,
    required this.orderId,
    required this.orderStatus,
    this.retailerName,
    this.retailerAddress,
  });

  @override
  State<DriverMapScreen> createState() => _DriverMapScreenState();
}

class _DriverMapScreenState extends State<DriverMapScreen> {
  final MapController _mapController = MapController();
  final Distance _distance = const Distance();

  // Controls the bottom sheet size
  final DraggableScrollableController _sheetController = DraggableScrollableController();

  LatLng? _driverLocation;
  LatLng? _retailerLocation;

  bool _isLoading = true;
  bool _isFetching = false;
  bool _hasRetailer = false;
  Timer? _pollingTimer;
  bool _autoFitDone = false;

  @override
  void initState() {
    super.initState();
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
    _fetchLocations();
    _pollingTimer = Timer.periodic(const Duration(seconds: 10), (_) {
      _fetchLocations();
    });
  }

  Future<void> _fetchLocations() async {
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
        }

        final rLat = loc['retailer_lat'] as double?;
        final rLng = loc['retailer_lng'] as double?;
        if (rLat != null && rLng != null) {
          _retailerLocation = LatLng(rLat, rLng);
          _hasRetailer = true;
        }
      }
      _isLoading = false;
    });

    if (!_autoFitDone &&
        (_driverLocation != null || _retailerLocation != null)) {
      _autoFitDone = true;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _fitCamera();
      });
    }

    _isFetching = false;
  }

  void _fitCamera() {
    final points = <LatLng>[
      if (_driverLocation != null) _driverLocation!,
      if (_retailerLocation != null) _retailerLocation!,
    ];
    if (points.isEmpty) return;

    if (points.length == 1) {
      _mapController.move(points.first, 16);
      return;
    }

    try {
      _mapController.fitCamera(
        CameraFit.bounds(
          bounds: LatLngBounds.fromPoints(points),
          // Added bottom padding so it fits above the bottom sheet
          padding: const EdgeInsets.only(top: 60, bottom: 250, left: 60, right: 60),
        ),
      );
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Text('Order #${widget.orderId} map'),
        actions: [
          IconButton(
            icon: const Icon(Icons.my_location_rounded),
            tooltip: 'Recenter',
            onPressed: _fitCamera,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(
        child: CircularProgressIndicator(color: AppColors.burgundy),
      )
          : Stack(
        children: [
          // Wrap map in GestureDetector so tapping it hides the sheet
          GestureDetector(
            onTap: () {
              if (_sheetController.isAttached) {
                _sheetController.animateTo(
                  0.12, // Snap to hidden (approx height of the handle)
                  duration: const Duration(milliseconds: 300),
                  curve: Curves.easeOut,
                );
              }
            },
            child: _buildMap(),
          ),
          _buildInfoCard(),
        ],
      ),
    );
  }

  Widget _buildMap() {
    final center = _driverLocation ??
        _retailerLocation ??
        const LatLng(14.5995, 120.9842);

    final points = <LatLng>[
      if (_driverLocation != null) _driverLocation!,
      if (_retailerLocation != null) _retailerLocation!,
    ];

    return FlutterMap(
      mapController: _mapController,
      // 👇 RESTORED MAP OPTIONS HERE 👇
      options: MapOptions(
        initialCenter: center,
        initialZoom: 14,
        minZoom: 4,
        maxZoom: 18,
        interactionOptions: const InteractionOptions(
          flags: InteractiveFlag.all & ~InteractiveFlag.rotate,
        ),
      ),
      // 👆 ------------------------- 👆
      children: [
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'com.kimfresh.driver_app',
        ),
        if (points.length == 2)
          PolylineLayer(
            polylines: [
              Polyline(
                points: points,
                strokeWidth: 4,
                color: AppColors.burgundy.withOpacity(0.5),
                pattern: StrokePattern.dashed(segments: const [10, 10]),
              ),
            ],
          ),
        MarkerLayer(
          markers: [
            if (_retailerLocation != null)
              Marker(
                point: _retailerLocation!,
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
                    color: AppColors.burgundy,
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 3),
                    boxShadow: const [
                      BoxShadow(color: Colors.black26, blurRadius: 4),
                    ],
                  ),
                  child: const Icon(
                    Icons.delivery_dining,
                    color: Colors.white,
                    size: 22,
                  ),
                ),
              ),
          ],
        ),
      ],
    );
  }

  Widget _buildInfoCard() {
    double distanceMeters = 0;
    int etaMinutes = 0;

    if (_driverLocation != null && _retailerLocation != null) {
      final d = _distance.as(
        LengthUnit.Meter,
        _driverLocation!,
        _retailerLocation!,
      );
      // d < 100000 (100km) prevents the crazy 11,432,127 meter bug from showing
      if (d.isFinite && d >= 0 && d < 100000) {
        distanceMeters = d;
        etaMinutes = (distanceMeters / 8.33 / 60).round();
      }
    }

    String statusText;
    if (_hasRetailer && _driverLocation != null) {
      if (etaMinutes < 1) {
        statusText = '< 1 min · ${distanceMeters.toStringAsFixed(0)} m away';
      } else {
        statusText = '$etaMinutes min · ${distanceMeters.toStringAsFixed(0)} m away';
      }
    } else if (_hasRetailer && _driverLocation == null) {
      statusText = 'Waiting for your GPS…';
    } else if (!_hasRetailer && _driverLocation != null) {
      statusText = 'Retailer hasn\'t shared their location yet';
    } else {
      statusText = 'Waiting for GPS data…';
    }

    return DraggableScrollableSheet(
      controller: _sheetController,
      initialChildSize: 0.25, // Starts lower because the card is shorter
      minChildSize: 0.12,     // Drag all the way down
      maxChildSize: 0.55,     // Drag up
      snap: true,             // Smoothly snaps into place
      snapSizes: const [0.12, 0.25, 0.55],
      builder: (context, scrollController) {
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 20)],
          ),
          child: SingleChildScrollView(
            controller: scrollController,
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
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
                const SizedBox(height: 16),
                Row(
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: Colors.green.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.store,
                          color: Colors.green, size: 24),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            widget.retailerName ?? 'Retailer',
                            style: const TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          if (widget.retailerAddress != null &&
                              widget.retailerAddress!.isNotEmpty) ...[
                            const SizedBox(height: 2),
                            Text(
                              widget.retailerAddress!,
                              style: const TextStyle(
                                fontSize: 12,
                                color: AppColors.textSecondary,
                                height: 1.3,
                              ),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(
                      horizontal: 14, vertical: 12),
                  decoration: BoxDecoration(
                    color: AppColors.burgundy.withOpacity(0.06),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: AppColors.burgundy.withOpacity(0.2),
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        _hasRetailer && _driverLocation != null
                            ? Icons.route_rounded
                            : Icons.hourglass_top_rounded,
                        size: 18,
                        color: AppColors.burgundy,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          statusText,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: AppColors.burgundy,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}