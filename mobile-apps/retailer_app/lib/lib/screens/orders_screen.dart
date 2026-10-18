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
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    loadOrders();
  }

  Future<void> loadOrders() async {
    setState(() => _loading = true);
    final data = await ApiService.getOrders(widget.retailerId);
    if (!mounted) return;
    setState(() {
      orders = data;
      _loading = false;
    });
  }

  Color _statusColor(String? status) {
    switch (status) {
      case 'pending':
        return Colors.orange;
      case 'in_transit':
        return Colors.blue;
      case 'delivered':
        return Colors.green;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('My Orders')),
      body: _loading
          ? Center(child: CircularProgressIndicator())
          : orders.isEmpty
          ? Center(
        child: Text(
          'No orders yet.\nPull down to refresh.',
          textAlign: TextAlign.center,
        ),
      )
          : RefreshIndicator(
        onRefresh: loadOrders,
        child: ListView.builder(
          itemCount: orders.length,
          itemBuilder: (context, i) {
            final o = orders[i];
            final status = o['order_status'] ?? 'pending';
            return Card(
              margin: EdgeInsets.symmetric(
                horizontal: 12,
                vertical: 6,
              ),
              child: ListTile(
                title: Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Order #${o['order_id']}',
                        style: TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ),
                    Container(
                      padding: EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: _statusColor(status),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        status.toString().toUpperCase(),
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),
                subtitle: Padding(
                  padding: EdgeInsets.only(top: 6),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (o['product_name'] != null)
                        Text('${o['product_name']}'),
                      Text(
                        'Total: ₱${o['total_amount'] ?? '0'}',
                        style: TextStyle(
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      if (o['order_date'] != null)
                        Text(
                          'Placed: ${o['order_date']}',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey[600],
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}