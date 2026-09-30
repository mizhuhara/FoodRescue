import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../features/auth/domain/auth_notifier.dart';
import '../../features/auth/presentation/screens/login_screen.dart';
import '../../features/auth/presentation/screens/register_screen.dart';
import '../../features/home/presentation/screens/home_screen.dart';
import '../../features/home/presentation/widgets/main_shell.dart';
import '../../features/home/presentation/screens/food_detail_screen.dart';

final routerProvider = Provider((ref) {
  final authState = ref.watch(authNotifierProvider);

  return GoRouter(
    initialLocation: authState.isAuthenticated ? '/home' : '/login',
    redirect: (context, state) {
      final isAuth = authState.isAuthenticated;
      final isAuthRoute = state.matchedLocation == '/login' ||
          state.matchedLocation == '/register';

      if (!isAuth && !isAuthRoute) {
        return '/login';
      }

      if (isAuth && isAuthRoute) {
        return '/home';
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => LoginScreen(),
      ),
      GoRoute(
        path: '/register',
        builder: (context, state) => RegisterScreen(),
      ),
      GoRoute(
        path: '/home',
        builder: (context, state) => MainShell(
          child: HomeScreen(),
        ),
      ),
      GoRoute(
        path: '/orders',
        builder: (context, state) => MainShell(
          child: OrdersScreen(),
        ),
      ),
      GoRoute(
        path: '/impact',
        builder: (context, state) => MainShell(
          child: Scaffold(
            appBar: AppBar(title: const Text('Impact')),
            body: const Center(child: Text('Impact coming soon')),
          ),
        ),
      ),
      GoRoute(
        path: '/profile',
        builder: (context, state) => MainShell(
          child: Scaffold(
            appBar: AppBar(title: const Text('Profile')),
            body: const Center(child: Text('Profile coming soon')),
          ),
        ),
      ),
    ],
  );
});
