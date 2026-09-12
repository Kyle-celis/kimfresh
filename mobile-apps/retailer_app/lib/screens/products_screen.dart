import 'package:flutter/material.dart';
import '../services/api.dart';
import 'orders_screen.dart';

class ProductsScreen extends StatefulWidget {
  final int retailerId;
  final String retailerName;
  ProductsScreen({required this.retailerId, required this.retailerName});

  @override
  _ProductsScreenState createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  List<dynamic> products = [];
  Map<int, int> cart = {};

  @override
  void initState() {
    super.initState();
    loadProducts();
  }

  void loadProducts() async {
    final data = await ApiService.getProducts();
    setState(() => products = data);
  }

  void addToCart(int productId) {
    setState(() {
      cart[productId] = (cart[productId] ?? 0) + 1;
    });
  }

  void placeOrder() async {
    if (cart.isEmpty) return;
    final items = cart.entries.map((e) => {'product_id': e.key, 'quantity': e.value}).toList();
    final result = await ApiService.placeOrder(widget.retailerId, items);
    if (result['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Order placed!')));
      setState(() => cart.clear());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Welcome, ${widget.retailerName}'),
        actions: [
          IconButton(
            icon: Icon(Icons.receipt),
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(
                builder: (_) => OrdersScreen(retailerId: widget.retailerId),
              ));
            },
          ),
        ],
      ),
      body: ListView.builder(
        itemCount: products.length,
        itemBuilder: (context, i) {
          final p = products[i];
          return ListTile(
            title: Text(p['name']),
            subtitle: Text('₱${p['unit_price']} - ${p['sku']}'),
            trailing: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text('Qty: ${cart[p['product_id']] ?? 0}'),
                IconButton(icon: Icon(Icons.add), onPressed: () => addToCart(p['product_id'])),
              ],
            ),
          );
        },
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: placeOrder,
        child: Icon(Icons.shopping_cart),
      ),
    );
  }
}