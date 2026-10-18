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
      iosNotificationOptions: IOSNotificationOptions(),
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
    init();

    await FlutterForegroundTask.startService(
      notificationTitle: 'KimFresh Driver',
      notificationText: 'Monitoring delivery',
      callback: startCallback,
    );
  }

  static Future<void> updateTemp(double temp, double hum) async {
    await FlutterForegroundTask.updateService(
      notificationTitle: 'KimFresh Driver',
      notificationText: 'Temp: ${temp.toStringAsFixed(1)}°C | Hum: ${hum.toStringAsFixed(1)}%',
    );
  }

  static Future<void> stop() async {
    await FlutterForegroundTask.stopService();
  }
}

@pragma('vm:entry-point')
void startCallback() {
  FlutterForegroundTask.setTaskHandler(MyTaskHandler());
}

class MyTaskHandler extends TaskHandler {
  @override
  Future<void> onStart(DateTime timestamp, TaskStarter starter) async {}

  @override
  void onRepeatEvent(DateTime timestamp) {}

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