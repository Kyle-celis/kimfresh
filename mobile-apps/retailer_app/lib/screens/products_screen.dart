import 'dart:async';
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
  String _openTime = '';
  String _closeTime = '';
  String _statusType = 'normal';
  bool _showBanner = false;
  bool? _lastStoreOpenStatus;
  bool _hasShownStartupPopup = false;
  Timer? _statusTimer;
  Timer? _bannerTimer;

  @override
  void initState() {
    super.initState();
    loadProducts();
    _checkStoreStatus();

    _statusTimer = Timer.periodic(const Duration(seconds: 15), (_) {
      _checkStoreStatus();
    });
  }

  @override
  void dispose() {
    _statusTimer?.cancel();
    _bannerTimer?.cancel();
    super.dispose();
  }

  Future<void> loadProducts() async {
    setState(() => _loading = true);
    _checkStoreStatus();

    final data = await ApiService.getProducts();
    if (!mounted) return;
    setState(() {
      products = data;
      // Safely clean up cart if a product went out of stock
      cart.removeWhere((id, _) {
        final p = products.firstWhere(
              (x) => int.tryParse(x['product_id'].toString()) == id,
          orElse: () => null,
        );
        return p != null && _isOutOfStock(p);
      });
      _loading = false;
    });
  }

  Future<void> _checkStoreStatus() async {
    final status = await ApiService.getStoreStatus(); // <-- Added missing line
    if (!mounted) return;

    setState(() {
      _openTime = status['open_time'] ?? '';
      _closeTime = status['close_time'] ?? '';
    });

    // Bulletproof boolean check
    final isOpen = status['is_open'] == true || status['is_open'] == 1 || status['is_open'] == 'true';
    final newType = isOpen ? 'normal' : 'closed';
    final newMessage = status['message'] ?? '';

    // 1. HANDLE THE STARTUP POP-UP (Only shows once per app session)
    if (!_hasShownStartupPopup) {
      _hasShownStartupPopup = true; // Mark as shown

      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Row(
            children: [
              Icon(
                isOpen ? Icons.storefront_rounded : Icons.lock_outline_rounded,
                color: isOpen ? _T.success : _T.error,
                size: 28,
              ),
              const SizedBox(width: 10),
              Text(isOpen ? 'Store is Open!' : 'Store is Closed'),
            ],
          ),
          content: Text(
            isOpen
                ? 'Welcome back! The market is open. You can start adding items to your cart.'
                : 'Welcome back! The market is currently closed. You cannot place orders right now.',
            style: const TextStyle(fontSize: 14, height: 1.4),
          ),
          actions: [
            ElevatedButton(
              onPressed: () => Navigator.pop(ctx),
              style: ElevatedButton.styleFrom(
                backgroundColor: isOpen ? _T.success : _T.error,
              ),
              child: const Text('Okay', style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      );
    }

    // 2. Handle the Top Banner logic
    if (newMessage.isNotEmpty && newMessage != _storeWarning) {
      setState(() {
        _statusType = newType;
        _storeWarning = newMessage;
        _showBanner = true;
      });

      _bannerTimer?.cancel();
      _bannerTimer = Timer(const Duration(seconds: 5), () {
        if (mounted) setState(() => _showBanner = false);
      });
    } else {
      setState(() {
        _statusType = newType;
        _storeWarning = newMessage;
      });
    }
  }
  Color get _bannerColor {
    if (_statusType == 'closed') return _T.error;
    if (_statusType == 'normal') return _T.success;
    return _T.warning;
  }

  IconData get _bannerIcon {
    if (_statusType == 'closed') return Icons.lock_outline;
    if (_statusType == 'normal') return Icons.check_circle_outline;
    return Icons.warning_amber_rounded;
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
            (x) => int.tryParse(x['product_id'].toString()) == id,
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
          (x) => int.tryParse(x['product_id'].toString()) == id,
      orElse: () => null,
    );
    if (p == null || _isOutOfStock(p)) return;

    // Check if expired
    final expiryStr = p['expiry_date'];
    if (expiryStr != null) {
      final expiry = DateTime.tryParse(expiryStr.toString());
      if (expiry != null && expiry.difference(DateTime.now()).inDays < 0) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: _T.error,
            content: const Text('This product is expired and cannot be ordered.', style: TextStyle(color: Colors.white)),
          ),
        );
        return;
      }
    }

    final stock = _getStock(p);
    final current = cart[id] ?? 0;

    if (stock != null && current >= stock) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: _T.warning,
          content: Text('Only $stock available', style: const TextStyle(color: Colors.white)),
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
          content: Text('Only $maxStock available', style: const TextStyle(color: Colors.white)),
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
          Position pos = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
          lat = pos.latitude;
          lng = pos.longitude;
        }
      }
    } catch (e) {
      print('GPS Error: $e');
    }

    final items = cart.entries.map((e) => {'product_id': e.key, 'quantity': e.value}).toList();
    final result = await ApiService.placeOrder(widget.retailerId, items, lat: lat, lng: lng);

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
      ),
    );
    if (confirmed == true) await placeOrder();
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Scaffold(
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
          body: Column(
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                curve: Curves.easeInOut,
                height: _showBanner ? null : 0,
                child: _showBanner
                    ? Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  color: _bannerColor,
                  child: Row(
                    children: [
                      Icon(_bannerIcon, color: Colors.white, size: 20),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          _storeWarning,
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                      ),
                      GestureDetector(
                        onTap: () => setState(() => _showBanner = false),
                        child: const Icon(Icons.close, color: Colors.white, size: 18),
                      )
                    ],
                  ),
                )
                    : const SizedBox.shrink(),
              ),

              if (!_showBanner && _storeWarning.isNotEmpty)
                Container(height: 3, color: _bannerColor),

              _searchBar(),
              Expanded(
                child: _loading
                    ? const Center(child: CircularProgressIndicator(color: _T.brand))
                    : products.isEmpty
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
        ),

        // --- LOADING OVERLAY ---
        if (_placing)
          Positioned.fill(
            child: Container(
              color: Colors.black.withOpacity(0.6),
              child: Center(
                child: Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: _T.surface,
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.2),
                        blurRadius: 10,
                        spreadRadius: 2,
                      )
                    ],
                  ),
                  child: const Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CircularProgressIndicator(color: _T.brand),
                      SizedBox(height: 20),
                      Text(
                        'Placing Order...',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: _T.text),
                      ),
                      SizedBox(height: 8),
                      Text(
                        'Please wait while we process your order.',
                        style: TextStyle(fontSize: 12, color: _T.muted),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _searchBar() {
    return Padding(
      padding: const EdgeInsets.all(_T.sMd),
      child: Column(
        children: [
          TextField(
            onChanged: (v) => setState(() => _query = v),
            decoration: InputDecoration(
              hintText: 'Search products',
              prefixIcon: const Icon(Icons.search_rounded, size: 20),
              fillColor: _T.surface,
            ),
          ),
          if (_openTime.isNotEmpty && _closeTime.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 12),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.schedule_rounded, size: 16, color: _T.muted),
                  const SizedBox(width: 6),
                  Text(
                    _statusType == 'closed' && _storeWarning.contains('Manual')
                        ? 'Manually closed by admin'
                        : 'Open: $_openTime - $_closeTime',
                    style: const TextStyle(
                        fontSize: 12,
                        color: _T.muted,
                        fontWeight: FontWeight.w600
                    ),
                  ),
                ],
              ),
            ),
        ],
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
        final id = int.tryParse(p['product_id'].toString()) ?? 0;

        return Padding(
          padding: const EdgeInsets.symmetric(horizontal: _T.sMd, vertical: 4),
          child: _ProductCard(
            product: p,
            qty: cart[id] ?? 0,
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
// PRODUCT CARD & OTHER WIDGETS
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



  Widget _buildExpiryBadge() {
    final expiryStr = product['expiry_date'];
    final status = product['status']?.toString() ?? 'available';
    final stock = this.stock;

    // 1. Check if database says expired OR the date is in the past
    bool isExpired = status == 'expired';

    if (!isExpired && expiryStr != null) {
      final expiry = DateTime.tryParse(expiryStr.toString());
      if (expiry != null && expiry.difference(DateTime.now()).inDays < 0) {
        isExpired = true;
      }
    }

    if (isExpired) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
        decoration: BoxDecoration(color: Colors.red.shade100, borderRadius: BorderRadius.circular(4)),
        child: const Text('EXPIRED', style: TextStyle(color: Colors.red, fontSize: 10, fontWeight: FontWeight.bold)),
      );
    }

    // 2. Check for Low Stock
    if (stock != null && stock <= 5) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
        decoration: BoxDecoration(color: Colors.orange.shade100, borderRadius: BorderRadius.circular(4)),
        child: Text('Low Stock ($stock left)', style: TextStyle(color: Colors.orange.shade900, fontSize: 10, fontWeight: FontWeight.bold)),
      );
    }

    // 3. Check for Eat Soon
    if (expiryStr != null) {
      final expiry = DateTime.tryParse(expiryStr.toString());
      if (expiry != null) {
        final daysLeft = expiry.difference(DateTime.now()).inDays;
        if (daysLeft <= 3) {
          return Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(color: Colors.orange.shade100, borderRadius: BorderRadius.circular(4)),
            child: Text('Eat Soon! ($daysLeft days left)', style: TextStyle(color: Colors.orange.shade900, fontSize: 10, fontWeight: FontWeight.bold)),
          );
        }
      }
    }

    // 4. Default: Fresh Batch
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(color: Colors.green.shade100, borderRadius: BorderRadius.circular(4)),
      child: Text('Fresh Batch', style: TextStyle(color: Colors.green.shade800, fontSize: 10, fontWeight: FontWeight.bold)),
    );
  }


  @override
  Widget build(BuildContext context) {
    final id = int.tryParse(product['product_id'].toString()) ?? 0;
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
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        product['name'] ?? 'Unnamed',
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: 8),
                    _buildExpiryBadge(),
                  ],
                ),
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
              child: placing
                  ? const SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(strokeWidth: 2, color: _T.brand),
              )
                  : const Text('Review'),
            ),
          ],
        ),
      ),
    );
  }
}

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
        int liveCount = 0;
        double liveTotal = 0;

        cart.forEach((id, qty) {
          final p = products.firstWhere(
                (x) => int.tryParse(x['product_id'].toString()) == id,
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

              ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 300),
                child: ListView.separated(
                  shrinkWrap: true,
                  itemCount: cart.length,
                  separatorBuilder: (_, __) => const Divider(),
                  itemBuilder: (_, i) {
                    final entry = cart.entries.elementAt(i);
                    final p = products.firstWhere(
                          (x) => int.tryParse(x['product_id'].toString()) == entry.key,
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
                          IconButton(
                            icon: const Icon(Icons.delete_outline_rounded, color: _T.error),
                            onPressed: () {
                              onRemoveItem(entry.key);
                              setSheetState(() {});
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