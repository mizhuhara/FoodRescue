import 'package:flutter_test/flutter_test.dart';

import 'package:foodrescue/features/auth/domain/auth_notifier.dart';
import 'package:foodrescue/shared/models/auth_model.dart';

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

  test('auth state copyWith preserves user', () {
    const user = User(id: 1, name: 'Andi', email: 'a@b.co', role: 'CUSTOMER');
    final auth1 = AuthState(user: user, isAuthenticated: true);
    final auth2 = auth1.copyWith(error: 'boom');
    expect(auth2.user, same(user));
    expect(auth2.error, 'boom');
    expect(auth2.isAuthenticated, isTrue);
  });
}