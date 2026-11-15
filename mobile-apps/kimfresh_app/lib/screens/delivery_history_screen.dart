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

  String? _expandedOrderId;

  @override
  void initState() {
    super.initState();
    loadHistory();
  }

  Future<void> loadHistory() async {
    setState(() => loading = true);

    final allDeliveries = await ApiService.getDeliveries(widget.driverId);

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
      body = const Center(child: CircularProgressIndicator(color: AppColors.burgundy));
    } else if (history.isEmpty) {
      body = Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 100,
              height: 100,
              decoration: BoxDecoration(
                color: AppColors.burgundy.withOpacity(0.05),
                shape: BoxShape.circle,
              ),
              alignment: Alignment.center,
              child: const Icon(Icons.history_rounded, size: 48, color: AppColors.burgundy),
            ),
            const SizedBox(height: 24),
            const Text(
              'No delivery history yet',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Completed deliveries will appear here.',
              style: TextStyle(fontSize: 14, color: AppColors.textSecondary),
            ),
          ],
        ),
      );
    } else {
      body = ListView.builder(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
        itemCount: history.length,
        physics: const AlwaysScrollableScrollPhysics(),
        itemBuilder: (context, i) {
          final d = history[i];
          final orderId = d['order_id']?.toString() ?? '-';
          final isExpanded = _expandedOrderId == orderId;

          return _HistoryCard(
            data: d,
            isExpanded: isExpanded,
            onTap: () {
              setState(() {
                _expandedOrderId = isExpanded ? null : orderId;
              });
            },
          );
        },
      );
    }

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        titleSpacing: 16,
        centerTitle: false,
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        iconTheme: const IconThemeData(color: AppColors.textPrimary),
        title: const Text(
          'Delivery History',
          style: TextStyle(
            color: AppColors.textPrimary,
            fontWeight: FontWeight.w900, // Matched the weight of Deliveries screen
            fontSize: 24, // Matched the size of Deliveries screen
          ),
        ),
      ),
      body: RefreshIndicator(
        color: AppColors.burgundy,
        onRefresh: loadHistory,
        child: body,
      ),
    );
  }
}

class _HistoryCard extends StatelessWidget {
  final dynamic data;
  final bool isExpanded;
  final VoidCallback onTap;

  const _HistoryCard({
    required this.data,
    required this.isExpanded,
    required this.onTap,
  });

  String _formatDate(String? dateStr) {
    if (dateStr == null) return 'Unknown date';
    try {
      final date = DateTime.parse(dateStr);
      final months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return '${months[date.month - 1]} ${date.day}, ${date.year} • ${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}';
    } catch (e) {
      return dateStr;
    }
  }

  @override
  Widget build(BuildContext context) {
    final d = data;
    final items = d['items'] as List? ?? [];
    final orderId = d['order_id']?.toString() ?? '-';
    final dateStr = d['delivery_date'] ?? d['order_date'];

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border.withOpacity(0.6)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: onTap,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Order #$orderId',
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w700, // Matched card title weight
                                color: AppColors.textPrimary,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              _formatDate(dateStr),
                              style: const TextStyle(
                                fontSize: 12,
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: AppColors.success.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: const Text(
                          'DELIVERED',
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w800,
                            color: AppColors.success,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Icon(
                        isExpanded ? Icons.keyboard_arrow_up_rounded : Icons.keyboard_arrow_down_rounded,
                        color: AppColors.textSecondary,
                      ),
                    ],
                  ),

                  AnimatedCrossFade(
                    firstChild: const SizedBox(width: double.infinity, height: 0),
                    secondChild: Padding(
                      padding: const EdgeInsets.only(top: 16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Divider(height: 1, color: AppColors.border),
                          const SizedBox(height: 16),
                          _buildDetailRow(
                            icon: Icons.storefront_rounded,
                            label: 'Retailer',
                            value: d['retailer_name']?.toString() ?? 'Unknown Retailer',
                          ),
                          const SizedBox(height: 12),
                          _buildDetailRow(
                            icon: Icons.location_on_outlined,
                            label: 'Address',
                            value: d['retailer_address']?.toString() ?? 'No address provided',
                          ),
                          const SizedBox(height: 12),
                          _buildDetailRow(
                            icon: Icons.inventory_2_outlined,
                            label: 'Items',
                            value: '${items.length} items delivered',
                          ),
                        ],
                      ),
                    ),
                    crossFadeState: isExpanded ? CrossFadeState.showSecond : CrossFadeState.showFirst,
                    duration: const Duration(milliseconds: 300),
                    sizeCurve: Curves.easeInOut,
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDetailRow({required IconData icon, required String label, required String value}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(6),
          decoration: BoxDecoration(
            color: AppColors.canvas,
            borderRadius: BorderRadius.circular(6),
          ),
          child: Icon(icon, size: 14, color: AppColors.textSecondary),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w500, // Matched body text weight
                  color: AppColors.textPrimary,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}