import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../shared/models/order_model.dart';
import '../data/order_repository.dart';

class OrdersScreen extends ConsumerWidget {
  const OrdersScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final orders = ref.watch(ordersProvider);

    return Scaffold(
      appBar: AppBar(title: Text('My Orders')),
      body: orders.when(
        data: (list) => list.isEmpty
            ? _EmptyOrders()
            : ListView.separated(
                padding: EdgeInsets.all(16),
                itemCount: list.length,
                separatorBuilder: (_, __) => SizedBox(height: 12),
                itemBuilder: (context, index) =>
                    _OrderCard(order: list[index]),
              ),
        loading: () => Center(child: CircularProgressIndicator()),
        error: (err, _) => Center(child: Text('Error: $err')),
      ),
    );
  }
}

class _OrderCard extends StatelessWidget {
  final OrderModel order;

  const _OrderCard({required this.order});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  order.orderNumber,
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                ),
                _StatusBadge(status: order.status),
              ],
            ),
            SizedBox(height: 8),
            Text(
              order.items.map((i) => '${i.quantity}x ${i.foodName}').join(', '),
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            SizedBox(height: 8),
            Text(
              'Rp${order.total}',
              style: TextStyle(
                color: Theme.of(context).colorScheme.primary,
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
            if (order.pickupCode != null) ...[
              SizedBox(height: 8),
              Container(
                padding: EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.grey[100],
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Icon(Icons.qr_code, color: Colors.black54),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Pickup code: ${order.pickupCode}',
                        style: TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String status;

  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'COMPLETED' => Colors.green,
      'PENDING' => Colors.orange,
      'CANCELLED' => Colors.red,
      'EXPIRED' => Colors.grey,
      _ => Colors.blue,
    };

    return Container(
      padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        status,
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }
}

class _EmptyOrders extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.receipt_long, size: 64, color: Colors.grey[400]),
          SizedBox(height: 16),
          Text('No orders yet', style: Theme.of(context).textTheme.headlineSmall),
          SizedBox(height: 8),
          Text(
            'Order rescued food to see it here',
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ],
      ),
    );
  }
}