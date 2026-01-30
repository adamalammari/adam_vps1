import 'dart:async';
import 'dart:io';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_chat_products/models/message.dart';
import 'package:flutter_chat_products/services/api_service.dart';
import 'package:flutter_chat_products/services/websocket_service.dart';
import 'package:flutter_chat_products/services/storage_service.dart';
import 'package:flutter_chat_products/providers/auth_provider.dart';
import 'package:uuid/uuid.dart';

final chatProvider = StateNotifierProvider<ChatNotifier, ChatState>((ref) {
  return ChatNotifier(
    ref.read(apiServiceProvider),
    ref.read(storageServiceProvider),
    ref.watch(authStateProvider),
  );
});

class ChatState {
  final List<Message> messages;
  final bool isLoading;
  final bool isLoadingMore;
  final bool hasMore;
  final ConnectionStatus connectionStatus;
  final Map<int, bool> typingUsers; // user_id -> isTyping
  final String? error;

  ChatState({
    this.messages = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.hasMore = true,
    this.connectionStatus = ConnectionStatus.disconnected,
    this.typingUsers = const {},
    this.error,
  });

  ChatState copyWith({
    List<Message>? messages,
    bool? isLoading,
    bool? isLoadingMore,
    bool? hasMore,
    ConnectionStatus? connectionStatus,
    Map<int, bool>? typingUsers,
    String? error,
  }) {
    return ChatState(
      messages: messages ?? this.messages,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      hasMore: hasMore ?? this.hasMore,
      connectionStatus: connectionStatus ?? this.connectionStatus,
      typingUsers: typingUsers ?? this.typingUsers,
      error: error,
    );
  }
}

class ChatNotifier extends StateNotifier<ChatState> {
  final ApiService _apiService;
  final StorageService _storageService;
  final AuthState _authState;
  final WebSocketService _wsService = WebSocketService();

  StreamSubscription? _wsStatusSubscription;
  StreamSubscription? _wsMessageSubscription;
  Timer? _typingTimer;

  ChatNotifier(this._apiService, this._storageService, this._authState)
      : super(ChatState()) {
    _init();
  }

  void _init() {
    // Load cached messages
    final cachedMessages = _storageService.getMessages();
    if (cachedMessages.isNotEmpty) {
      state = state.copyWith(messages: cachedMessages);
    }

    // Connect WebSocket if authenticated
    if (_authState.isAuthenticated && _authState.token != null) {
      _connectWebSocket(_authState.token!);
    }

    // Fetch messages from API
    fetchMessages();
  }

  void _connectWebSocket(String token) {
    _wsService.connect(token);

    // Listen to connection status
    _wsStatusSubscription = _wsService.statusStream.listen((status) {
      state = state.copyWith(connectionStatus: status);
    });

    // Listen to messages
    _wsMessageSubscription = _wsService.messageStream.listen((data) {
      _handleWebSocketMessage(data);
    });
  }

  void _handleWebSocketMessage(Map<String, dynamic> data) {
    final type = data['type'] as String?;

    switch (type) {
      case 'new_message':
        final message = Message.fromJson(data['message']);
        _addMessage(message);
        break;

      case 'message_ack':
        // Update temporary message with server ID
        final clientMsgId = data['client_msg_id'] as String?;
        final messageId = data['message_id'] as int?;
        if (clientMsgId != null && messageId != null) {
          _updateMessageId(clientMsgId, messageId);
        }
        break;

      case 'user_typing':
        final userId = data['user_id'] as int?;
        final isTyping = data['is_typing'] as bool? ?? false;
        if (userId != null) {
          _updateTypingStatus(userId, isTyping);
        }
        break;
    }
  }

