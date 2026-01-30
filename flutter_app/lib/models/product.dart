import 'package:hive/hive.dart';

part 'product.g.dart';

@HiveType(typeId: 3)
class Product extends HiveObject {
  @HiveField(0)
  final int id;

  @HiveField(1)
  final String name;

  @HiveField(2)
  final double price;

  @HiveField(3)
  final String? description;

  @HiveField(4)
  final String? imageUrl;

  @HiveField(5)
  final String? category;

  @HiveField(6)
  final String? contactLink;

  @HiveField(7)
  final DateTime createdAt;

  Product({
    required this.id,
    required this.name,
    required this.price,
    this.description,
    this.imageUrl,
    this.category,
    this.contactLink,
    required this.createdAt,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'] as int,
      name: json['name'] as String,
      price: (json['price'] as num).toDouble(),
      description: json['description'] as String?,
      imageUrl: json['image_url'] as String?,
      category: json['category'] as String?,
      contactLink: json['contact_link'] as String?,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'] as String)
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'price': price,
      'description': description,
      'image_url': imageUrl,
      'category': category,
      'contact_link': contactLink,
      'created_at': createdAt.toIso8601String(),
    };
  }

  @override
  String toString() => 'Product(id: $id, name: $name, price: $price)';
}
