import 'package:flutter/material.dart';
import '../services/api.dart';
import 'dart:async';

class SensorHistoryScreen extends StatefulWidget {
  final int driverId;
  SensorHistoryScreen({required this.driverId});

  @override
  _SensorHistoryScreenState createState() => _SensorHistoryScreenState();
}

class _SensorHistoryScreenState extends State<SensorHistoryScreen> {
  List<dynamic> readings = [];
  bool loading = true;

  @override
  void initState() {
    super.initState();
    loadHistory();

    // Auto-refresh every 10 seconds
    Timer.periodic(Duration(seconds: 10), (_) {
      if (mounted) loadHistory(silent: true);
    });
  }

  Future<void> loadHistory({bool silent = false}) async {
    if (!silent) {
      setState(() => loading = true);
    }
    final data = await ApiService.getSensorHistory(widget.driverId);
    if (!mounted) return;
    setState(() {
      readings = data;
      loading = false;
    });
  }

  String formatTime(String? raw) {
    if (raw == null) return '-';
    // Just remove the GMT/UTC suffix, keep the rest as-is
    return raw
        .replaceAll(' GMT', '')
        .replaceAll(' UTC', '')
        .trim();
  }

  @override
  Widget build(BuildContext context) {
    Widget body;

    if (loading) {
      body = Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 15),
            Text('Loading...'),
          ],
        ),
      );
    } else if (readings.isEmpty) {
      body = Center(
        child: Padding(
          padding: EdgeInsets.all(20),
          child: Text(
            'No sensor readings yet.\nConnect to ESP32.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.grey, fontSize: 16),
          ),
        ),
      );
    } else {
      body = ListView.builder(
        itemCount: readings.length,
        itemBuilder: (context, i) {
          final r = readings[i];
          final isAlert = r['is_alert'] == true || r['is_alert'] == 1;

          return Container(
            margin: EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            padding: EdgeInsets.all(15),
            decoration: BoxDecoration(
              color: isAlert ? Colors.red[50] : Colors.white,
              border: Border.all(
                color: isAlert ? Colors.red : Colors.grey[300]!,
                width: isAlert ? 2 : 1,
              ),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      formatTime(r['reading_timestamp']),
                      style: TextStyle(color: Colors.grey[600], fontSize: 13),
                    ),
                    if (isAlert)
                      Text(
                        'ALERT',
                        style: TextStyle(
                          color: Colors.red,
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                        ),
                      ),
                  ],
                ),
                SizedBox(height: 8),
                Text(
                  '${r['temperature']} °C  |  ${r['humidity']} %',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: isAlert ? Colors.red[900] : Colors.black87,
                  ),
                ),
              ],
            ),
          );
        },
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text('Sensor History'),
        automaticallyImplyLeading: false,
      ),
      body: RefreshIndicator(
        onRefresh: () => loadHistory(),
        child: body,
      ),
    );
  }
}