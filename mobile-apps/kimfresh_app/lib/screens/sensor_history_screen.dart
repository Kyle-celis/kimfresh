import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart'; // For HapticFeedback
import 'package:flutter_ringtone_player/flutter_ringtone_player.dart'; // ✅ For loud alarm
import 'package:flutter_blue_plus/flutter_blue_plus.dart';
import '../services/api.dart';
import '../services/ble_service.dart';
import '../theme/app_theme.dart';

class SensorHistoryScreen extends StatefulWidget {
  final int driverId;
  final int? deliveryId;

  SensorHistoryScreen({required this.driverId, this.deliveryId});

  @override
  _SensorHistoryScreenState createState() => _SensorHistoryScreenState();
}

class _SensorHistoryScreenState extends State<SensorHistoryScreen> {
  List<dynamic> readings = [];
  bool loading = true;
  Timer? _timer;
  DateTime _lastSaved = DateTime.now();

  final BleService ble = BleService.instance;

  String bleStatus = 'Not connected';
  double? currentTemp;
  double? currentHum;

  String currentAlertLevel = 'safe'; // ✅ Tracks 'safe', 'warning', or 'critical'
  String _lastAlertLevel = 'safe';
  DateTime _lastBeepTime = DateTime(0);

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

  void initBle() {
    try {
      if (!ble.isConnected) {
        setState(() => bleStatus = 'Connecting...');
        ble.connectToSavedDevice().then((success) {
          if (success && mounted) {
            setState(() => bleStatus = 'Connected');
          } else if (mounted) {
            setState(() => bleStatus = 'Not connected');
          }
        });
      } else {
        setState(() => bleStatus = 'Connected');
      }

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
            currentAlertLevel = alertLevel; // ✅ Save the level for UI colors
          });

          // ✅ PLAY ALARM SOUND
          if (alertLevel != 'safe' && alertLevel != _lastAlertLevel) {
            _lastAlertLevel = alertLevel;
            _playAlarm(alertLevel);
          } else if (alertLevel == 'safe') {
            _lastAlertLevel = 'safe';
          }

          // Save to DB
          if (widget.deliveryId != null) {
            final now = DateTime.now();
            if (now.difference(_lastSaved).inSeconds >= 10) {
              _lastSaved = now;
              ApiService.saveSensorData(
                  widget.deliveryId!,
                  data['temperature'],
                  data['humidity'],
                  alertLevel != 'safe'
              );
            }
          }
        }
      });
    } catch (e) {
      debugPrint('BLE Error: $e');
    }
  }

  // ✅ BEEP ALARM FUNCTION using Ringtone Player
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
    return raw.replaceAll(' GMT', '').replaceAll(' UTC', '').trim();
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
            fontWeight: FontWeight.w900,
            fontSize: 24,
          ),
        ),
      ),
      body: RefreshIndicator(
        color: AppColors.burgundy,
        onRefresh: () => loadHistory(),
        child: ListView(
          padding: const EdgeInsets.only(bottom: 24),
          children: [
            _buildMonitorCard(),
            const Padding(
              padding: EdgeInsets.fromLTRB(16, 24, 16, 8),
              child: Text(
                'Log History',
                style: TextStyle(
                    fontSize: 18,
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
    final isWarning = currentAlertLevel == 'warning';   // ✅ 5 to 14.9
    final isCritical = currentAlertLevel == 'critical'; // ✅ 15+
    final isConnected = bleStatus == 'Connected';

    // Determine card color
    Color cardColor = AppColors.surface;
    Color borderColor = AppColors.border;
    Color textColor = AppColors.textPrimary;

    if (isCritical) {
      cardColor = AppColors.danger.withOpacity(0.06);
      borderColor = AppColors.danger.withOpacity(0.4);
      textColor = AppColors.danger;
    } else if (isWarning) {
      cardColor = Colors.amber.withOpacity(0.08); // ✅ YELLOW BACKGROUND
      borderColor = Colors.amber.withOpacity(0.5);
      textColor = Colors.amber.shade800; // Dark yellow text for visibility
    }

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                  Icons.sensors_rounded,
                  size: 18,
                  color: isCritical ? AppColors.danger : isWarning ? Colors.amber.shade800 : AppColors.burgundy
              ),
              const SizedBox(width: 8),
              Text(
                'IoT Monitor',
                style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: textColor),
              ),
              const Spacer(),
              _statusPill(bleStatus, currentAlertLevel),
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
                    color: textColor, // ✅ Use dynamic color
                  ),
                ),
                Container(width: 1, height: 44, color: borderColor),
                Expanded(
                  child: _statBlock(
                    label: 'Humidity',
                    value: currentHum?.toStringAsFixed(1) ?? '--',
                    unit: '%',
                    color: textColor, // ✅ Use dynamic color
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
          if (isCritical || isWarning) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                  color: isCritical ? AppColors.danger : Colors.amber.shade700, // ✅ Yellow vs Red
                  borderRadius: BorderRadius.circular(8)
              ),
              child: Row(
                children: [
                  const Icon(Icons.warning_rounded, color: Colors.white, size: 16),
                  const SizedBox(width: 6),
                  Text(
                    isCritical ? 'CRITICAL: Temp above 15°C' : 'WARNING: Temp above 5°C',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 12),
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
    required Color color, // ✅ Changed from bool alert to Color
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
                  color: color, // ✅ Use dynamic color
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

  Widget _statusPill(String status, String alertLevel) {
    final connected = status.toLowerCase().contains('connected');
    final isCritical = alertLevel == 'critical';
    final isWarning = alertLevel == 'warning';

    Color color = AppColors.textSecondary;
    String label = 'OFFLINE';

    if (isCritical) {
      color = AppColors.danger;
      label = 'CRITICAL';
    } else if (isWarning) {
      color = Colors.amber.shade700;
      label = 'WARNING';
    } else if (connected) {
      color = AppColors.success;
      label = 'LIVE';
    }

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
    // ✅ SAFE PARSING: Handles both String ("32.1") and Number (32.1)
    final temp = double.tryParse(r['temperature']?.toString() ?? '0') ?? 0.0;

    // ✅ Recalculate warning/critical from the raw temp for history cards
    final isCritical = temp >= 15.0;
    final isWarning = temp >= 5.0 && !isCritical;

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isCritical
              ? AppColors.danger.withOpacity(0.5)
              : isWarning
              ? Colors.amber.withOpacity(0.5)
              : AppColors.border,
          width: (isCritical || isWarning) ? 1.5 : 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                isCritical
                    ? Icons.warning_amber_rounded
                    : isWarning
                    ? Icons.warning_rounded
                    : Icons.thermostat_rounded,
                size: 16,
                color: isCritical ? AppColors.danger : isWarning ? Colors.amber.shade700 : AppColors.burgundy,
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
              if (isCritical || isWarning)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: isCritical ? AppColors.danger.withOpacity(0.1) : Colors.amber.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    isCritical ? 'CRITICAL' : 'WARNING',
                    style: TextStyle(
                      color: isCritical ? AppColors.danger : Colors.amber.shade800,
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
                  color: isCritical ? AppColors.danger : isWarning ? Colors.amber.shade800 : AppColors.textPrimary,
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
                  color: AppColors.textPrimary,
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
    required Color color,
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
                  color: color,
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