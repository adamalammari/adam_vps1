/// App Configuration
/// Contains API and WebSocket URLs
class AppConfig {
  // API Configuration
  static const String apiBaseUrl = 'http://10.0.2.2'; // For Android Emulator (localhost)
  // static const String apiBaseUrl = 'http://192.168.1.100'; // For physical device
  // static const String apiBaseUrl = 'https://yourdomain.com'; // For production
  
  static const String apiUrl = '$apiBaseUrl/api';

  // WebSocket Configuration
  static const String wsUrl = 'ws://10.0.2.2:8080'; // For Android Emulator
  // static const String wsUrl = 'ws://192.168.1.100:8080'; // For physical device
  // static const String wsUrl = 'wss://yourdomain.com/ws'; // For production

  // Cache Limits
  static const int maxCachedMessages = 200;
  static const int maxCachedProducts = 100;

  // Pagination
  static const int messagesPageSize = 50;

  // Timeouts
  static const Duration apiTimeout = Duration(seconds: 30);
  static const Duration wsReconnectDelay = Duration(seconds: 3);

  // App Info
  static const String appName = 'Flutter Chat & Products';
  static const String appVersion = '1.0.0';
}
