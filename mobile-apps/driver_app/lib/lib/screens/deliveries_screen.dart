import 'dart:async';
import 'package:flutter/material.dart';
import '../services/api.dart';
import '../services/ble_service.dart';
import '../services/foreground_service.dart';
import 'sensor_history_screen.dart';
import 'profile_screen.dart';

class DeliveriesScreen extends StatefulWidget {
  final int driverId;
  final String driverName;
  DeliveriesScreen({required this.driverId, required this.driverName});

  @override
  _DeliveriesScreenState createState() => _DeliveriesScreenState();
}

class _DeliveriesScreenState extends State<DeliveriesScreen> {
  List<dynamic> deliveries = [];
  int? currentDeliveryId;

  final BleService ble = BleService();
  String bleStatus = 'Not connected';
  double? currentTemp;
  double? currentHum;
  bool tempAlert = false;

  Timer? _refreshTimer;
  Timer? _bannerTimer;
  List<dynamic> _previousDeliveries = [];
  String _bannerMessage = '';
  Color _bannerColor = Colors.orange;

  @override
  void initState() {
    super.initState();
    loadDeliveries();
    initBle();

    _refreshTimer = Timer.periodic(Duration(seconds: 10), (_) {
      if (mounted) loadDeliveries();
    });
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _bannerTimer?.cancel();
    super.dispose();
  }

  void initBle() {
    ble.scanAndConnect();
    ble.readingStream.listen((data) {
      if (data['type'] == 'live') {
        setState(() {
          currentTemp = data['temperature'];
          currentHum = data['humidity'];
          tempAlert = data['is_alert'] ?? false;
          bleStatus = 'Connected';
        });

        if (currentTemp != null && currentHum != null) {
          ForegroundService.updateTemp(currentTemp!, currentHum!);
        }

        if (currentDeliveryId != null && currentTemp != null && currentHum != null) {
          ApiService.saveSensorData(
            currentDeliveryId!,
            currentTemp!,
            currentHum!,
            tempAlert,
          );
        }
      }
    });
  }

  Future<void> loadDeliveries() async {
    final data = await ApiService.getDeliveries(widget.driverId);

    // Skip banner on first load
    if (_previousDeliveries.isEmpty) {
      _previousDeliveries = List.from(data);
      setState(() {
        deliveries = data;
        final active = data.firstWhere(
              (d) => d['delivery_status'] != 'delivered',
          orElse: () => null,
        );
        currentDeliveryId = active != null ? active['delivery_id'] : null;
      });
      return;
    }

    // Compare old vs new using order_id
    final oldIds = _previousDeliveries.map((d) => d['order_id']).toSet();
    final newIds = data.map((d) => d['order_id']).toSet();

    final removed = oldIds.difference(newIds);
    final added = newIds.difference(oldIds);

    if (removed.isNotEmpty) {
      _showBanner('Order #${removed.first} was reassigned', Colors.orange);
    } else if (added.isNotEmpty) {
      _showBanner('New order #${added.first} assigned to you', Colors.green);
    }

    _previousDeliveries = List.from(data);
    setState(() {
      deliveries = data;
      final active = data.firstWhere(
            (d) => d['delivery_status'] != 'delivered',
        orElse: () => null,
      );
      currentDeliveryId = active != null ? active['delivery_id'] : null;
    });
  }

  void _showBanner(String message, Color color) {
    setState(() {
      _bannerMessage = message;
      _bannerColor = color;
    });
    _bannerTimer?.cancel();
    _bannerTimer = Timer(Duration(seconds: 5), () {
      if (mounted) setState(() => _bannerMessage = '');
    });
  }

