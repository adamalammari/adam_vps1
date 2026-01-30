import 'package:dio/dio.dart';
import 'package:flutter_chat_products/core/config.dart';
import 'package:flutter_chat_products/models/user.dart';
import 'package:flutter_chat_products/models/message.dart';
import 'package:flutter_chat_products/models/product.dart';
import 'dart:io';

class ApiService {
  late final Dio _dio;
  String? _token;

  ApiService() {
    _dio = Dio(BaseOptions(
      baseUrl: AppConfig.apiUrl,
      connectTimeout: AppConfig.apiTimeout,
      receiveTimeout: AppConfig.apiTimeout,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    ));

    // Add interceptors
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) {
        if (_token != null) {
          options.headers['Authorization'] = 'Bearer $_token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        print('API Error: ${error.message}');
        return handler.next(error);
      },
    ));
  }

  void setToken(String? token) {
    _token = token;
  }

  /// Guest Login
  Future<Map<String, dynamic>> guestLogin(String username) async {
    try {
      final response = await _dio.post('/auth/guest-login', data: {
        'username': username,
      });

      if (response.data['success'] == true) {
        final data = response.data['data'];
        _token = data['token'];
        return {
          'success': true,
          'user': User.fromJson(data['user']),
          'token': data['token'],
        };
      }

      return {
        'success': false,
        'message': response.data['message'] ?? 'Login failed',
      };
    } on DioException catch (e) {
      return {
        'success': false,
        'message': e.response?.data['message'] ?? e.message ?? 'Network error',
        'errors': e.response?.data['errors'],
      };
    }
  }

  /// Get Messages with Pagination
  Future<List<Message>> getMessages({int? beforeId, int? limit}) async {
    try {
      final queryParams = <String, dynamic>{};
      if (beforeId != null) queryParams['before_id'] = beforeId;
      if (limit != null) queryParams['limit'] = limit;

      final response = await _dio.get('/chat/messages', queryParameters: queryParams);

      if (response.data['success'] == true) {
        final messagesData = response.data['data']['messages'] as List;
        return messagesData.map((json) => Message.fromJson(json)).toList();
      }

      return [];
    } catch (e) {
      print('Get messages error: $e');
      return [];
    }
  }

  /// Send Message (REST fallback)
  Future<Message?> sendMessage({
    required String type,
    required String content,
    String? clientMsgId,
  }) async {
    try {
      final response = await _dio.post('/chat/send', data: {
        'type': type,
        'content': content,
        'client_msg_id': clientMsgId,
      });

      if (response.data['success'] == true) {
        return Message.fromJson(response.data['data']['message']);
      }

      return null;
    } catch (e) {
      print('Send message error: $e');
      return null;
    }
  }

  /// Upload File
  Future<Map<String, dynamic>?> uploadFile(File file) async {
    try {
      final fileName = file.path.split('/').last;
      final formData = FormData.fromMap({
        'file': await MultipartFile.fromFile(file.path, filename: fileName),
      });

      final response = await _dio.post('/upload', data: formData);

      if (response.data['success'] == true) {
        return response.data['data'];
      }

      return null;
    } catch (e) {
      print('Upload file error: $e');
      return null;
    }
  }

  /// Get Products
  Future<List<Product>> getProducts({String? category}) async {
    try {
      final queryParams = <String, dynamic>{};
      if (category != null && category.isNotEmpty) {
        queryParams['category'] = category;
      }

      final response = await _dio.get('/products', queryParameters: queryParams);

      if (response.data['success'] == true) {
        final productsData = response.data['data']['products'] as List;
        return productsData.map((json) => Product.fromJson(json)).toList();
      }

      return [];
    } catch (e) {
      print('Get products error: $e');
      return [];
    }
  }

  /// Get Single Product
  Future<Product?> getProduct(int id) async {
    try {
      final response = await _dio.get('/products/$id');

      if (response.data['success'] == true) {
        return Product.fromJson(response.data['data']['product']);
      }

      return null;
    } catch (e) {
      print('Get product error: $e');
      return null;
    }
  }

  /// Get Categories
  Future<List<String>> getCategories() async {
    try {
      final response = await _dio.get('/products/categories');

      if (response.data['success'] == true) {
        return List<String>.from(response.data['data']['categories']);
      }

      return [];
    } catch (e) {
      print('Get categories error: $e');
      return [];
    }
  }
}
