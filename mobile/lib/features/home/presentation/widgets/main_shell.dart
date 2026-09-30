import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../screens/home_screen.dart';
import '../screens/orders_screen.dart';

class MainShell extends StatefulWidget {
  final Widget child;

  const MainShell({Key? key, required this.child}) : super(key: key);

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _currentIndex = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: widget.child,
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (index) {
          setState(() => _currentIndex = index);
          switch (index) {
            case 0:
              context.go('/home');
            case 1:
              context.go('/orders');
            case 2:
              context.go('/impact');
            case 3:
              context.go('/profile');
          }
        },
        destinations: [
          NavigationDestination(icon: Icon(Icons.home), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.restaurant), label: 'Explore'),
          NavigationDestination(icon: Icon(Icons.receipt), label: 'Orders'),
          NavigationDestination(icon: Icon(Icons.eco), label: 'Impact'),
          NavigationDestination(icon: Icon(Icons.person), label: 'Profile'),
        ],
      ),
    );
  }
}