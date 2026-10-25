import 'package:flutter/foundation.dart';
import 'package:flutter_foreground_task/flutter_foreground_task.dart';

class ForegroundService {
  static bool _initialized = false;

  static void init() {
    if (_initialized) return;

    FlutterForegroundTask.init(
      androidNotificationOptions: AndroidNotificationOptions(
        channelId: 'kimfresh_driver',
        channelName: 'KimFresh Driver',
        channelDescription: 'Monitoring delivery',
        channelImportance: NotificationChannelImportance.LOW,
        priority: NotificationPriority.LOW,
      ),
      iosNotificationOptions: const IOSNotificationOptions(
        showNotification: true,
        playSound: false,
      ),
      foregroundTaskOptions: ForegroundTaskOptions(
        eventAction: ForegroundTaskEventAction.nothing(),
        autoRunOnBoot: false,
        allowWakeLock: true,
        allowWifiLock: false,
      ),
    );

    _initialized = true;
  }

  static Future<void> start() async {
    try {
      final permission = await FlutterForegroundTask.checkNotificationPermission();
      if (permission != NotificationPermission.granted) {
        await FlutterForegroundTask.requestNotificationPermission();
      }

      await FlutterForegroundTask.startService(
        notificationTitle: 'KimFresh Driver',
        notificationText: 'Monitoring delivery',
        callback: startCallback,
      );
    } catch (e, st) {
      debugPrint('ForegroundService.start failed: $e\n$st');
    }
  }

  static Future<void> updateTemp(double temp, double hum) async {
    try {
      await FlutterForegroundTask.updateService(
        notificationTitle: 'KimFresh Driver',
        notificationText:
        'Temp: ${temp.toStringAsFixed(1)}°C | Hum: ${hum.toStringAsFixed(1)}%',
      );
    } catch (e) {
      debugPrint('ForegroundService.updateTemp failed: $e');
    }
  }

  static Future<void> stop() async {
    try {
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
  @override
  Future<void> onStart(DateTime timestamp, TaskStarter starter) async {
    // 🔥 REQUIRED: Tell Flutter the task started successfully
    FlutterForegroundTask.sendDataToTask({});
  }

  @override
  void onRepeatEvent(DateTime timestamp) {
    // 🔥 REQUIRED: If autoRunOnBoot is false, this keeps the task alive
  }

  @override
  Future<void> onDestroy(DateTime timestamp) async {}

  @override
  void onReceiveData(Object data) {}

  @override
  void onNotificationButtonPressed(String id) {}

  @override
  void onNotificationPressed() {
    FlutterForegroundTask.launchApp();
  }

  @override
  void onNotificationDismissed() {}
}