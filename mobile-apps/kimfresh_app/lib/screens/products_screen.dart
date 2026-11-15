import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:geolocator/geolocator.dart';
import '../services/api.dart';
import '../theme/colors.dart';
import 'orders_screen.dart';
import 'retailer_profile_screen.dart';

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

  // --- SEARCH CONTROLLER ---
  final TextEditingController _searchController = TextEditingController();
  final FocusNode _searchFocusNode = FocusNode();

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
    _searchController.dispose();
    _searchFocusNode.dispose();
    super.dispose();
  }

  Future<void> loadProducts() async {
    setState(() => _loading = true);
    _checkStoreStatus();

    final data = await ApiService.getProducts();
    if (!mounted) return;
    setState(() {
      products = data;
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
    final status = await ApiService.getStoreStatus();
    if (!mounted) return;

    setState(() {
      _openTime = status['open_time'] ?? '';
      _closeTime = status['close_time'] ?? '';
    });

    final isOpen = status['is_open'] == true || status['is_open'] == 1 || status['is_open'] == 'true';
    final newType = isOpen ? 'normal' : 'closed';
    final newMessage = status['message'] ?? '';

    if (!_hasShownStartupPopup) {
      _hasShownStartupPopup = true;

      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          backgroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: (isOpen ? _T.success : _T.error).withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  isOpen ? Icons.storefront_rounded : Icons.lock_outline_rounded,
                  color: isOpen ? _T.success : _T.error,
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Text(
                isOpen ? 'Store is Open!' : 'Store is Closed',
                style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.black),
              ),
            ],
          ),
          content: Text(
            isOpen
                ? 'Welcome back! The market is open. You can start adding items to your cart.'
                : 'Welcome back! The market is currently closed. You cannot place orders right now.',
            style: const TextStyle(fontSize: 14, height: 1.5, color: _T.muted),
          ),
          actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
          actions: [
            ElevatedButton(
              onPressed: () => Navigator.pop(ctx),
              style: ElevatedButton.styleFrom(
                backgroundColor: isOpen ? _T.success : _T.error,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              ),
              child: const Text('Got it', style: TextStyle(fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      );
    }

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

    final expiryStr = p['expiry_date'];
    if (expiryStr != null) {
      final expiry = DateTime.tryParse(expiryStr.toString());
      if (expiry != null && expiry.difference(DateTime.now()).inDays < 0) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: _T.error,
            content: const Text('This product is expired and cannot be ordered.', style: TextStyle(color: Colors.white)),
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
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
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
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
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
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
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );

    if (ok) {
      final newOrderId = result['order_id'];
      if (newOrderId != null && lat != null && lng != null) {
        await ApiService.saveRetailerGPS(widget.retailerId, lat, lng);
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
      backgroundColor: Colors.transparent,
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
          backgroundColor: const Color(0xFFF4F5F7),
          appBar: AppBar(
            backgroundColor: const Color(0xFFF4F5F7),
            elevation: 0,
            surfaceTintColor: Colors.transparent,
            titleSpacing: 20,
            centerTitle: false,
            title: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                    'Store',
                    style: TextStyle(
                        fontSize: 32,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -1.5,
                        color: Colors.black
                    )
                ),
                Text(
                    'KimFresh · ${widget.retailerName}',
                    style: const TextStyle(
                        fontSize: 13,
                        color: _T.muted,
                        fontWeight: FontWeight.w500
                    )
                ),
              ],
            ),
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
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                  color: _bannerColor,
                  child: Row(
                    children: [
                      Icon(_bannerIcon, color: Colors.white, size: 22),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          _storeWarning,
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                      ),
                      GestureDetector(
                        onTap: () => setState(() => _showBanner = false),
                        child: const Icon(Icons.close_rounded, color: Colors.white, size: 20),
                      )
                    ],
                  ),
                )
                    : const SizedBox.shrink(),
              ),

              if (!_showBanner && _storeWarning.isNotEmpty)
                Container(height: 4, color: _bannerColor),

              Padding(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
                child: Container(
                  decoration: BoxDecoration(
                    boxShadow: [
                      BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 10, offset: const Offset(0, 4)),
                    ],
                  ),
                  child: TextField(
                    controller: _searchController,
                    focusNode: _searchFocusNode,
                    onChanged: (v) => setState(() => _query = v),
                    style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w500),
                    decoration: InputDecoration(
                      hintText: 'Search products...',
                      hintStyle: TextStyle(color: _T.muted.withOpacity(0.7), fontSize: 15),
                      prefixIcon: const Icon(Icons.search_rounded, size: 22, color: _T.muted),
                      filled: true,
                      fillColor: Colors.white,
                      contentPadding: const EdgeInsets.symmetric(vertical: 18),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: _T.brand, width: 1.5)),
                    ),
                  ),
                ),
              ),

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

          bottomNavigationBar: SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(30),
                  boxShadow: [
                    BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 24, offset: const Offset(0, 8)),
                  ],
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _buildDockIcon(
                      icon: Icons.receipt_long_rounded,
                      label: 'Orders',
                      onTap: () => Navigator.push(context, MaterialPageRoute(
                        builder: (_) => OrdersScreen(retailerId: widget.retailerId),
                      )),
                    ),
                    _buildDockIcon(
                      icon: Icons.shopping_bag_rounded,
                      label: 'Cart',
                      badgeCount: _cartCount,
                      onTap: () {
                        if (cart.isNotEmpty) {
                          _confirmOrder();
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Your cart is empty'), behavior: SnackBarBehavior.floating),
                          );
                        }
                      },
                    ),
                    _buildDockIcon(
                      icon: Icons.person_rounded,
                      label: 'Profile',
                      onTap: () => Navigator.push(context, MaterialPageRoute(
                        builder: (_) => RetailerProfileScreen(
                          retailerId: widget.retailerId,
                          retailerName: widget.retailerName,
                        ),
                      )),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),

        if (_placing)
          Positioned.fill(
            child: Container(
              color: Colors.black.withOpacity(0.5),
              child: Center(
                child: Container(
                  padding: const EdgeInsets.all(32),
                  decoration: BoxDecoration(
                    color: _T.surface,
                    borderRadius: BorderRadius.circular(24),
                  ),
                  child: const Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CircularProgressIndicator(color: _T.brand, strokeWidth: 3),
                      SizedBox(height: 24),
                      Text('Placing Order...', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: _T.text)),
                    ],
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildDockIcon({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
    int badgeCount = 0,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: SizedBox(
        width: 70,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                Icon(icon, color: const Color(0xFFB0B3B8), size: 24),
                if (badgeCount > 0)
                  Positioned(
                    right: -6,
                    top: -4,
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: const BoxDecoration(color: _T.brand, shape: BoxShape.circle),
                      child: Text(
                        '$badgeCount',
                        style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: const TextStyle(color: Color(0xFFB0B3B8), fontSize: 11, fontWeight: FontWeight.w500),
            ),
          ],
        ),
      ),
    );
  }

  Widget _emptyProducts() {
    return const Center(child: Text('No products available. Pull to refresh.', style: TextStyle(color: _T.muted)));
  }

  Widget _productList() {
    final items = _visibleProducts;
    if (items.isEmpty) return const Center(child: Text('No matches found.', style: TextStyle(color: _T.muted)));

    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
      itemCount: items.length,
      itemBuilder: (_, i) {
        final p = items[i];
        final id = int.tryParse(p['product_id'].toString()) ?? 0;

        return Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: _ProductBoxCard(
            product: p,
            qty: cart[id] ?? 0,
            outOfStock: _isOutOfStock(p),
            stock: _getStock(p),
            bgColor: _T.brand, // ALL SAME COLOR AS REQUESTED
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
// PRODUCT BOX CARD (OLD SOLID COLOR DESIGN)
// ============================================================

class _ProductBoxCard extends StatelessWidget {
  final dynamic product;
  final int qty;
  final bool outOfStock;
  final int? stock;
  final Color bgColor;
  final void Function(int) onAdd;
  final void Function(int) onRemove;
  final void Function(int, int, int?) onSetQuantity;

  const _ProductBoxCard({
    required this.product,
    required this.qty,
    required this.outOfStock,
    required this.stock,
    required this.bgColor,
    required this.onAdd,
    required this.onRemove,
    required this.onSetQuantity,
  });

  Widget _buildExpiryBadge() {
    final expiryStr = product['expiry_date'];
    final status = product['status']?.toString() ?? 'available';
    final stock = this.stock;

    bool isExpired = status == 'expired';
    if (!isExpired && expiryStr != null) {
      final expiry = DateTime.tryParse(expiryStr.toString());
      if (expiry != null && expiry.difference(DateTime.now()).inDays < 0) isExpired = true;
    }

    String text = 'FRESH';
    if (isExpired) {
      text = 'EXPIRED';
    } else if (stock != null && stock <= 5) {
      text = 'LOW STOCK';
    } else if (expiryStr != null) {
      final expiry = DateTime.tryParse(expiryStr.toString());
      if (expiry != null && expiry.difference(DateTime.now()).inDays <= 3) {
        text = 'EAT SOON';
      }
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.2),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.white.withOpacity(0.3)),
      ),
      child: Text(
          text,
          style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: 0.5)
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final id = int.tryParse(product['product_id'].toString()) ?? 0;
    final inCart = qty > 0;
    final price = product['unit_price']?.toString() ?? '0';
    final name = product['name'] ?? 'Unnamed';

    return Container(
      height: 170,
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: bgColor.withOpacity(0.3),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Stack(
        children: [
          Positioned(
            right: -20,
            top: -20,
            child: Container(
              width: 130,
              height: 130,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withOpacity(0.1),
              ),
            ),
          ),

          Padding(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.black.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        'SKU: ${product['sku'] ?? '-'}',
                        style: const TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.w600),
                      ),
                    ),
                    _buildExpiryBadge(),
                  ],
                ),

                const Spacer(),

                Text(
                  name,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.5,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),

                const SizedBox(height: 4),

                Row(
                  children: [
                    Text(
                      '₱$price',
                      style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(width: 8),
                    if (!outOfStock && stock != null)
                      Text(
                        '• $stock in stock',
                        style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 12, fontWeight: FontWeight.w500),
                      ),
                    const Spacer(),

                    if (outOfStock)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(color: Colors.black.withOpacity(0.3), borderRadius: BorderRadius.circular(12)),
                        child: const Text('Sold Out', style: TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold)),
                      )
                    else if (inCart)
                      Container(
                        height: 36,
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            InkWell(
                              onTap: () => onRemove(id),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                child: Icon(Icons.remove_rounded, size: 18, color: bgColor),
                              ),
                            ),
                            SizedBox(
                              width: 28,
                              child: _QuantityInput(
                                qty: qty,
                                onSubmitted: (newQty) => onSetQuantity(id, newQty, stock),
                              ),
                            ),
                            InkWell(
                              onTap: (stock != null && qty >= stock!) ? null : () => onAdd(id),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                child: Icon(Icons.add_rounded, size: 18, color: (stock != null && qty >= stock!) ? Colors.grey : bgColor),
                              ),
                            ),
                          ],
                        ),
                      )
                    else
                      ElevatedButton(
                        onPressed: () => onAdd(id),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: bgColor,
                          elevation: 0,
                          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: const Text('Add', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// Helper widget to type quantity
class _QuantityInput extends StatefulWidget {
  final int qty;
  final void Function(int) onSubmitted;

  const _QuantityInput({required this.qty, required this.onSubmitted});

  @override
  State<_QuantityInput> createState() => _QuantityInputState();
}

class _QuantityInputState extends State<_QuantityInput> {
  late TextEditingController _controller;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: widget.qty.toString());
  }

  @override
  void didUpdateWidget(covariant _QuantityInput oldWidget) {
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

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: _controller,
      textAlign: TextAlign.center,
      keyboardType: TextInputType.number,
      inputFormatters: [FilteringTextInputFormatter.digitsOnly],
      style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: _T.brand),
      decoration: const InputDecoration(
        border: InputBorder.none,
        isDense: true,
        contentPadding: EdgeInsets.zero,
      ),
      onFieldSubmitted: (val) {
        final parsed = int.tryParse(val);
        if (parsed != null) widget.onSubmitted(parsed);
      },
      onTapOutside: (_) {
        FocusScope.of(context).unfocus();
        final parsed = int.tryParse(_controller.text);
        if (parsed != null) widget.onSubmitted(parsed);
      },
    );
  }
}

