import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/storage/storage_service.dart';
import '../../shared/models/auth_model.dart';

class AuthRepository {
  final ApiClient _apiClient;

  AuthRepository({required ApiClient apiClient}) : _apiClient = apiClient;

  Future<AuthResponse> register({
    required String name,
    required String email,
    required String password,
  }) async {
    final response = await _apiClient.post(
      '/auth/register',
      data: {
        'name': name,
        'email': email,
        'password': password,
        'role': 'CUSTOMER',
      },
    );

    if (response.statusCode == 201) {
      final authResponse = AuthResponse.fromJson(response.data);
      await _saveAuth(authResponse);
      return authResponse;
    }

    throw Exception(response.data['message'] ?? 'Registration failed');
  }

  Future<AuthResponse> login({
    required String email,
    required String password,
  }) async {
    final response = await _apiClient.post(
      '/auth/login',
      data: {
        'email': email,
        'password': password,
      },
    );

    if (response.statusCode == 200) {
      final authResponse = AuthResponse.fromJson(response.data);
      await _saveAuth(authResponse);
      return authResponse;
    }

    throw Exception(response.data['message'] ?? 'Login failed');
  }

  Future<void> logout() async {
    try {
      await _apiClient.post('/auth/logout');
    } catch (e) {
      // ignore errors on logout
    }
    await StorageService.instance.clear();
  }

  Future<User?> getStoredUser() async {
    final userJson = StorageService.instance.getUser();
    if (userJson == null) return null;
    return User.fromJson(jsonDecode(userJson));
  }

  Future<bool> isAuthenticated() async {
    final token = await StorageService.instance.getAuthToken();
    return token != null;
  }

  Future<void> _saveAuth(AuthResponse auth) async {
    await StorageService.instance.setAuthToken(auth.token);
    await StorageService.instance.setUser(jsonEncode(auth.user.toJson()));
  }
}

final authRepositoryProvider = Provider((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return AuthRepository(apiClient: apiClient);
});
