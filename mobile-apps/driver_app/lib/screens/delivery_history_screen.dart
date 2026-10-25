import 'package:flutter/material.dart';
import '../services/api.dart';
import '../theme/app_theme.dart';

class DeliveryHistoryScreen extends StatefulWidget {
  final int driverId;
  DeliveryHistoryScreen({required this.driverId});

  @override
  _DeliveryHistoryScreenState createState() => _DeliveryHistoryScreenState();
}

class _DeliveryHistoryScreenState extends State<DeliveryHistoryScreen> {
  List<dynamic> history = [];
  bool loading = true;

  @override
  void initState() {
    super.initState();
    loadHistory();
  }

  Future<void> loadHistory() async {
    setState(() => loading = true);
    final allDeliveries = await ApiService.getDeliveries(widget.driverId);

    // Filter only delivered orders
    final delivered = allDeliveries.where((d) {
      if (d is Map) {
        return d['delivery_status'] == 'delivered';
      }
      return false;
    }).toList();

    if (!mounted) return;
    setState(() {
      history = delivered;
      loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    Widget body;
    if (loading) {
      body = const Center(child: CircularProgressIndicator());
    } else if (history.isEmpty) {
      body = Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: AppColors.burgundy.withOpacity(0.08),
                shape: BoxShape.circle,
              ),
              alignment: Alignment.center,
              child: const Icon(Icons.history_rounded,
                  size: 40, color: AppColors.burgundy),
            ),
            const SizedBox(height: 16),
            const Text(
              'No delivery history yet',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            const Text(
              'Completed deliveries will appear here.',
              style: TextStyle(fontSize: 13, color: AppColors.textSecondary),
            ),
          ],
        ),
      );
    } else {
      body = ListView.builder(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        itemCount: history.length,
        itemBuilder: (context, i) {
          final d = history[i];
          final items = d['items'] as List? ?? [];
          final orderId = d['order_id']?.toString() ?? '-';

          return Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Order #$orderId',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: AppColors.success.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: const Text(
                        'DELIVERED',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 0.4,
                          color: AppColors.success,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    const Icon(Icons.storefront_rounded,
                        size: 16, color: AppColors.textSecondary),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        d['retailer_name']?.toString() ?? '-',
                        style: const TextStyle(
                          fontSize: 13,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    const Icon(Icons.inventory_2_outlined,
                        size: 16, color: AppColors.textSecondary),
                    const SizedBox(width: 8),
                    Text(
                      '${items.length} items delivered',
                      style: const TextStyle(
                        fontSize: 13,
                        color: AppColors.textPrimary,
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

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: const Text('Delivery History'),
      ),
      body: RefreshIndicator(
        color: AppColors.burgundy,
        onRefresh: loadHistory,
        child: body,
      ),
    );
  }
}