  void updateStatus(int deliveryId, String status) async {
    final result = await ApiService.updateDeliveryStatus(deliveryId, status);
    if (result['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Updated to $status')),
      );
      loadDeliveries();
    }
  }

  Color statusColor(String status) {
    switch (status) {
      case 'assigned': return Colors.orange;
      case 'picked_up': return Colors.blue;
      case 'in_transit': return Colors.purple;
      case 'delivered': return Colors.green;
      default: return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    Widget tempPanel = Container(
      margin: EdgeInsets.all(10),
      padding: EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: tempAlert ? Colors.red[100] : Colors.green[100],
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: tempAlert ? Colors.red : Colors.green),
      ),
      child: Column(
        children: [
          Text('IoT Monitor', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          SizedBox(height: 5),
          Text('Status: $bleStatus'),
          Text('Temp: ${currentTemp?.toStringAsFixed(1) ?? "--"} °C'),
          Text('Humidity: ${currentHum?.toStringAsFixed(1) ?? "--"} %'),
          if (tempAlert)
            Text('ALERT', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
        ],
      ),
    );

    Widget banner = _bannerMessage.isNotEmpty
        ? Container(
      width: double.infinity,
      padding: EdgeInsets.all(12),
      color: _bannerColor,
      child: Row(
        children: [
          Icon(
            _bannerColor == Colors.orange ? Icons.warning : Icons.check_circle,
            color: Colors.white,
          ),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              _bannerMessage,
              style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    )
        : SizedBox.shrink();

    return Scaffold(
      appBar: AppBar(
        title: Text('${widget.driverName} - Deliveries'),
        actions: [
          IconButton(
            icon: Icon(Icons.history),
            tooltip: 'Sensor History',
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => SensorHistoryScreen(driverId: widget.driverId),
                ),
              );
            },
          ),

          IconButton(
            icon: Icon(Icons.refresh),
            onPressed: loadDeliveries,
          ),

          IconButton(
            icon: Icon(Icons.person),
            tooltip: 'Profile',
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => ProfileScreen(
                    driverId: widget.driverId,
                    driverName: widget.driverName,
                  ),
                ),
              );
            },
          ),

        ],
      ),
      body: Column(
        children: [
          banner,
          tempPanel,
          Expanded(
            child: deliveries.isEmpty
                ? Center(child: Text('No deliveries assigned'))
                : ListView.builder(
              itemCount: deliveries.length,
              itemBuilder: (context, i) {
                final d = deliveries[i];
                final items = d['items'] as List? ?? [];

                return Card(
                  margin: EdgeInsets.all(10),
                  child: Padding(
                    padding: EdgeInsets.all(15),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Order #${d['order_id']}',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                        SizedBox(height: 5),
                        Text('Retailer: ${d['retailer_name'] ?? '-'}'),
                        Text('Address: ${d['retailer_address'] ?? '-'}'),
                        Text('Phone: ${d['retailer_phone'] ?? '-'}'),
                        SizedBox(height: 10),
                        Text('Products:', style: TextStyle(fontWeight: FontWeight.bold)),
                        SizedBox(height: 5),
                        ...items.map((it) => Text('• ${it['product_name']} x${it['quantity']}')),
                        SizedBox(height: 10),
                        Row(
                          children: [
                            Container(
                              padding: EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                              decoration: BoxDecoration(
                                color: statusColor(d['delivery_status']),
                                borderRadius: BorderRadius.circular(5),
                              ),
                              child: Text(d['delivery_status'],
                                  style: TextStyle(color: Colors.white)),
                            ),
                          ],
                        ),
                        SizedBox(height: 10),
                        Wrap(
                          spacing: 5,
                          children: [
                            if (d['delivery_status'] == 'assigned')
                              ElevatedButton(
                                onPressed: () => updateStatus(d['delivery_id'], 'picked_up'),
                                child: Text('Picked Up'),
                              ),
                            if (d['delivery_status'] == 'picked_up')
                              ElevatedButton(
                                onPressed: () => updateStatus(d['delivery_id'], 'in_transit'),
                                child: Text('In Transit'),
                              ),
                            if (d['delivery_status'] == 'in_transit')
                              ElevatedButton(
                                onPressed: () => updateStatus(d['delivery_id'], 'delivered'),
                                child: Text('Delivered'),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}