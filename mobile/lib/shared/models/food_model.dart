class FoodCategory {
  final int id;
  final String name;
  final String? slug;

  FoodCategory({
    required this.id,
    required this.name,
    this.slug,
  });

  factory FoodCategory.fromJson(Map<String, dynamic> json) {
    return FoodCategory(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
    );
  }
}

class FoodPartner {
  final int id;
  final String businessName;
  final double? latitude;
  final double? longitude;

  FoodPartner({
    required this.id,
    required this.businessName,
    this.latitude,
    this.longitude,
  });

  factory FoodPartner.fromJson(Map<String, dynamic> json) {
    return FoodPartner(
      id: json['id'],
      businessName: json['business_name'],
      latitude: (json['latitude'] as num?)?.toDouble(),
      longitude: (json['longitude'] as num?)?.toDouble(),
    );
  }
}

class Food {
  final int id;
  final String name;
  final String description;
  final int originalPrice;
  final int rescuePrice;
  final int discountPercent;
  final int stock;
  final String? image;
  final String pickupStart;
  final String pickupEnd;
  final String status;
  final FoodCategory? category;
  final FoodPartner? partner;

  Food({
    required this.id,
    required this.name,
    required this.description,
    required this.originalPrice,
    required this.rescuePrice,
    required this.discountPercent,
    required this.stock,
    this.image,
    required this.pickupStart,
    required this.pickupEnd,
    required this.status,
    this.category,
    this.partner,
  });

  factory Food.fromJson(Map<String, dynamic> json) {
    return Food(
      id: json['id'],
      name: json['name'],
      description: json['description'] ?? '',
      originalPrice: json['original_price'],
      rescuePrice: json['rescue_price'],
      discountPercent: json['discount_percent'],
      stock: json['stock'],
      image: json['image'],
      pickupStart: json['pickup_start'] ?? '',
      pickupEnd: json['pickup_end'] ?? '',
      status: json['status'] ?? '',
      category: json['category'] != null
          ? FoodCategory.fromJson(json['category'])
          : null,
      partner: json['partner'] != null
          ? FoodPartner.fromJson(json['partner'])
          : null,
    );
  }
}