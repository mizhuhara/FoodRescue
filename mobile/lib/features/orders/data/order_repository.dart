import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../shared/models/order_model.dart';

class OrderRepository {
  final ApiClient _apiClient;

  OrderRepository({required ApiClient apiClient}) : _apiClient = apiClient;

  Future<List<OrderModel>> getOrders() async {
    final response = await _apiClient.get('/orders');
    return (response.data['data'] as List)
        .map((item) => OrderModel.fromJson(item))
        .toList();
  }

  Future<OrderModel> createOrder({
    required int foodId,
    required int quantity,
  }) async {
    final response = await _apiClient.post(
      '/orders',
      data: {
        'items': [
          {'food_id': foodId, 'quantity': quantity},
        ],
      },
    );
    return OrderModel.fromJson(response.data['data']);
  }

  Future<OrderModel> cancelOrder(int id) async {
    final response = await _apiClient.post('/orders/$id/cancel');
    return OrderModel.fromJson(response.data['data']);
  }
}

final orderRepositoryProvider = Provider((ref) {
  return OrderRepository(apiClient: ref.watch(apiClientProvider));
});

final ordersProvider = FutureProvider.autoDispose<List<OrderModel>>((ref) async {
  return ref.watch(orderRepositoryProvider).getOrders();
});