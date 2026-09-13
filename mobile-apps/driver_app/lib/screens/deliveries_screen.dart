import 'package:flutter/material.dart';
import '../services/api.dart';
import '../services/ble_service.dart';

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
  List<Map<String, dynamic>> storedReadings = [];

  @override
  void initState() {
    super.initState();
    loadDeliveries();
    initBle();
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

print('=== LIVE: delivery=$currentDeliveryId temp=$currentTemp hum=$currentHum ===');
if (currentDeliveryId != null && currentTemp != null && currentHum != null) {
  print('=== SAVING SENSOR ===');
  ApiService.saveSensorData(
    currentDeliveryId!,
    currentTemp!,
    currentHum!,
    tempAlert,
  ).then((_) {
    print('=== SAVED SUCCESSFULLY ===');
  }).catchError((e) {
    print('=== SAVE ERROR: $e ===');
  });
} else {
  print('=== SKIP SAVE: delivery=$currentDeliveryId ===');
}
      } else if (data['type'] == 'stored') {
        final readings = List<Map<String, dynamic>>.from(data['readings']);
        setState(() {
          storedReadings = readings;
        });

        if (currentDeliveryId != null) {
          for (var r in readings) {
            if (r['temperature'] != null && r['humidity'] != null) {
              ApiService.saveSensorData(
                currentDeliveryId!,
                r['temperature'],
                r['humidity'],
                r['is_alert'] ?? false,
              );
            }
          }
        }
      }
    });
  }

  void loadDeliveries() async {
    final data = await ApiService.getDeliveries(widget.driverId);
    setState(() {
      deliveries = data;
      final active = data.firstWhere(
        (d) => d['delivery_status'] != 'delivered',
        orElse: () => null,
      );
      if (active != null) {
        currentDeliveryId = active['delivery_id'];
      }
    });
  }

  void updateStatus(int deliveryId, String status) async {
    final result = await ApiService.updateDeliveryStatus(deliveryId, status);
    if (result['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Updated to $status')));
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
            Text('⚠️ ALERT!', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
          if (storedReadings.isNotEmpty) ...[
            SizedBox(height: 10),
            Divider(),
            Text('Stored Readings (offline)', style: TextStyle(fontWeight: FontWeight.bold)),
            ...storedReadings.map((r) => Text(
              'T:${r['temperature']} °C | H:${r['humidity']} %${r['is_alert'] == true ? ' ⚠️' : ''}',
              style: TextStyle(fontSize: 12),
            )).toList(),
          ],
        ],
      ),
    );

    return Scaffold(
      appBar: AppBar(
        title: Text('${widget.driverName} - Deliveries'),
        actions: [
          IconButton(icon: Icon(Icons.refresh), onPressed: loadDeliveries),
        ],
      ),
      body: Column(
        children: [
          tempPanel,
          Expanded(
            child: deliveries.isEmpty
                ? Center(child: Text('No deliveries assigned'))
                : ListView.builder(
                    itemCount: deliveries.length,
                    itemBuilder: (context, i) {
                      final d = deliveries[i];
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
                              SizedBox(height: 5),
                              Text('Product: ${d['product_name'] ?? '-'} x ${d['quantity'] ?? '-'}'),
                              SizedBox(height: 10),
                              Row(
                                children: [
                                  Container(
                                    padding: EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                                    decoration: BoxDecoration(
                                      color: statusColor(d['delivery_status']),
                                      borderRadius: BorderRadius.circular(5),
                                    ),
                                    child: Text(d['delivery_status'], style: TextStyle(color: Colors.white)),
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