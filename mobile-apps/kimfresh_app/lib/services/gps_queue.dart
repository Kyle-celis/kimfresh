import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// GPS-specific retry queue used *inside* the foreground service isolate.
/// Persisted via shared_preferences so it survives isolate restarts and app kills.
class GpsQueue {
  static const String _prefKey = 'fg_gps_queue';
  static const String _endpoint = 'http://10.0.2.2/php-web/save_location.php';
  static const int _maxAttempts = 10;

  final List<Map<String, dynamic>> _items = [];
  bool _flushing = false;
  int _consecutiveFailures = 0;
  Timer? _backoffTimer;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_prefKey);
    if (raw != null) {
      try {
        _items.addAll(
          (jsonDecode(raw) as List).cast<Map<String, dynamic>>(),
        );
      } catch (e) {
        debugPrint('GpsQueue: corrupt store, dropping: $e');
      }
    }
    if (_items.isNotEmpty) {
      debugPrint('GpsQueue: restored ${_items.length} pending items');
      unawaited(flush());
    }
  }

  Future<void> enqueue(Map<String, dynamic> item) async {
    final key = item['dedup_key']?.toString();
    if (key != null && _items.any((e) => e['dedup_key'] == key)) return;

    // For GPS, only the freshest position per order matters.
    // Collapse any *unsent* pings for the same order so a network
    // outage doesn't queue N stale positions that all fire at once
    // when connectivity returns.
    final orderId = item['order_id'];
    _items.removeWhere(
          (e) =>
      e['order_id'] == orderId &&
          ((e['attempts'] as int?) ?? 0) == 0,
    );

    _items.add({...item, 'attempts': 0});
    await _persist();
    unawaited(flush());
  }

  Future<void> flush() async {
    if (_flushing || _items.isEmpty) return;
    _flushing = true;

    try {
      final snapshot = List<Map<String, dynamic>>.from(_items);
      final succeeded = <Map<String, dynamic>>[];

      for (final item in snapshot) {
        try {
          final ok = await _send(item);
          if (ok) {
            succeeded.add(item);
          } else {
            item['attempts'] = ((item['attempts'] as int?) ?? 0) + 1;
            if ((item['attempts'] as int) >= _maxAttempts) {
              debugPrint(
                'GpsQueue: dropping after $_maxAttempts attempts',
              );
              succeeded.add(item);
            }
          }
        } catch (e) {
          item['attempts'] = ((item['attempts'] as int?) ?? 0) + 1;
        }
      }

      _items.removeWhere((e) => succeeded.contains(e));
      await _persist();

      if (_items.isNotEmpty) {
        _consecutiveFailures++;
        final backoff =
        Duration(seconds: (5 * _consecutiveFailures).clamp(5, 60));
        _backoffTimer?.cancel();
        _backoffTimer = Timer(backoff, flush);
      } else {
        _consecutiveFailures = 0;
      }
    } finally {
      _flushing = false;
    }
  }

  Future<bool> _send(Map<String, dynamic> item) async {
    try {
      final res = await http
          .post(
        Uri.parse(_endpoint),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'order_id': item['order_id'],
          'lat': item['lat'],
          'lng': item['lng'],
        }),
      )
          .timeout(const Duration(seconds: 15));
      if (res.statusCode < 200 || res.statusCode >= 300) return false;
      final decoded = jsonDecode(res.body);
      return decoded is Map && decoded['success'] == true;
    } catch (e) {
      return false;
    }
  }

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    if (_items.isEmpty) {
      await prefs.remove(_prefKey);
    } else {
      await prefs.setString(_prefKey, jsonEncode(_items));
    }
  }

  void dispose() {
    _backoffTimer?.cancel();
  }
}