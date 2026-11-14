import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:latlong2/latlong.dart';
import '../services/api.dart';
import '../services/foreground_service.dart';
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
  final DraggableScrollableController _sheetController =
  DraggableScrollableController();

  // YOUR NEW API KEY
  final String _orsApiKey =
      'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjgzMTc3NzM5ZGYxYTQ2NzdhZTI1NTBlNmJkNmQ1ODJmIiwiaCI6Im11cm11cjY0In0=';

  LatLng? _driverLocation;
  LatLng? _retailerLocation;
  List<LatLng> _routePoints = [];

  bool _isLoading = true;
  bool _autoFitDone = false;
  double _routeDistanceKm = 0;
  int _routeDurationMin = 0;

  StreamSubscription<Position>? _positionStream;

  @override
  void initState() {
    super.initState();
    ForegroundService.pauseTracking();
    _startTracking();
  }

  @override
  void dispose() {
    ForegroundService.resumeTracking();
    _positionStream?.cancel();
    _sheetController.dispose();
    super.dispose();
  }

  Future<void> _startTracking() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      setState(() => _isLoading = false);
      return;
    }

    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        setState(() => _isLoading = false);
        return;
      }
    }

    _fetchRetailerLocation();

    _positionStream = Geolocator.getPositionStream(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: 10, // Changed to 10m to save API calls
      ),
    ).listen((Position position) {
      final newLatLng = LatLng(position.latitude, position.longitude);

      setState(() {
        _driverLocation = newLatLng;
        _isLoading = false;
      });

      if (_retailerLocation != null && _routePoints.isEmpty) {
        _fetchRoadRoute(newLatLng, _retailerLocation!);
      }

      if (!_autoFitDone) {
        _autoFitDone = true;
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) _fitCamera();
        });
      }
    });
  }

  Future<void> _fetchRetailerLocation() async {
    final loc = await ApiService.getDriverLocation(widget.orderId);
    if (!mounted) return;

    if (loc != null) {
      final rLat = loc['retailer_lat'] as double?;
      final rLng = loc['retailer_lng'] as double?;
      if (rLat != null && rLng != null) {
        setState(() {
          _retailerLocation = LatLng(rLat, rLng);
        });

        if (_driverLocation != null && _routePoints.isEmpty) {
          _fetchRoadRoute(_driverLocation!, _retailerLocation!);
        }
      }
    }
  }

  // ── EXACT SAME LOGIC AS THE RETAILER APP ──
  Future<void> _fetchRoadRoute(LatLng driver, LatLng store) async {
    try {
      final url = 'https://api.heigit.org/openrouteservice/v2/directions/driving-car'
          '?api_key=$_orsApiKey'
          '&start=${driver.longitude},${driver.latitude}'
          '&end=${store.longitude},${store.latitude}';

      final response = await http.get(Uri.parse(url));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);

        // Handle if the response is an array or object
        final List<dynamic> coords = data['features'][0]['geometry']['coordinates'];

        final points = coords
            .map((c) => LatLng((c[1] as num).toDouble(), (c[0] as num).toDouble()))
            .toList();

        if (mounted) {
          setState(() {
            _routePoints = points;

            // Calculate ETA from the successful API call
            final summary = data['features'][0]['properties']['summary'];
            final double distanceMeters = (summary['distance'] as num).toDouble();
            final double durationSeconds = (summary['duration'] as num).toDouble();
            _routeDistanceKm = distanceMeters / 1000;
            _routeDurationMin = (durationSeconds / 60).ceil();
          });
        }
      } else {
        debugPrint('ORS Error: ${response.statusCode} - ${response.body}');
      }
    } catch (e) {
      debugPrint('ORS Route Error: $e');
    }
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
          padding: const EdgeInsets.only(
            top: 60,
            bottom: 250,
            left: 60,
            right: 60,
          ),
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
          GestureDetector(
            onTap: () {
              if (_sheetController.isAttached) {
                _sheetController.animateTo(
                  0.12,
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
    final center =
        _driverLocation ?? _retailerLocation ?? const LatLng(14.5995, 120.9842);

    return FlutterMap(
      mapController: _mapController,
      options: MapOptions(
        initialCenter: center,
        initialZoom: 15,
        minZoom: 4,
        maxZoom: 18,
        interactionOptions: const InteractionOptions(
          flags: InteractiveFlag.all & ~InteractiveFlag.rotate,
        ),
      ),
      children: [
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'com.kimfresh.driver_app',
        ),
        if (_routePoints.isNotEmpty)
          PolylineLayer(
            polylines: [
              Polyline(
                points: _routePoints,
                strokeWidth: 6,
                color: AppColors.burgundy.withOpacity(0.8),
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
    String statusText;

    if (_retailerLocation != null && _routePoints.isNotEmpty) {
      if (_routeDurationMin < 1) {
        statusText = '< 1 min · ${_routeDistanceKm.toStringAsFixed(1)} km away';
      } else {
        statusText =
        '$_routeDurationMin min · ${_routeDistanceKm.toStringAsFixed(1)} km away';
      }
    } else if (_retailerLocation != null && _driverLocation != null) {
      statusText = 'Calculating route...';
    } else if (_retailerLocation == null) {
      statusText = 'Retailer hasn\'t shared their location yet';
    } else {
      statusText = 'Waiting for your GPS…';
    }

    return DraggableScrollableSheet(
      controller: _sheetController,
      initialChildSize: 0.25,
      minChildSize: 0.12,
      maxChildSize: 0.55,
      snap: true,
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
                      child: const Icon(
                        Icons.store,
                        color: Colors.green,
                        size: 24,
                      ),
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
                    horizontal: 14,
                    vertical: 12,
                  ),
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
                        _retailerLocation != null && _routePoints.isNotEmpty
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