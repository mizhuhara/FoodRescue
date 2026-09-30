import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../shared/models/food_model.dart';

class FoodRepository {
  final ApiClient _apiClient;

  FoodRepository({required ApiClient apiClient}) : _apiClient = apiClient;

  Future<List<Food>> getFoods({
    String? search,
    int? categoryId,
    String? sort,
    int page = 1,
  }) async {
    final query = <String, dynamic>{
      'page': page,
      'per_page': 15,
    };

    if (search != null && search.isNotEmpty) query['search'] = search;
    if (categoryId != null) query['category_id'] = categoryId;
    if (sort != null) query['sort'] = sort;

    final response = await _apiClient.get(
      '/foods',
      queryParameters: query,
    );

    return _extractFoods(response.data['data']);
  }

  List<Food> _extractFoods(dynamic data) {
    return (data as List)
        .map((item) => Food.fromJson(item))
        .toList(growable: false);
  }
}

final foodRepositoryProvider = Provider((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return FoodRepository(apiClient: apiClient);
});

final foodsProvider = FutureProvider.autoDispose<List<Food>>((ref) async {
  final repo = ref.watch(foodRepositoryProvider);
  return repo.getFoods();
});