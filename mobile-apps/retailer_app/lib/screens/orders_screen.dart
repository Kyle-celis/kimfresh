import 'package:flutter/material.dart';
import '../services/api.dart';

class OrdersScreen extends StatefulWidget {
  final int retailerId;
  OrdersScreen({required this.retailerId});

  @override
  _OrdersScreenState createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  List<dynamic> orders = [];

  @override
  void initState() {
    super.initState();
    loadOrders();
  }

  void loadOrders() async {
    final data = await ApiService.getOrders(widget.retailerId);
    setState(() => orders = data);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('My Orders')),
      body: ListView.builder(
        itemCount: orders.length,
        itemBuilder: (context, i) {
          final o = orders[i];
          return ListTile(
            title: Text('Order #${o['order_id']} - ${o['order_status']}'),
subtitle: Text('Customer ID: ${o['customer_code'] ?? '-'}\nTotal: ₱${o['total_amount']} | ${o['product_name'] ?? ''}'),
isThreeLine: true,
          );
        },
      ),
    );
  }
}