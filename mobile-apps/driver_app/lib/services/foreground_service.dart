import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter_foreground_task/flutter_foreground_task.dart';
import 'package:geolocator/geolocator.dart';
import 'gps_queue.dart';

class ForegroundService {
  static bool _initialized = false;

  // 👇 ADDED THIS VARIABLE 👇
  static bool _isPaused = false;

  // Throttle state for the notification temp/hum text so it doesn't
  // flicker in the tray every 5s.
  static double? _lastTemp;
  static bool _lastAlert = false;

  static void init() {
    if (_initialized) return;

    FlutterForegroundTask.init(
      androidNotificationOptions: AndroidNotificationOptions(
        channelId: 'kimfresh_driver',
        channelName: 'KimFresh Driver',
        channelDescription: 'Tracking delivery location and cold-chain',
        channelImportance: NotificationChannelImportance.LOW,
        priority: NotificationPriority.LOW,
      ),
      iosNotificationOptions: const IOSNotificationOptions(
        showNotification: true,
        playSound: false,
      ),
      foregroundTaskOptions: ForegroundTaskOptions(
        // Fires onRepeatEvent every 10s — this is where GPS ping happens.
        eventAction: ForegroundTaskEventAction.repeat(10000),
        autoRunOnBoot: false,
        allowWakeLock: true,
        allowWifiLock: false,
      ),
    );

    _initialized = true;
  }

  static Future<void> start() async {
    try {
      final permission =
          await FlutterForegroundTask.checkNotificationPermission();
      if (permission != NotificationPermission.granted) {
        await FlutterForegroundTask.requestNotificationPermission();
      }

      if (await FlutterForegroundTask.isRunningService) return;

      await FlutterForegroundTask.startService(
        notificationTitle: 'KimFresh Driver',
        notificationText: 'Tracking delivery…',
        callback: startCallback,
      );
    } catch (e, st) {
      debugPrint('ForegroundService.start failed: $e\n$st');
    }
  }

  /// Tell the service which order to track (or 0 to stop GPS pings).
  static void setActiveOrder(int orderId) {
    FlutterForegroundTask.sendDataToTask({'active_order_id': orderId});
  }

  // 👇 ADDED THESE TWO FUNCTIONS 👇
  static void pauseTracking() {
    _isPaused = true;
    debugPrint('ForegroundService: Tracking PAUSED (Map is open)');
  }

  static void resumeTracking() {
    _isPaused = false;
    debugPrint('ForegroundService: Tracking RESUMED');
  }

  static Future<void> updateTemp(
    double temp,
    double hum, {
    bool alert = false,
  }) async {
    final changed =
        _lastTemp == null ||
        (temp - _lastTemp!).abs() >= 0.5 ||
        alert != _lastAlert;
    if (!changed) return;

    _lastTemp = temp;
    _lastAlert = alert;

    try {
      await FlutterForegroundTask.updateService(
        notificationTitle: 'KimFresh Driver',
        notificationText:
            'Temp: ${temp.toStringAsFixed(1)}°C | Hum: ${hum.toStringAsFixed(1)}%${alert ? " ⚠️" : ""}',
      );
    } catch (e) {
      debugPrint('ForegroundService.updateTemp failed: $e');
    }
  }

  static Future<void> stop() async {
    try {
      setActiveOrder(0); // tell handler to stop pinging
      // Give the service isolate a moment to see order=0 and finish
      // any in-flight flush before Android tears it down.
      await Future.delayed(const Duration(milliseconds: 300));
      await FlutterForegroundTask.stopService();
    } catch (e) {
      debugPrint('ForegroundService.stop failed: $e');
    }
  }
}

@pragma('vm:entry-point')
void startCallback() {
  FlutterForegroundTask.setTaskHandler(MyTaskHandler());
}

class MyTaskHandler extends TaskHandler {
  final GpsQueue _queue = GpsQueue();
  int _activeOrderId = 0;
  bool _sendingGps = false;
  bool _started = false;

  @override
  Future<void> onStart(DateTime timestamp, TaskStarter starter) async {
    _started = true;
    try {
      await _queue.init();
      debugPrint('FG onStart: queue init success');
    } catch (e, st) {
      debugPrint('FG onStart: queue init FAILED $e\n$st');
    }
  }

  @override
  void onRepeatEvent(DateTime timestamp) {
    if (!_started || _activeOrderId <= 0) return;

    // 👇 ADDED THIS CHECK 👇
    if (ForegroundService._isPaused) {
      return; // Skip this 10-second ping because the map is open
    }

    if (_sendingGps) return;
    unawaited(_sendGpsPing());
    // Also flush any pending items from previous failures
    unawaited(_queue.flush());
  }

  Future<void> _sendGpsPing() async {
    _sendingGps = true;
    try {
      if (!await Geolocator.isLocationServiceEnabled()) return;

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) return;
      }
      if (permission == LocationPermission.deniedForever) return;

      final pos = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      ).timeout(const Duration(seconds: 8));

      await _queue.enqueue({
        'order_id': _activeOrderId,
        'lat': pos.latitude,
        'lng': pos.longitude,
        'dedup_key':
            'gps:$_activeOrderId:${DateTime.now().millisecondsSinceEpoch ~/ 10000}',
      });
    } catch (e) {
      debugPrint('FG GPS ping failed: $e');
    } finally {
      _sendingGps = false;
    }
  }

  @override
  Future<void> onDestroy(DateTime timestamp) async {
    try {
      await _queue.flush(); // best-effort drain before death
    } catch (e) {
      debugPrint('FG onDestroy flush failed: $e');
    }
    _queue.dispose();
  }

  @override
  void onReceiveData(Object data) {
    if (data is Map && data.containsKey('active_order_id')) {
      final id = data['active_order_id'];
      _activeOrderId = id is int
          ? id
          : int.tryParse(id?.toString() ?? '0') ?? 0;
      debugPrint('FG handler: active_order_id = $_activeOrderId');
    }
  }

  @override
  void onNotificationButtonPressed(String id) {}

  @override
  void onNotificationPressed() {
    FlutterForegroundTask.launchApp();
  }

  @override
  void onNotificationDismissed() {}
}
