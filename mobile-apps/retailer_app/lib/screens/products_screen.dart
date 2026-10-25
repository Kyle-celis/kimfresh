import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:geolocator/geolocator.dart';
import '../services/api.dart';
import '../theme/colors.dart';
import 'orders_screen.dart';
import 'profile_screen.dart';

class _T {
  static const brand = AppColors.brand;
  static const bg = AppColors.background;
  static const surface = AppColors.surface;
  static const border = AppColors.border;
  static const text = AppColors.text;
  static const muted = AppColors.muted;
  static const success = AppColors.success;
  static const error = AppColors.error;
  static const warning = AppColors.warning;
  static const rSm = AppColors.rSm;
  static const rMd = AppColors.rMd;
  static const rLg = AppColors.rLg;
  static const sXs = AppColors.sXs;
  static const sSm = AppColors.sSm;
  static const sMd = AppColors.sMd;
  static const sLg = AppColors.sLg;
}

class ProductsScreen extends StatefulWidget {
  final int retailerId;
  final String retailerName;
  ProductsScreen({
    super.key,
    required this.retailerId,
    required this.retailerName,
  });

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  List<dynamic> products = [];
  Map<int, int> cart = {};
  bool _loading = true;
  bool _placing = false;
  String _query = '';

  String _storeWarning = '';
  String _statusType = 'normal';

  @override
  void initState() {
    super.initState();
    loadProducts();
    _checkStoreStatus();
  }

  Future<void> loadProducts() async {
    setState(() => _loading = true);
    final data = await ApiService.getProducts();
    if (!mounted) return;
    setState(() {
      products = data;
      cart.removeWhere((id, _) {
        final p = products.firstWhere(
              (x) => x['product_id'] == id,
          orElse: () => null,
        );
        return p != null && _isOutOfStock(p);
      });
      _loading = false;
    });
  }

  Future<void> _checkStoreStatus() async {
    final status = await ApiService.getStoreStatus();
    if (mounted) {
      setState(() {
        _statusType = status['status_type'] ?? 'normal';
        _storeWarning = status['message'] ?? '';
      });
    }
  }

  int? _getStock(dynamic p) {
    final raw = p['stock_quantity'] ?? p['stock'] ?? p['quantity'];
    if (raw == null) return null;
    if (raw is int) return raw;
    return int.tryParse(raw.toString());
  }

  bool _isOutOfStock(dynamic p) {
    final s = _getStock(p);
    return s != null && s <= 0;
  }

  List<dynamic> get _visibleProducts {
    if (_query.trim().isEmpty) return products;
    final q = _query.toLowerCase();
    return products.where((p) {
      final name = (p['name'] ?? '').toString().toLowerCase();
      final sku = (p['sku'] ?? '').toString().toLowerCase();
      return name.contains(q) || sku.contains(q);
    }).toList();
  }

  int get _cartCount => cart.values.fold(0, (s, q) => s + q);

  double get _cartTotal {
    double total = 0;
    cart.forEach((id, qty) {
      final p = products.firstWhere(
            (x) => x['product_id'] == id,
        orElse: () => null,
      );
      if (p != null) {
        final price = double.tryParse(p['unit_price'].toString()) ?? 0;
        total += price * qty;
      }
    });
    return total;
  }

