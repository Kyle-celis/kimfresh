import 'package:flutter/material.dart';
import '../services/api.dart';
import '../theme/colors.dart';
import 'order_tracking_map_screen.dart';

class OrdersScreen extends StatefulWidget {
  final int retailerId;
  final String? retailerName; // Added to fix the error

  OrdersScreen({
    Key? key,
    required this.retailerId,
    this.retailerName,
  }) : super(key: key);

  @override
  _OrdersScreenState createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  List<dynamic> orders = [];
  bool _loading = true;
  String _filter = 'all';

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

  Color _statusColor(String? s) {
    switch (s) {
      case 'pending':
        return AppColors.warning;
      case 'confirmed':
        return AppColors.purple;
      case 'in_transit':
        return AppColors.info;
      case 'delivered':
        return AppColors.success;
      case 'cancelled':
        return AppColors.error;
      default:
        return AppColors.muted;
    }
  }

  IconData _statusIcon(String? s) {
    switch (s) {
      case 'pending':
        return Icons.schedule_rounded;
      case 'confirmed':
        return Icons.verified_outlined;
      case 'in_transit':
        return Icons.local_shipping_outlined;
      case 'delivered':
        return Icons.check_circle_outline_rounded;
      case 'cancelled':
        return Icons.cancel_outlined;
      default:
        return Icons.receipt_long_outlined;
    }
  }

  String _pretty(String? s) {
    switch (s) {
      case 'in_transit':
        return 'In transit';
      case 'pending':
        return 'Pending';
      case 'confirmed':
        return 'Confirmed';
      case 'delivered':
        return 'Delivered';
      case 'cancelled':
        return 'Cancelled';
      default:
        return (s ?? 'Unknown');
    }
  }

  List<dynamic> get _filteredOrders {
    if (_filter == 'all') return orders;
    return orders
        .where((o) => (o['order_status'] ?? 'pending') == _filter)
        .toList();
  }

  int _countFor(String s) {
    if (s == 'all') return orders.length;
    return orders
        .where((o) => (o['order_status'] ?? 'pending') == s)
        .length;
  }

