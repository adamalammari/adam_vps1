import 'package:hive_flutter/hive_flutter.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_chat_products/models/user.dart';
import 'package:flutter_chat_products/models/message.dart';
import 'package:flutter_chat_products/models/product.dart';
import 'package:flutter_chat_products/core/config.dart';

class StorageService {
  static const String _userBoxName = 'user';
  static const String _messagesBoxName = 'messages';
  static const String _productsBoxName = 'products';
  static const String _tokenKey = 'auth_token';

  final _secureStorage = const FlutterSecureStorage();

  /// Initialize Hive
  Future<void> init() async {
    await Hive.initFlutter();

    // Register adapters
    if (!Hive.isAdapterRegistered(0)) {
      Hive.registerAdapter(UserAdapter());
    }
    if (!Hive.isAdapterRegistered(1)) {
      Hive.registerAdapter(MessageAdapter());
    }
    if (!Hive.isAdapterRegistered(2)) {
      Hive.registerAdapter(MessageTypeAdapter());
    }
    if (!Hive.isAdapterRegistered(3)) {
      Hive.registerAdapter(ProductAdapter());
    }

    // Open boxes
    await Hive.openBox<User>(_userBoxName);
    await Hive.openBox<Message>(_messagesBoxName);
    await Hive.openBox<Product>(_productsBoxName);
  }

  // ==================== User & Token ====================

  Future<void> saveUser(User user) async {
    final box = Hive.box<User>(_userBoxName);
    await box.put('current_user', user);
  }

  User? getUser() {
    final box = Hive.box<User>(_userBoxName);
    return box.get('current_user');
  }

  Future<void> saveToken(String token) async {
    await _secureStorage.write(key: _tokenKey, value: token);
  }

  Future<String?> getToken() async {
    return await _secureStorage.read(key: _tokenKey);
  }

  Future<void> clearUser() async {
    final box = Hive.box<User>(_userBoxName);
    await box.clear();
    await _secureStorage.delete(key: _tokenKey);
  }

  // ==================== Messages ====================

  Future<void> saveMessages(List<Message> messages) async {
    final box = Hive.box<Message>(_messagesBoxName);
    
    // Clear old messages if exceeding limit
    if (box.length + messages.length > AppConfig.maxCachedMessages) {
      await box.clear();
    }

    for (final message in messages) {
      await box.put(message.id, message);
    }
  }

  Future<void> saveMessage(Message message) async {
    final box = Hive.box<Message>(_messagesBoxName);
    await box.put(message.id, message);

    // Enforce limit
    if (box.length > AppConfig.maxCachedMessages) {
      // Remove oldest messages
      final keys = box.keys.toList()..sort();
      final toRemove = keys.take(box.length - AppConfig.maxCachedMessages);
      for (final key in toRemove) {
        await box.delete(key);
      }
    }
  }

  List<Message> getMessages() {
    final box = Hive.box<Message>(_messagesBoxName);
    return box.values.toList()
      ..sort((a, b) => a.id.compareTo(b.id));
  }

  Future<void> clearMessages() async {
    final box = Hive.box<Message>(_messagesBoxName);
    await box.clear();
  }

  // ==================== Products ====================

  Future<void> saveProducts(List<Product> products) async {
    final box = Hive.box<Product>(_productsBoxName);
    await box.clear();

    // Save only up to max limit
    final toSave = products.take(AppConfig.maxCachedProducts);
    for (final product in toSave) {
      await box.put(product.id, product);
    }
  }

  List<Product> getProducts() {
    final box = Hive.box<Product>(_productsBoxName);
    return box.values.toList()
      ..sort((a, b) => b.createdAt.compareTo(a.createdAt));
  }

  Future<void> clearProducts() async {
    final box = Hive.box<Product>(_productsBoxName);
    await box.clear();
  }

  // ==================== Clear All ====================

  Future<void> clearAll() async {
    await clearUser();
    await clearMessages();
    await clearProducts();
  }
}
