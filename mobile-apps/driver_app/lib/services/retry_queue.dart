import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Persisted FIFO queue for API calls that must not be lost on a dead spot.
/// Uses `dedup_key` to prevent the same logical event from being retried twice.
class RetryQueue {
  final String name;
  final Future<bool> Function(Map<String, dynamic> item) send;
  final int maxAttempts;

  final List<Map<String, dynamic>> _items = [];
  bool _flushing = false;
  Timer? _backoffTimer;
  int _consecutiveFailures = 0;

  RetryQueue({
    required this.name,
    required this.send,
    this.maxAttempts = 10,
  });

  String get _prefKey => 'retry_queue_$name';
  int get pendingCount => _items.length;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_prefKey);
    if (raw != null) {
      try {
        final decoded = jsonDecode(raw) as List;
        _items.addAll(decoded.cast<Map<String, dynamic>>());
      } catch (e) {
        debugPrint('RetryQueue[$name] corrupt store, dropping: $e');
      }
    }
    if (_items.isNotEmpty) {
      debugPrint('RetryQueue[$name] restored ${_items.length} pending items');
      unawaited(flush());
    }
  }

  Future<void> enqueue(Map<String, dynamic> item) async {
    final key = item['dedup_key']?.toString();
    if (key != null && _items.any((e) => e['dedup_key'] == key)) {
      return;
    }
    _items.add({
      ...item,
      'attempts': 0,
      'queued_at': DateTime.now().toIso8601String(),
    });
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
          final ok = await send(item);
          if (ok) {
            succeeded.add(item);
          } else {
            item['attempts'] = ((item['attempts'] as int?) ?? 0) + 1;
            if ((item['attempts'] as int) >= maxAttempts) {
              debugPrint('RetryQueue[$name] dropping after $maxAttempts attempts: $item');
              succeeded.add(item);
            }
          }
        } catch (e) {
          item['attempts'] = ((item['attempts'] as int?) ?? 0) + 1;
          debugPrint('RetryQueue[$name] send threw: $e');
        }
      }

      _items.removeWhere((e) => succeeded.contains(e));
      await _persist();

      if (_items.isNotEmpty) {
        _consecutiveFailures++;
        final backoff = Duration(seconds: (5 * _consecutiveFailures).clamp(5, 60));
        debugPrint('RetryQueue[$name] ${_items.length} remain, retry in ${backoff.inSeconds}s');
        _backoffTimer?.cancel();
        _backoffTimer = Timer(backoff, flush);
      } else {
        _consecutiveFailures = 0;
      }
    } finally {
      _flushing = false;
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
    _backoffTimer = null;
  }
}