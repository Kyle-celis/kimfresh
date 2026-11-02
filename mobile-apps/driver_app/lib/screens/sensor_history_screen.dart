import 'dart:async';
import 'package:flutter/material.dart';
import '../services/api.dart';
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

  @override
  void initState() {
    super.initState();
    loadHistory();
    _timer = Timer.periodic(const Duration(seconds: 10), (_) {
      if (mounted) loadHistory(silent: true);
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
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

  String formatTime(String? raw) {
    if (raw == null) return '-';
    return raw
        .replaceAll(' GMT', '')
        .replaceAll(' UTC', '')
        .trim();
  }

  @override
  Widget build(BuildContext context) {
    Widget body;
    if (loading) {
      body = const Center(child: CircularProgressIndicator());
    } else if (readings.isEmpty) {
      body = Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
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
                child: const Icon(Icons.sensors_off_rounded,
                    size: 38, color: AppColors.burgundy),
              ),
              const SizedBox(height: 16),
              const Text(
                'No sensor readings yet',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: AppColors.textPrimary,
                ),
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
      );
    } else {
      body = ListView.builder(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        itemCount: readings.length,
        itemBuilder: (context, i) {
          final r = readings[i];
          final isAlert = r['is_alert'] == true || r['is_alert'] == 1;
          return Container(
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: isAlert
                    ? AppColors.danger.withOpacity(0.5)
                    : AppColors.border,
                width: isAlert ? 1.5 : 1,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(
                      isAlert
                          ? Icons.warning_amber_rounded
                          : Icons.thermostat_rounded,
                      size: 16,
                      color: isAlert
                          ? AppColors.danger
                          : AppColors.burgundy,
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
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 3),
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
        },
      );
    }

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: const Text('Sensor History'),
      ),
      body: RefreshIndicator(
        color: AppColors.burgundy,
        onRefresh: () => loadHistory(),
        child: body,
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
                  color: highlight
                      ? AppColors.danger
                      : AppColors.textPrimary,
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