  void _openTracking(dynamic order) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => _TrackingSheet(order: order),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        foregroundColor: AppColors.text,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: const Text(
          'My Orders',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w700,
            color: AppColors.text,
            letterSpacing: -0.2,
          ),
        ),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh_rounded, color: AppColors.text),
            onPressed: _loading ? null : loadOrders,
          ),
        ],
      ),
      body: _loading
          ? const Center(
          child: CircularProgressIndicator(color: AppColors.brand))
          : Column(
        children: [
          _filterBar(),
          Expanded(
            child: orders.isEmpty
                ? _empty()
                : RefreshIndicator(
              color: AppColors.brand,
              onRefresh: loadOrders,
              child: _filteredOrders.isEmpty
                  ? _emptyFiltered()
                  : ListView.builder(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                itemCount: _filteredOrders.length,
                itemBuilder: (_, i) =>
                    _orderCard(_filteredOrders[i]),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _filterBar() {
    final filters = <MapEntry<String, String>>[
      const MapEntry('all', 'All'),
      const MapEntry('pending', 'Pending'),
      const MapEntry('in_transit', 'In transit'),
      const MapEntry('delivered', 'Delivered'),
    ];

    return Container(
      height: 52,
      color: AppColors.background,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
        itemCount: filters.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (_, i) {
          final f = filters[i];
          final selected = _filter == f.key;
          final count = _countFor(f.key);
          return GestureDetector(
            onTap: () => setState(() => _filter = f.key),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              padding:
              const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                color: selected ? AppColors.brand : AppColors.surface,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                  color: selected ? AppColors.brand : AppColors.border,
                ),
              ),
              child: Row(
                children: [
                  Text(
                    f.value,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: selected ? Colors.white : AppColors.text,
                    ),
                  ),
                  if (count > 0 && f.key != 'all') ...[
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 6, vertical: 1),
                      decoration: BoxDecoration(
                        color: selected
                            ? Colors.white.withOpacity(0.25)
                            : AppColors.brand.withOpacity(0.10),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        '$count',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                          color: selected ? Colors.white : AppColors.brand,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _empty() {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        SizedBox(height: MediaQuery.of(context).size.height * 0.18),
        Center(
          child: Column(
            children: [
              Container(
                height: 72,
                width: 72,
                decoration: BoxDecoration(
                  color: AppColors.brand.withOpacity(0.06),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.receipt_long_outlined,
                    size: 32, color: AppColors.brand.withOpacity(0.7)),
              ),
              const SizedBox(height: 16),
              const Text(
                'No orders yet',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: AppColors.text,
                ),
              ),
              const SizedBox(height: 4),
              const Text(
                'Your orders will appear here.',
                style: TextStyle(fontSize: 13, color: AppColors.muted),
              ),
              const SizedBox(height: 20),
              SizedBox(
                height: 40,
                child: OutlinedButton.icon(
                  onPressed: loadOrders,
                  icon: const Icon(Icons.refresh_rounded, size: 16),
                  label: const Text('Refresh'),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _emptyFiltered() {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        SizedBox(height: MediaQuery.of(context).size.height * 0.18),
        Center(
          child: Column(
            children: [
              Container(
                height: 72,
                width: 72,
                decoration: BoxDecoration(
                  color: AppColors.brand.withOpacity(0.06),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.filter_alt_off_outlined,
                    size: 32, color: AppColors.brand.withOpacity(0.7)),
              ),
              const SizedBox(height: 16),
              Text(
                'No ${_filter == 'in_transit' ? 'in transit' : _filter} orders',
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: AppColors.text,
                ),
              ),
              const SizedBox(height: 4),
              const Text(
                'Try a different filter.',
                style: TextStyle(fontSize: 13, color: AppColors.muted),
              ),
              const SizedBox(height: 20),
              TextButton(
                onPressed: () => setState(() => _filter = 'all'),
                style: TextButton.styleFrom(
                    foregroundColor: AppColors.brand),
                child: const Text('Show all orders'),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _orderCard(dynamic o) {
    final status = o['order_status'] ?? 'pending';
    final color = _statusColor(status);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
        boxShadow: AppColors.cardShadow,
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: () => _openTracking(o),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: Column(
              children: [
                Container(height: 4, color: color),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Order #${o['order_id']}',
                                  style: const TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.text,
                                  ),
                                ),
                                if (o['order_date'] != null) ...[
                                  const SizedBox(height: 2),
                                  Row(
                                    children: [
                                      const Icon(
                                          Icons.calendar_today_outlined,
                                          size: 11,
                                          color: AppColors.muted),
                                      const SizedBox(width: 4),
                                      Text(
                                        '${o['order_date']}',
                                        style: const TextStyle(
                                            fontSize: 11,
                                            color: AppColors.muted),
                                      ),
                                    ],
                                  ),
                                ],
                              ],
                            ),
                          ),
                          _chip(status, color),
                        ],
                      ),
                      const SizedBox(height: 12),
                      if (o['product_name'] != null)
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 8),
                          decoration: BoxDecoration(
                            color: AppColors.background,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Row(
                            children: [
                              Container(
                                height: 32,
                                width: 32,
                                decoration: BoxDecoration(
                                  color: AppColors.brand.withOpacity(0.08),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: const Icon(
                                  Icons.inventory_2_outlined,
                                  size: 16,
                                  color: AppColors.brand,
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  '${o['product_name']}',
                                  style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w500,
                                    color: AppColors.text,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          const Icon(Icons.payments_outlined,
                              size: 14, color: AppColors.muted),
                          const SizedBox(width: 6),
                          const Text(
                            'Total',
                            style: TextStyle(
                                fontSize: 12, color: AppColors.muted),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            '₱${o['total_amount'] ?? '0'}',
                            style: const TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: AppColors.brand,
                            ),
                          ),
                          const Spacer(),
                          Row(
                            children: [
                              Icon(Icons.route_outlined,
                                  size: 14, color: AppColors.brand),
                              const SizedBox(width: 4),
                              const Text(
                                'Track',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.brand,
                                ),
                              ),
                              const Icon(Icons.chevron_right_rounded,
                                  size: 16, color: AppColors.brand),
                            ],
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _chip(String? status, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: color.withOpacity(0.10),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(_statusIcon(status), size: 12, color: color),
          const SizedBox(width: 4),
          Text(
            _pretty(status),
            style: TextStyle(
              color: color,
              fontSize: 11,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.1,
            ),
          ),
        ],
      ),
    );
  }
}

// ============================================================
// Tracking bottom sheet
// ============================================================
class _TrackingSheet extends StatelessWidget {
  final dynamic order;
  const _TrackingSheet({required this.order});

  static const List<_Stage> _stages = [
    _Stage(
      key: 'pending',
      label: 'Order placed',
      description: 'We received your order.',
      icon: Icons.receipt_long_outlined,
    ),
    _Stage(
      key: 'confirmed',
      label: 'Confirmed',
      description: 'Your order has been confirmed.',
      icon: Icons.verified_outlined,
    ),
    _Stage(
      key: 'in_transit',
      label: 'In transit',
      description: 'Your order is on the way.',
      icon: Icons.local_shipping_outlined,
    ),
    _Stage(
      key: 'delivered',
      label: 'Delivered',
      description: 'Your order has arrived.',
      icon: Icons.check_circle_outline_rounded,
    ),
  ];

  int _stageIndexFor(String? status) {
    switch (status) {
      case 'pending':
        return 0;
      case 'confirmed':
        return 1;
      case 'in_transit':
        return 2;
      case 'delivered':
        return 3;
      case 'cancelled':
        return -1;
      default:
        return 0;
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = order['order_status'] ?? 'pending';
    final isCancelled = status == 'cancelled';
    final currentIndex = _stageIndexFor(status);
    final progress = isCancelled
        ? 0.0
        : (currentIndex + 1) / _stages.length;

    return DraggableScrollableSheet(
      initialChildSize: 0.75,
      minChildSize: 0.5,
      maxChildSize: 0.95,
      expand: false,
      builder: (_, scrollController) {
        return SingleChildScrollView(
          controller: scrollController,
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).padding.bottom + 24,
          ),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(24, 12, 24, 0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    height: 4,
                    width: 36,
                    decoration: BoxDecoration(
                      color: AppColors.border,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Order #${order['order_id']}',
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w700,
                              color: AppColors.text,
                            ),
                          ),
                          if (order['order_date'] != null)
                            Padding(
                              padding: const EdgeInsets.only(top: 2),
                              child: Text(
                                '${order['order_date']}',
                                style: const TextStyle(
                                    fontSize: 12,
                                    color: AppColors.muted),
                              ),
                            ),
                        ],
                      ),
                    ),
                    _statusChip(status),
                  ],
                ),
                const SizedBox(height: 20),
                _banner(status, isCancelled),
                const SizedBox(height: 16),

                // ---- Map Button: ONLY for in_transit OR delivered ----
                if (status == 'in_transit' || status == 'delivered') ...[
                  SizedBox(
                    height: 48,
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      icon: const Icon(Icons.map_outlined, size: 18),
                      label: Text(
                          status == 'in_transit'
                              ? 'Track on live map'
                              : 'View delivery route'
                      ),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.brand,
                        side: BorderSide(
                            color: AppColors.brand.withOpacity(0.4)),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                        textStyle: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => OrderTrackingMapScreen(
                              orderId: int.tryParse(order['order_id'].toString()) ?? 0,
                              orderStatus: status,
                              productName: order['product_name']?.toString(),
                              courierName: order['driver_name']?.toString(),
                              productQuantity: order['quantity'] != null
                                  ? int.tryParse(order['quantity'].toString())
                                  : 1, // <-- ADD THIS
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                // ---- Existing progress + timeline ----
                if (!isCancelled) ...[
                  ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: progress,
                      minHeight: 6,
                      backgroundColor: AppColors.border,
                      valueColor: const AlwaysStoppedAnimation<Color>(
                          AppColors.brand),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Step ${currentIndex + 1} of ${_stages.length}',
                    style: const TextStyle(
                        fontSize: 11, color: AppColors.muted),
                  ),
                  const SizedBox(height: 20),
                  ..._buildTimeline(currentIndex),
                ],
                const SizedBox(height: 24),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.background,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Column(
                    children: [
                      if (order['product_name'] != null)
                        _detailRow(
                          icon: Icons.inventory_2_outlined,
                          label: 'Product',
                          value: '${order['product_name']}',
                        ),
                      if (order['product_name'] != null)
                        const SizedBox(height: 10),
                      _detailRow(
                        icon: Icons.payments_outlined,
                        label: 'Total',
                        value: '₱${order['total_amount'] ?? '0'}',
                        valueColor: AppColors.brand,
                        valueBold: true,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _statusChip(String status) {
    final color = _colorFor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withOpacity(0.10),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        _labelFor(status),
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }

  Widget _banner(String status, bool isCancelled) {
    final color = isCancelled ? AppColors.error : _colorFor(status);
    final icon = isCancelled ? Icons.cancel_outlined : _iconFor(status);
    final message = isCancelled
        ? 'This order was cancelled.'
        : _bannerMessageFor(status);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.06),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withOpacity(0.25)),
      ),
      child: Row(
        children: [
          Icon(icon, size: 20, color: color),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                fontSize: 13,
                color: color,
                fontWeight: FontWeight.w600,
                height: 1.3,
              ),
            ),
          ),
        ],
      ),
    );
  }

  List<Widget> _buildTimeline(int currentIndex) {
    final widgets = <Widget>[];
    for (var i = 0; i < _stages.length; i++) {
      widgets.add(_timelineRow(
        stage: _stages[i],
        done: i < currentIndex,
        active: i == currentIndex,
        isLast: i == _stages.length - 1,
      ));
    }
    return widgets;
  }

  Widget _timelineRow({
    required _Stage stage,
    required bool done,
    required bool active,
    required bool isLast,
  }) {
    final color = done || active ? AppColors.brand : AppColors.muted;
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          SizedBox(
            width: 32,
            child: Column(
              children: [
                Container(
                  height: 24,
                  width: 24,
                  decoration: BoxDecoration(
                    color: done
                        ? AppColors.brand
                        : active
                        ? AppColors.brand.withOpacity(0.15)
                        : AppColors.border,
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: active ? AppColors.brand : Colors.transparent,
                      width: 2,
                    ),
                  ),
                  child: Icon(
                    done ? Icons.check_rounded : stage.icon,
                    size: 12,
                    color: done
                        ? Colors.white
                        : active
                        ? AppColors.brand
                        : AppColors.muted,
                  ),
                ),
                if (!isLast)
                  Expanded(
                    child: Container(
                      width: 2,
                      color: done ? AppColors.brand : AppColors.border,
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(bottom: isLast ? 0 : 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    stage.label,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: color,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    stage.description,
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.muted,
                      height: 1.4,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _detailRow({
    required IconData icon,
    required String label,
    required String value,
    Color? valueColor,
    bool valueBold = false,
  }) {
    return Row(
      children: [
        Icon(icon, size: 15, color: AppColors.muted),
        const SizedBox(width: 10),
        Text(label,
            style:
            const TextStyle(fontSize: 13, color: AppColors.muted)),
        const Spacer(),
        Text(
          value,
          style: TextStyle(
            fontSize: 13,
            fontWeight: valueBold ? FontWeight.w700 : FontWeight.w500,
            color: valueColor ?? AppColors.text,
          ),
        ),
      ],
    );
  }

  Color _colorFor(String s) {
    switch (s) {
      case 'pending':
        return AppColors.warning;
      case 'confirmed':
        return AppColors.purple;
      case 'in_transit':
        return AppColors.info;
      case 'delivered':
        return AppColors.success;
      case 'cancelled':
        return AppColors.error;
      default:
        return AppColors.muted;
    }
  }

  IconData _iconFor(String s) {
    switch (s) {
      case 'pending':
        return Icons.schedule_rounded;
      case 'confirmed':
        return Icons.verified_outlined;
      case 'in_transit':
        return Icons.local_shipping_outlined;
      case 'delivered':
        return Icons.check_circle_outline_rounded;
      default:
        return Icons.receipt_long_outlined;
    }
  }

  String _labelFor(String s) {
    switch (s) {
      case 'in_transit':
        return 'In transit';
      case 'pending':
        return 'Pending';
      case 'confirmed':
        return 'Confirmed';
      case 'delivered':
        return 'Delivered';
      case 'cancelled':
        return 'Cancelled';
      default:
        return s;
    }
  }

  String _bannerMessageFor(String s) {
    switch (s) {
      case 'pending':
        return 'Your order is being prepared.';
      case 'confirmed':
        return 'Your order has been confirmed and is being packed.';
      case 'in_transit':
        return 'Your order is on its way to you.';
      case 'delivered':
        return 'Your order has been delivered. Enjoy!';
      default:
        return 'Tracking your order.';
    }
  }
}

class _Stage {
  final String key;
  final String label;
  final String description;
  final IconData icon;
  const _Stage({
    required this.key,
    required this.label,
    required this.description,
    required this.icon,
  });
}