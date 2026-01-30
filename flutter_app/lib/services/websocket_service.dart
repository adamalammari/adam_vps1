import 'dart:async';
import 'dart:convert';
import 'package:web_socket_channel/web_socket_channel.dart';
import 'package:flutter_chat_products/core/config.dart';
import 'package:flutter_chat_products/models/message.dart';

enum ConnectionStatus { disconnected, connecting, connected, reconnecting }

class WebSocketService {
  WebSocketChannel? _channel;
  ConnectionStatus _status = ConnectionStatus.disconnected;
  String? _token;

  // Streams
  final _statusController = StreamController<ConnectionStatus>.broadcast();
  final _messageController = StreamController<Map<String, dynamic>>.broadcast();
  
  Stream<ConnectionStatus> get statusStream => _statusController.stream;
  Stream<Map<String, dynamic>> get messageStream => _messageController.stream;
  ConnectionStatus get status => _status;

  // Reconnection
  Timer? _reconnectTimer;
  bool _shouldReconnect = true;
  int _reconnectAttempts = 0;
  static const int maxReconnectAttempts = 5;

  void connect(String token) {
    _token = token;
    _shouldReconnect = true;
    _reconnectAttempts = 0;
    _connect();
  }

  void _connect() {
    try {
      _updateStatus(ConnectionStatus.connecting);
      
      _channel = WebSocketChannel.connect(Uri.parse(AppConfig.wsUrl));
      
      // Listen to messages
      _channel!.stream.listen(
        (data) {
          _onMessage(data);
          _reconnectAttempts = 0; // Reset on successful message
        },
        onError: (error) {
          print('WebSocket error: $error');
          _handleDisconnect();
        },
        onDone: () {
          print('WebSocket connection closed');
          _handleDisconnect();
        },
      );

      // Send join event after connection
      Future.delayed(const Duration(milliseconds: 500), () {
        if (_status == ConnectionStatus.connecting) {
          _sendJoin();
        }
      });

    } catch (e) {
      print('WebSocket connection error: $e');
      _handleDisconnect();
    }
  }

  void _handleDisconnect() {
    _updateStatus(ConnectionStatus.disconnected);
    _channel = null;

    if (_shouldReconnect && _reconnectAttempts < maxReconnectAttempts) {
      _reconnectAttempts++;
      _updateStatus(ConnectionStatus.reconnecting);
      
      print('Reconnecting in ${AppConfig.wsReconnectDelay.inSeconds} seconds (attempt $_reconnectAttempts)...');
      
      _reconnectTimer?.cancel();
      _reconnectTimer = Timer(AppConfig.wsReconnectDelay, () {
        if (_shouldReconnect) {
          _connect();
        }
      });
    }
  }

  void _sendJoin() {
    if (_token != null) {
      send({
        'type': 'join',
        'token': _token,
      });
    }
  }

  void _onMessage(dynamic data) {
    try {
      final message = json.decode(data as String);
      final type = message['type'] as String?;

      switch (type) {
        case 'joined':
          _updateStatus(ConnectionStatus.connected);
          print('Joined chat successfully. Online: ${message['online_count']}');
          break;

        case 'new_message':
        case 'message_ack':
        case 'user_typing':
        case 'user_joined':
        case 'user_left':
        case 'pong':
          _messageController.add(message);
          break;

        case 'error':
          print('WebSocket error message: ${message['message']}');
          _messageController.add(message);
          break;

        default:
          print('Unknown message type: $type');
      }
    } catch (e) {
      print('Error parsing WebSocket message: $e');
    }
  }

  void _updateStatus(ConnectionStatus newStatus) {
    _status = newStatus;
    _statusController.add(newStatus);
  }

  /// Send message
  void sendMessage({
    required String messageType,
    required String content,
    String? clientMsgId,
  }) {
    send({
      'type': 'message',
      'token': _token,
      'messageType': messageType,
      'content': content,
      'clientMsgId': clientMsgId,
    });
  }

  /// Send typing indicator
  void sendTyping(bool isTyping) {
    send({
      'type': 'typing',
      'token': _token,
      'isTyping': isTyping,
    });
  }

  /// Send ping (keepalive)
  void sendPing() {
    send({'type': 'ping'});
  }

  /// Send generic message
  void send(Map<String, dynamic> data) {
    if (_channel != null && _status == ConnectionStatus.connected) {
      _channel!.sink.add(json.encode(data));
    } else {
      print('Cannot send message: WebSocket not connected');
    }
  }

  /// Disconnect
  void disconnect() {
    _shouldReconnect = false;
    _reconnectTimer?.cancel();
    _channel?.sink.close();
    _channel = null;
    _updateStatus(ConnectionStatus.disconnected);
  }

  /// Dispose
  void dispose() {
    disconnect();
    _statusController.close();
    _messageController.close();
  }
}
