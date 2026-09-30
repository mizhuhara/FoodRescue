class OrderItemModel {
  final int foodId;
  final String foodName;
  final int quantity;
  final int unitPrice;
  final int subtotal;

  OrderItemModel({
    required this.foodId,
    required this.foodName,
    required this.quantity,
    required this.unitPrice,
    required this.subtotal,
  });

  factory OrderItemModel.fromJson(Map<String, dynamic> json) {
    return OrderItemModel(
      foodId: json['food_id'],
      foodName: json['food']['name'] ?? 'Unknown',
      quantity: json['quantity'],
      unitPrice: json['unit_price'],
      subtotal: json['subtotal'],
    );
  }
}

class OrderModel {
  final int id;
  final String orderNumber;
  final String status;
  final int total;
  final String? pickupCode;
  final DateTime? pickupCompletedAt;
  final DateTime createdAt;
  final List<OrderItemModel> items;
  final Map<String, dynamic>? partner;

  OrderModel({
    required this.id,
    required this.orderNumber,
    required this.status,
    required this.total,
    this.pickupCode,
    this.pickupCompletedAt,
    required this.createdAt,
    required this.items,
    this.partner,
  });

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    return OrderModel(
      id: json['id'],
      orderNumber: json['order_number'],
      status: json['status'],
      total: json['total'],
      pickupCode: json['pickup_code'],
      pickupCompletedAt: json['pickup_completed_at'] != null
          ? DateTime.parse(json['pickup_completed_at'])
          : null,
      createdAt: DateTime.parse(json['created_at']),
      items: (json['items'] as List)
          .map((item) => OrderItemModel.fromJson(item))
          .toList(),
      partner: json['partner'],
    );
  }
}