// ============================================================
// REVIEW SHEET
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

        return Container(
          decoration: const BoxDecoration(
            color: _T.surface,
            borderRadius: BorderRadius.vertical(top: Radius.circular(32)),
          ),
          padding: EdgeInsets.only(
            left: 24,
            right: 24,
            top: 16,
            bottom: MediaQuery.of(context).viewInsets.bottom + 32,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(height: 5, width: 48, decoration: BoxDecoration(color: _T.border, borderRadius: BorderRadius.circular(4))),
              const SizedBox(height: 24),
              const Text('Review Order', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, letterSpacing: -0.5)),
              const SizedBox(height: 4),
              Text('$liveCount items', style: const TextStyle(color: _T.muted, fontSize: 14, fontWeight: FontWeight.w500)),
              const SizedBox(height: 24),

              ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 350),
                child: ListView.separated(
                  shrinkWrap: true,
                  itemCount: cart.length,
                  separatorBuilder: (_, __) => const Divider(height: 24, color: _T.border),
                  itemBuilder: (_, i) {
                    final entry = cart.entries.elementAt(i);
                    final p = products.firstWhere(
                          (x) => int.tryParse(x['product_id'].toString()) == entry.key,
                      orElse: () => null,
                    );
                    final price = double.tryParse(p?['unit_price'].toString() ?? '0') ?? 0;

                    return Row(
                      children: [
                        Container(
                          height: 40,
                          width: 40,
                          decoration: BoxDecoration(color: _T.bg, borderRadius: BorderRadius.circular(10)),
                          child: const Icon(Icons.shopping_bag_outlined, size: 20, color: _T.muted),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(p?['name'] ?? 'Product', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                              const SizedBox(height: 2),
                              Text('₱${price.toStringAsFixed(2)} × ${entry.value}', style: const TextStyle(color: _T.muted, fontSize: 12)),
                            ],
                          ),
                        ),
                        Text('₱${(price * entry.value).toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                        const SizedBox(width: 8),
                        IconButton(
                          icon: const Icon(Icons.delete_outline_rounded, color: _T.error, size: 20),
                          onPressed: () {
                            onRemoveItem(entry.key);
                            setSheetState(() {});
                          },
                        ),
                      ],
                    );
                  },
                ),
              ),

              const Divider(height: 32, color: _T.border),
              Row(
                children: [
                  const Text('Total', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: _T.muted)),
                  const Spacer(),
                  Text('₱${liveTotal.toStringAsFixed(2)}', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: _T.brand, letterSpacing: -1)),
                ],
              ),
              const SizedBox(height: 32),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => Navigator.pop(context, false),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        side: const BorderSide(color: _T.border),
                      ),
                      child: const Text('Cancel', style: TextStyle(color: _T.text, fontWeight: FontWeight.bold)),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton(
                      onPressed: liveCount == 0 ? null : () => Navigator.pop(context, true),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _T.brand,
                        foregroundColor: Colors.white,
                        elevation: 0,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      child: const Text('Place Order', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
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