  void addToCart(int id) {
    final p = products.firstWhere(
          (x) => x['product_id'] == id,
      orElse: () => null,
    );
    if (p == null || _isOutOfStock(p)) return;

    final stock = _getStock(p);
    final current = cart[id] ?? 0;
    if (stock != null && current >= stock) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: _T.warning,
          content: Text('Only $stock available',
              style: const TextStyle(color: Colors.white)),
        ),
      );
      return;
    }
    setState(() => cart[id] = current + 1);
  }

  void removeFromCart(int id) {
    setState(() {
      final cur = cart[id] ?? 0;
      if (cur <= 1) {
        // If it's 1, the minus button just resets it to 1 (doesn't delete).
        // Deletion happens via Trash can in Review or Clear Cart.
        return;
      } else {
        cart[id] = cur - 1;
      }
    });
  }

  void _setQuantity(int id, int newQty, int? maxStock) {
    if (newQty <= 0) {
      setState(() => cart.remove(id));
      return;
    }
    if (maxStock != null && newQty > maxStock) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: _T.warning,
          content: Text('Only $maxStock available',
              style: const TextStyle(color: Colors.white)),
        ),
      );
      newQty = maxStock;
    }
    setState(() => cart[id] = newQty);
  }

  void _removeItem(int id) {
    setState(() => cart.remove(id));
  }

  void _clearCart() {
    setState(() => cart.clear());
  }

  Future<void> placeOrder() async {
    if (cart.isEmpty || _placing) return;
    setState(() => _placing = true);

    // 1. Get the GPS coordinates BEFORE placing the order
    double? lat;
    double? lng;
    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (serviceEnabled) {
        LocationPermission permission = await Geolocator.checkPermission();
        if (permission == LocationPermission.denied) {
          permission = await Geolocator.requestPermission();
        }
        if (permission == LocationPermission.whileInUse || permission == LocationPermission.always) {
          Position pos = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.high,
          );
          lat = pos.latitude;
          lng = pos.longitude;
        }
      }
    } catch (e) {
      // If GPS fails, we just won't save the location, but we still let them order
      print('GPS Error: $e');
    }

    // 2. Place the order in Flask
    final items = cart.entries
        .map((e) => {'product_id': e.key, 'quantity': e.value})
        .toList();
    final result = await ApiService.placeOrder(widget.retailerId, items);

    if (!mounted) return;
    setState(() => _placing = false);

    final ok = result['success'] == true;
    String message = result['message'] ?? (ok ? 'Order placed successfully' : 'Failed to place order');

    Color bgColor = ok ? _T.success : _T.error;
    if (ok && message.contains('close in')) {
      bgColor = _T.warning;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        backgroundColor: bgColor,
        content: Text(message, style: const TextStyle(color: Colors.white)),
        duration: const Duration(seconds: 4),
      ),
    );

    if (ok) {
      // 3. If the order was successful, save the location to the PHP database
      final newOrderId = result['order_id'];
      if (newOrderId != null && lat != null && lng != null) {
        await ApiService.saveRetailerLocation(newOrderId, lat, lng);
      }

      setState(() => cart.clear());
      _checkStoreStatus();
    }
  }

  Future<void> _confirmOrder() async {
    if (cart.isEmpty) return;
    final confirmed = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: _T.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => _ReviewSheet(
        cart: cart,
        products: products,
        onRemoveItem: _removeItem,
        // Removed 'total' and 'count' here!
      ),
    );
    if (confirmed == true) await placeOrder();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _T.bg,
      appBar: AppBar(
        backgroundColor: _T.bg,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Products', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w600)),
            Text('KimFresh · ${widget.retailerName}', style: const TextStyle(fontSize: 12, color: _T.muted)),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.receipt_long_outlined),
            onPressed: () => Navigator.push(context, MaterialPageRoute(
              builder: (_) => OrdersScreen(retailerId: widget.retailerId),
            )),
          ),
          IconButton(
            icon: const Icon(Icons.person_outline_rounded),
            onPressed: () => Navigator.push(context, MaterialPageRoute(
              builder: (_) => ProfileScreen(
                retailerId: widget.retailerId,
                retailerName: widget.retailerName,
              ),
            )),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: _T.brand))
          : Column(
        children: [
          if (_storeWarning.isNotEmpty)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              color: _statusType == 'closed' ? _T.error : _T.warning,
              child: Row(
                children: [
                  Icon(
                    _statusType == 'closed'
                        ? Icons.lock_outline
                        : (_statusType == 'opening_soon' ? Icons.lock_clock : Icons.warning_amber_rounded),
                    color: Colors.white,
                    size: 20,
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      _storeWarning,
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                  ),
                ],
              ),
            ),
          _searchBar(),
          Expanded(
            child: products.isEmpty
                ? _emptyProducts()
                : RefreshIndicator(
              color: _T.brand,
              onRefresh: loadProducts,
              child: _productList(),
            ),
          ),
        ],
      ),
      bottomNavigationBar: cart.isNotEmpty
          ? _CartBar(
        count: _cartCount,
        total: _cartTotal,
        placing: _placing,
        onReview: _confirmOrder,
        onClear: _clearCart,
      )
          : null,
    );
  }

  Widget _searchBar() {
    return Padding(
      padding: const EdgeInsets.all(_T.sMd),
      child: TextField(
        onChanged: (v) => setState(() => _query = v),
        decoration: InputDecoration(
          hintText: 'Search products',
          prefixIcon: const Icon(Icons.search_rounded, size: 20),
          fillColor: _T.surface,
        ),
      ),
    );
  }

  Widget _emptyProducts() {
    return const Center(child: Text('No products available. Pull to refresh.'));
  }

  Widget _productList() {
    final items = _visibleProducts;
    if (items.isEmpty) return const Center(child: Text('No matches found.'));

    return ListView.builder(
      padding: const EdgeInsets.only(bottom: 96),
      itemCount: items.length,
      itemBuilder: (_, i) {
        final p = items[i];
        return Padding(
          padding: const EdgeInsets.symmetric(horizontal: _T.sMd, vertical: 4),
          child: _ProductCard(
            product: p,
            qty: cart[p['product_id']] ?? 0,
            outOfStock: _isOutOfStock(p),
            stock: _getStock(p),
            onAdd: addToCart,
            onRemove: removeFromCart,
            onSetQuantity: _setQuantity,
          ),
        );
      },
    );
  }
}

