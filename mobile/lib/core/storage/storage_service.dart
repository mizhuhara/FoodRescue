import 'dart:async';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

class StorageService {
  late SharedPreferences _prefs;
  late FlutterSecureStorage _secureStorage;

  static StorageService? _instance;

  StorageService._();

  static StorageService get instance => _instance ??= StorageService._();

  Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
    _secureStorage = const FlutterSecureStorage();
  }

  Future<void> setAuthToken(String token) async {
    await _secureStorage.write(key: 'auth_token', value: token);
  }

  Future<String?> getAuthToken() async {
    return _secureStorage.read(key: 'auth_token');
  }

  Future<void> deleteAuthToken() async {
    await _secureStorage.delete(key: 'auth_token');
  }

  Future<void> setUser(String userJson) async {
    await _prefs.setString('user', userJson);
  }

  String? getUser() {
    return _prefs.getString('user');
  }

  Future<void> deleteUser() async {
    await _prefs.remove('user');
  }

  Future<void> clear() async {
    await _secureStorage.deleteAll();
    await _prefs.clear();
  }
}
