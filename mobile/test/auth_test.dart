import 'package:flutter_test/flutter_test.dart';

import 'package:foodrescue/features/auth/domain/auth_notifier.dart';
import 'package:foodrescue/shared/models/auth_model.dart';

class _FakeAuthRepository extends AuthRepository {
  _FakeAuthRepository() : super(apiClient: null!);

  @override
  Future<bool> isAuthenticated() async => false;

  @override
  Future<User?> getStoredUser() async => null;

  @override
  Future<void> logout() async {}
}

void main() {
  test('auth state initializes as unauthenticated', () {
    final auth = AuthState();
    expect(auth.isAuthenticated, isFalse);
    expect(auth.user, isNull);
  });

  test('auth state copyWith updates fields', () {
    final auth1 = AuthState();
    final auth2 = auth1.copyWith(isLoading: true);
    expect(auth2.isLoading, isTrue);
    expect(auth1.isLoading, isFalse);
  });
}