// ============================================================
// PRODUCT CARD
// ============================================================
class _ProductCard extends StatelessWidget {
  final dynamic product;
  final int qty;
  final bool outOfStock;
  final int? stock;
  final void Function(int) onAdd;
  final void Function(int) onRemove;
  final void Function(int, int, int?) onSetQuantity;

  const _ProductCard({
    required this.product,
    required this.qty,
    required this.outOfStock,
    required this.stock,
    required this.onAdd,
    required this.onRemove,
    required this.onSetQuantity,
  });

  @override
  Widget build(BuildContext context) {
    final id = product['product_id'];
    final inCart = qty > 0;
    final price = product['unit_price']?.toString() ?? '0';

    return Container(
      padding: const EdgeInsets.all(_T.sMd),
      decoration: BoxDecoration(
        color: _T.surface,
        borderRadius: BorderRadius.circular(_T.rLg),
        border: Border.all(
          color: outOfStock ? _T.border : inCart ? _T.brand.withOpacity(0.4) : _T.border,
        ),
      ),
      child: Row(
        children: [
          Container(
            height: 48,
            width: 48,
            decoration: BoxDecoration(
              color: outOfStock ? _T.border : _T.brand.withOpacity(0.06),
              borderRadius: BorderRadius.circular(_T.rMd),
            ),
            child: Icon(Icons.local_mall_outlined, color: outOfStock ? _T.muted : _T.brand),
          ),
          const SizedBox(width: _T.sMd),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(product['name'] ?? 'Unnamed', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
                Text('SKU: ${product['sku'] ?? '-'}', style: const TextStyle(fontSize: 12, color: _T.muted)),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Text('₱$price', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: outOfStock ? _T.muted : _T.brand)),
                    const SizedBox(width: 8),
                    if (!outOfStock && stock != null)
                      Text(
                        'Stock: $stock',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: _T.muted),
                      ),
                  ],
                ),
              ],
            ),
          ),
          if (outOfStock)
            const Text('Unavailable', style: TextStyle(color: _T.muted, fontSize: 12))
          else if (inCart)
            _Stepper(
              qty: qty,
              maxReached: stock != null && qty >= stock!,
              onMinus: () => onRemove(id),
              onPlus: () => onAdd(id),
              onSetQuantity: (newQty) => onSetQuantity(id, newQty, stock),
            )
          else
            ElevatedButton(
              onPressed: () => onAdd(id),
              style: ElevatedButton.styleFrom(backgroundColor: _T.brand),
              child: const Text('Add'),
            ),
        ],
      ),
    );
  }
}

// ============================================================
// STEPPER (No Trashcan here anymore, just +/-)
// ============================================================
class _Stepper extends StatefulWidget {
  final int qty;
  final bool maxReached;
  final VoidCallback onMinus;
  final VoidCallback onPlus;
  final void Function(int) onSetQuantity;

  const _Stepper({
    required this.qty,
    required this.maxReached,
    required this.onMinus,
    required this.onPlus,
    required this.onSetQuantity,
  });

  @override
  State<_Stepper> createState() => _StepperState();
}

class _StepperState extends State<_Stepper> {
  late TextEditingController _controller;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: widget.qty.toString());
  }

  @override
  void didUpdateWidget(covariant _Stepper oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.qty != widget.qty) {
      _controller.text = widget.qty.toString();
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _submitValue(String value) {
    final parsed = int.tryParse(value);
    if (parsed != null && parsed > 0) {
      widget.onSetQuantity(parsed);
    } else {
      _controller.text = widget.qty.toString();
      FocusScope.of(context).unfocus();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        border: Border.all(color: _T.border),
        borderRadius: BorderRadius.circular(_T.rSm),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Minus button (disabled at 1, does not delete)
          InkWell(
            onTap: widget.qty > 1 ? widget.onMinus : null,
            child: Padding(
              padding: const EdgeInsets.all(8),
              child: Icon(
                Icons.remove_rounded,
                size: 16,
                color: widget.qty > 1 ? _T.text : _T.muted,
              ),
            ),
          ),
          SizedBox(
            width: 40,
            child: TextFormField(
              controller: _controller,
              textAlign: TextAlign.center,
              keyboardType: TextInputType.number,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
              decoration: const InputDecoration(border: InputBorder.none, isDense: true, contentPadding: EdgeInsets.zero),
              onFieldSubmitted: _submitValue,
              onTapOutside: (_) {
                FocusScope.of(context).unfocus();
                _submitValue(_controller.text);
              },
            ),
          ),
          InkWell(
            onTap: widget.maxReached ? null : widget.onPlus,
            child: Padding(
              padding: const EdgeInsets.all(8),
              child: Icon(Icons.add_rounded, size: 16, color: widget.maxReached ? _T.muted : _T.text),
            ),
          ),
        ],
      ),
    );
  }
}

