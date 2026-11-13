import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import '../theme/app_theme.dart';

class LocationGate extends StatefulWidget {
  final Widget child;
  const LocationGate({super.key, required this.child});

  @override
  State<LocationGate> createState() => _LocationGateState();
}

class _LocationGateState extends State<LocationGate>
    with WidgetsBindingObserver {
  bool _checking = true;
  bool _servicesEnabled = false;
  LocationPermission _permission = LocationPermission.denied;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _check();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Re-check when user comes back from system settings
    if (state == AppLifecycleState.resumed) _check();
  }

  Future<void> _check() async {
    try {
      final enabled = await Geolocator.isLocationServiceEnabled();
      final perm = await Geolocator.checkPermission();
      if (!mounted) return;
      setState(() {
        _servicesEnabled = enabled;
        _permission = perm;
        _checking = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _servicesEnabled = false;
        _permission = LocationPermission.denied;
        _checking = false;
      });
    }
  }

  Future<void> _requestPermission() async {
    final result = await Geolocator.requestPermission();
    if (!mounted) return;
    setState(() => _permission = result);
  }

  Future<void> _openLocationSettings() async {
    await Geolocator.openLocationSettings();
  }

  Future<void> _openAppSettings() async {
    await Geolocator.openAppSettings();
  }

  @override
  Widget build(BuildContext context) {
    if (_checking) {
      return const Scaffold(
        backgroundColor: AppColors.canvas,
        body: Center(
          child: CircularProgressIndicator(color: AppColors.burgundy),
        ),
      );
    }

    final permGranted = _permission == LocationPermission.whileInUse ||
        _permission == LocationPermission.always;
    final permanentlyDenied =
        _permission == LocationPermission.deniedForever;

    if (_servicesEnabled && permGranted) {
      return widget.child;
    }

    // Determine which prompt to show
    final needsServices = !_servicesEnabled;
    final needsPermission = _servicesEnabled && !permGranted;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Container(
            padding: const EdgeInsets.all(28),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 76,
                  height: 76,
                  decoration: BoxDecoration(
                    color: AppColors.burgundy.withOpacity(0.08),
                    shape: BoxShape.circle,
                  ),
                  alignment: Alignment.center,
                  child: const Icon(
                    Icons.location_on_rounded,
                    size: 38,
                    color: AppColors.burgundy,
                  ),
                ),
                const SizedBox(height: 22),
                Text(
                  needsServices
                      ? 'Location services are off'
                      : 'Location permission needed',
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  needsServices
                      ? 'KimFresh Driver needs Location services enabled to scan for the delivery temperature sensor via Bluetooth.'
                      : 'Please allow location access so the app can find nearby Bluetooth sensors.',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 14,
                    height: 1.45,
                    color: AppColors.textSecondary,
                  ),
                ),
                if (permanentlyDenied) ...[
                  const SizedBox(height: 14),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 8),
                    decoration: BoxDecoration(
                      color: AppColors.danger.withOpacity(0.08),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Text(
                      'Permission permanently denied. Please enable it from App Settings.',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: AppColors.danger,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
                const SizedBox(height: 24),
                if (needsServices)
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _openLocationSettings,
                      icon: const Icon(Icons.settings_rounded),
                      label: const Text('Open Location Settings'),
                    ),
                  )
                else if (permanentlyDenied)
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _openAppSettings,
                      icon: const Icon(Icons.settings_rounded),
                      label: const Text('Open App Settings'),
                    ),
                  )
                else
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _requestPermission,
                      icon: const Icon(Icons.location_on_rounded),
                      label: const Text('Allow Location'),
                    ),
                  ),
                const SizedBox(height: 10),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton(
                    onPressed: _check,
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size(0, 48),
                      side: const BorderSide(color: AppColors.border),
                      foregroundColor: AppColors.textPrimary,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: const Text("I've enabled it — Retry"),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}