  Future<void> fetchMessages({bool loadMore = false}) async {
    if (loadMore) {
      if (state.isLoadingMore || !state.hasMore) return;
      state = state.copyWith(isLoadingMore: true);
    } else {
      state = state.copyWith(isLoading: true);
    }

    try {
      final beforeId = loadMore && state.messages.isNotEmpty
          ? state.messages.first.id
          : null;

      final messages = await _apiService.getMessages(beforeId: beforeId);

      if (messages.isEmpty) {
        state = state.copyWith(hasMore: false);
      } else {
        final updatedMessages = loadMore
            ? [...messages, ...state.messages]
            : [...state.messages, ...messages];

        // Remove duplicates
        final uniqueMessages = <int, Message>{};
        for (final msg in updatedMessages) {
          uniqueMessages[msg.id] = msg;
        }
        final sortedMessages = uniqueMessages.values.toList()
          ..sort((a, b) => a.id.compareTo(b.id));

        state = state.copyWith(messages: sortedMessages);

        // Cache messages
        await _storageService.saveMessages(sortedMessages);
      }
    } catch (e) {
      state = state.copyWith(error: 'Failed to load messages');
    } finally {
      state = state.copyWith(isLoading: false, isLoadingMore: false);
    }
  }

  Future<void> sendTextMessage(String text) async {
    if (text.trim().isEmpty) return;

    final clientMsgId = const Uuid().v4();
    final tempMessage = Message(
      id: DateTime.now().millisecondsSinceEpoch, // Temporary ID
      userId: _authState.user!.id,
      username: _authState.user!.username,
      type: MessageType.text,
      content: text,
      clientMsgId: clientMsgId,
      createdAt: DateTime.now(),
      isSending: true,
    );

    // Add temporary message to UI
    state = state.copyWith(messages: [...state.messages, tempMessage]);

    // Send via WebSocket
    if (state.connectionStatus == ConnectionStatus.connected) {
      _wsService.sendMessage(
        messageType: 'text',
        content: text,
        clientMsgId: clientMsgId,
      );
    } else {
      // Fallback to REST API
      final message = await _apiService.sendMessage(
        type: 'text',
        content: text,
        clientMsgId: clientMsgId,
      );

      if (message != null) {
        _replaceMessage(tempMessage.id, message);
      }
    }
  }

  Future<void> sendMediaMessage(File file, MessageType type) async {
    // Upload file first
    final uploadResult = await _apiService.uploadFile(file);
    
    if (uploadResult == null) {
      state = state.copyWith(error: 'Failed to upload file');
      return;
    }

    final url = uploadResult['url'] as String;
    final fileType = uploadResult['type'] as String;

    // Send message with URL
    final clientMsgId = const Uuid().v4();
    
    if (state.connectionStatus == ConnectionStatus.connected) {
      _wsService.sendMessage(
        messageType: fileType,
        content: url,
        clientMsgId: clientMsgId,
      );
    } else {
      await _apiService.sendMessage(
        type: fileType,
        content: url,
        clientMsgId: clientMsgId,
      );
    }
  }

  void sendTyping(bool isTyping) {
    if (state.connectionStatus == ConnectionStatus.connected) {
      _wsService.sendTyping(isTyping);
    }
  }

  void _addMessage(Message message) {
    final messages = [...state.messages, message];
    state = state.copyWith(messages: messages);
    _storageService.saveMessage(message);
  }

  void _updateMessageId(String clientMsgId, int serverId) {
    final messages = state.messages.map((msg) {
      if (msg.clientMsgId == clientMsgId) {
        return msg.copyWith(id: serverId, isSending: false);
      }
      return msg;
    }).toList();

    state = state.copyWith(messages: messages);
  }

  void _replaceMessage(int tempId, Message newMessage) {
    final messages = state.messages.map((msg) {
      if (msg.id == tempId) {
        return newMessage;
      }
      return msg;
    }).toList();

    state = state.copyWith(messages: messages);
  }

  void _updateTypingStatus(int userId, bool isTyping) {
    final typingUsers = Map<int, bool>.from(state.typingUsers);
    
    if (isTyping) {
      typingUsers[userId] = true;
      // Auto-clear after 3 seconds
      _typingTimer?.cancel();
      _typingTimer = Timer(const Duration(seconds: 3), () {
        final updated = Map<int, bool>.from(state.typingUsers);
        updated.remove(userId);
        state = state.copyWith(typingUsers: updated);
      });
    } else {
      typingUsers.remove(userId);
    }

    state = state.copyWith(typingUsers: typingUsers);
  }

  @override
  void dispose() {
    _wsStatusSubscription?.cancel();
    _wsMessageSubscription?.cancel();
    _typingTimer?.cancel();
    _wsService.dispose();
    super.dispose();
  }
}