// ============================================================
// CART BAR (With Trash Icon)
// ============================================================
class _CartBar extends StatelessWidget {
  final int count;
  final double total;
  final bool placing;
  final VoidCallback onReview;
  final VoidCallback onClear;

  const _CartBar({
    required this.count,
    required this.total,
    required this.placing,
    required this.onReview,
    required this.onClear,
  });

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Container(
        margin: const EdgeInsets.all(16),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(color: _T.brand, borderRadius: BorderRadius.circular(12)),
        child: Row(
          children: [
            // TRASH ICON TO CLEAR CART
            IconButton(
              icon: const Icon(Icons.delete_sweep_rounded, color: Colors.white),
              tooltip: 'Clear Cart',
              onPressed: onClear,
            ),
            Text('$count items', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
            const Spacer(),
            Text('₱${total.toStringAsFixed(2)}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(width: 16),
            ElevatedButton(
              onPressed: placing ? null : onReview,
              style: ElevatedButton.styleFrom(backgroundColor: Colors.white, foregroundColor: _T.brand),
              child: const Text('Review'),
            ),
          ],
        ),
      ),
    );
  }
}

// ============================================================
// REVIEW SHEET (With Trash Icon per product)
// ============================================================
class _ReviewSheet extends StatelessWidget {
  final Map<int, int> cart;
  final List<dynamic> products;
  final void Function(int) onRemoveItem;

  const _ReviewSheet({
    required this.cart,
    required this.products,
    required this.onRemoveItem,
  });

  @override
  Widget build(BuildContext context) {
    return StatefulBuilder(
      builder: (context, setSheetState) {
        // ✅ CALCULATE TOTAL & COUNT HERE SO IT UPDATES LIVE
        int liveCount = 0;
        double liveTotal = 0;

        cart.forEach((id, qty) {
          final p = products.firstWhere(
                (x) => x['product_id'] == id,
            orElse: () => null,
          );
          if (p != null) {
            final price = double.tryParse(p['unit_price'].toString()) ?? 0;
            liveCount += qty;
            liveTotal += price * qty;
          }
        });

        return Padding(
          padding: EdgeInsets.only(
            left: 24,
            right: 24,
            top: 16,
            bottom: MediaQuery.of(context).viewInsets.bottom + 24,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(height: 4, width: 36, decoration: BoxDecoration(color: _T.border, borderRadius: BorderRadius.circular(4))),
              const SizedBox(height: 16),
              const Text('Review Order', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 4),
              Text('$liveCount items', style: const TextStyle(color: _T.muted)),
              const SizedBox(height: 16),

              // LIST OF ITEMS
              ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 300),
                child: ListView.separated(
                  shrinkWrap: true,
                  itemCount: cart.length,
                  separatorBuilder: (_, __) => const Divider(),
                  itemBuilder: (_, i) {
                    final entry = cart.entries.elementAt(i);
                    final p = products.firstWhere(
                          (x) => x['product_id'] == entry.key,
                      orElse: () => null,
                    );
                    final price = double.tryParse(p?['unit_price'].toString() ?? '0') ?? 0;

                    return ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(p?['name'] ?? 'Product'),
                      subtitle: Text('₱${price.toStringAsFixed(2)} × ${entry.value}'),
                      trailing: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text('₱${(price * entry.value).toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.bold)),
                          const SizedBox(width: 8),
                          // TRASH ICON PER PRODUCT
                          IconButton(
                            icon: const Icon(Icons.delete_outline_rounded, color: _T.error),
                            onPressed: () {
                              onRemoveItem(entry.key); // Call parent to remove
                              setSheetState(() {}); // Rebuilds UI & recalculates liveTotal
                            },
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),

              const Divider(),
              Row(
                children: [
                  const Text('Total', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  const Spacer(),
                  Text('₱${liveTotal.toStringAsFixed(2)}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: _T.brand)),
                ],
              ),
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Cancel'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton(
                      // Also check if liveCount is 0 to disable the button
                      onPressed: liveCount == 0 ? null : () => Navigator.pop(context, true),
                      style: ElevatedButton.styleFrom(backgroundColor: _T.brand),
                      child: const Text('Place Order'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }
}