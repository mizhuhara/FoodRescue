
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/models/food_model.dart';
import '../../home/data/food_repository.dart';
import '../../home/presentation/widgets/food_card.dart';
import '../../orders/data/order_repository.dart';
import '../../orders/presentation/widgets/order_success_dialog.dart';

class FoodDetailScreen extends ConsumerStatefulWidget {
  final int foodId;

  const FoodDetailScreen({Key? key, required this.foodId}) : super(key: key);

  @override
  ConsumerState<FoodDetailScreen> createState() => _FoodDetailScreenState();
}

class _FoodDetailScreenState extends ConsumerState<FoodDetailScreen> {
  int _quantity = 1;
  Food? _food;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadFood();
  }

  Future<void> _loadFood() async {
    final repo = ref.read(foodRepositoryProvider);
    final foods = await repo.getFoods();
    setState(() {
      _food = foods.firstWhere((f) => f.id == widget.foodId, orElse: () => null);
      _loading = false;
    });
  }

  Future<void> _placeOrder() async {
    if (_food == null) return;
    final repo = ref.read(orderRepositoryProvider);
    final order = await repo.createOrder(foodId: _food!.id, quantity: _quantity);
    if (!mounted) return;
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Order placed'),
        content: Text('Order #${order.orderNumber} created.'),
        actions: [
          TextButton(onPressed: () => context.pop(), child: const Text('OK')),
        ],
      ),
    ).then((_) => context.go('/orders'));
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(appBar: AppBar(title: const Text('Food')),
        body: const Center(child: CircularProgressIndicator()));
    }
    if (_food == null) {
      return Scaffold(appBar: AppBar(title: const Text('Food')),
        body: const Center(child: Text('Food not found')));
    }
    final food = _food!;
    return Scaffold(
      appBar: AppBar(title: Text(food.name)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          FoodCard(food: food),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Quantity'),
              Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.remove),
                    onPressed: _quantity > 1 ? () => setState(() => _quantity--) : null,
                  ),
                  Text('$_quantity'),
                  IconButton(
                    icon: const Icon(Icons.add),
                    onPressed: () => setState(() => _quantity++),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: _placeOrder,
            child: const Text('Place Order'),
          ),
        ],
      ),
    );
  }
}
