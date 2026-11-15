import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_blue_plus/flutter_blue_plus.dart';
import 'package:geolocator/geolocator.dart';
import 'package:permission_handler/permission_handler.dart';
import '../services/api.dart';
import '../services/ble_service.dart';
import '../theme/app_theme.dart';

class SensorHistoryScreen extends StatefulWidget {
  final int driverId;
  SensorHistoryScreen({required this.driverId});

  @override
  _SensorHistoryScreenState createState() => _SensorHistoryScreenState();
}

class _SensorHistoryScreenState extends State<SensorHistoryScreen> {
  List<dynamic> readings = [];
  bool loading = true;
  Timer? _timer;

  // --- IoT Monitor State ---
  final BleService ble = BleService();
  String bleStatus = 'Not connected';
  double? currentTemp;
  double? currentHum;
  bool tempAlert = false;
  StreamSubscription? _bleSub;

  @override
  void initState() {
    super.initState();
    loadHistory();
    initBle();
    _timer = Timer.periodic(const Duration(seconds: 10), (_) {
      if (mounted) loadHistory(silent: true);
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _bleSub?.cancel();
    super.dispose();
  }

  Future<void> loadHistory({bool silent = false}) async {
    if (!silent) setState(() => loading = true);
    final data = await ApiService.getSensorHistory(widget.driverId);
    if (!mounted) return;
    setState(() {
      readings = data;
      loading = false;
    });
  }

  // --- BLE Logic from DeliveriesScreen ---
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
        }
      });
    } catch (e) {
      debugPrint('BLE Error: $e');
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

  String formatTime(String? raw) {
    if (raw == null) return '-';
    return raw
        .replaceAll(' GMT', '')
        .replaceAll(' UTC', '')
        .trim();
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
        iconTheme: const IconThemeData(color: AppColors.textPrimary),
        title: const Text(
          'Sensor History',
          style: TextStyle(
            color: AppColors.textPrimary,
            fontWeight: FontWeight.w900, // Matched the weight of Deliveries screen
            fontSize: 24, // Matched the size of Deliveries screen
          ),
        ),
      ),
      body: RefreshIndicator(
        color: AppColors.burgundy,
        onRefresh: () => loadHistory(),
        child: ListView(
          padding: const EdgeInsets.only(bottom: 24),
          children: [
            // --- IOT MONITOR CARD ADDED HERE ---
            _buildMonitorCard(),

            const Padding(
              padding: EdgeInsets.fromLTRB(16, 24, 16, 8),
              child: Text(
                'Log History',
                style: TextStyle(
                    fontSize: 18, // Matched section header
                    fontWeight: FontWeight.w800,
                    color: AppColors.textPrimary
                ),
              ),
            ),

            if (loading)
              const Padding(
                padding: EdgeInsets.all(32.0),
                child: Center(child: CircularProgressIndicator()),
              )
            else if (readings.isEmpty)
              Center(
                child: Padding(
                  padding: const EdgeInsets.all(32),
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
                        child: const Icon(Icons.sensors_off_rounded, size: 38, color: AppColors.burgundy),
                      ),
                      const SizedBox(height: 16),
                      const Text(
                        'No sensor readings yet',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
                      ),
                      const SizedBox(height: 6),
                      const Text(
                        'Connect to the ESP32 device to start logging.',
                        textAlign: TextAlign.center,
                        style: TextStyle(fontSize: 13, color: AppColors.textSecondary),
                      ),
                    ],
                  ),
                ),
              )
            else
              ...readings.map((r) => _buildReadingCard(r)).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildMonitorCard() {
    final alert = tempAlert;
    final isConnected = bleStatus == 'Connected';

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 16, 16, 8),
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
            Column(
              children: [
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.bluetooth_searching, size: 18),
                    label: const Text('Scan for ESP32 Sensor'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.burgundy,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    onPressed: _showBluetoothScanner,
                  ),
                ),
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    icon: const Icon(Icons.link, size: 18),
                    label: const Text('Connect to Saved Sensor'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.burgundy,
                      side: const BorderSide(color: AppColors.burgundy),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    onPressed: () async {
                      final messenger = ScaffoldMessenger.of(context);
                      messenger.showSnackBar(
                        const SnackBar(content: Text('Connecting to saved sensor...')),
                      );

                      final success = await ble.connectToSavedDevice();
                      if (!mounted) return;

                      if (success) {
                        setState(() => bleStatus = 'Connected');
                      } else {
                        messenger.showSnackBar(
                          const SnackBar(
                            content: Text('Failed to connect. Make sure ESP32 is on and nearby.'),
                            backgroundColor: Colors.red,
                          ),
                        );
                      }
                    },
                  ),
                ),
              ],
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

  Widget _buildReadingCard(dynamic r) {
    final isAlert = r['is_alert'] == true || r['is_alert'] == 1;
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isAlert ? AppColors.danger.withOpacity(0.5) : AppColors.border,
          width: isAlert ? 1.5 : 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                isAlert ? Icons.warning_amber_rounded : Icons.thermostat_rounded,
                size: 16,
                color: isAlert ? AppColors.danger : AppColors.burgundy,
              ),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  formatTime(r['reading_timestamp']),
                  style: const TextStyle(
                    color: AppColors.textSecondary,
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              if (isAlert)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.danger.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Text(
                    'ALERT',
                    style: TextStyle(
                      color: AppColors.danger,
                      fontWeight: FontWeight.w700,
                      fontSize: 10,
                      letterSpacing: 0.5,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: _metric(
                  label: 'Temperature',
                  value: '${r['temperature']}',
                  unit: '°C',
                  highlight: isAlert,
                ),
              ),
              Container(
                width: 1,
                height: 32,
                color: AppColors.border,
              ),
              Expanded(
                child: _metric(
                  label: 'Humidity',
                  value: '${r['humidity']}',
                  unit: '%',
                  highlight: isAlert,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _metric({
    required String label,
    required String value,
    required String unit,
    required bool highlight,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 10,
              color: AppColors.textSecondary,
              letterSpacing: 0.4,
            ),
          ),
          const SizedBox(height: 2),
          Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Text(
                value,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: highlight ? AppColors.danger : AppColors.textPrimary,
                ),
              ),
              const SizedBox(width: 3),
              Text(
                unit,
                style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.textSecondary,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}