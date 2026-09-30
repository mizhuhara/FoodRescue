class AppConstants {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );

  static const String appName = 'FoodRescue';
  static const String appVersion = '1.0.0';

  static const Duration connectTimeout = Duration(seconds: 10);
  static const Duration receiveTimeout = Duration(seconds: 10);
  static const Duration sendTimeout = Duration(seconds: 10);

  static const int defaultPageSize = 15;
  static const int maxImageSize = 2 * 1024 * 1024;

  static const String storageKeyToken = 'auth_token';
  static const String storageKeyUser = 'auth_user';
  static const String storageKeyLocation = 'user_location';

  static const String timezone = 'Asia/Makassar';

  static const Map<String, String> httpHeaders = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };
}
