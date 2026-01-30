import 'package:hive/hive.dart';

part 'message.g.dart';

@HiveType(typeId: 1)
class Message extends HiveObject {
  @HiveField(0)
  final int id;

  @HiveField(1)
  final int userId;

  @HiveField(2)
  final String username;

  @HiveField(3)
  final MessageType type;

  @HiveField(4)
  final String content;

  @HiveField(5)
  final String? clientMsgId;

  @HiveField(6)
  final DateTime createdAt;

  @HiveField(7)
  final bool isSending;

  Message({
    required this.id,
    required this.userId,
    required this.username,
    required this.type,
    required this.content,
    this.clientMsgId,
    required this.createdAt,
    this.isSending = false,
  });

  factory Message.fromJson(Map<String, dynamic> json) {
    return Message(
      id: json['id'] as int,
      userId: json['user_id'] as int,
      username: json['username'] as String,
      type: _parseMessageType(json['type'] as String),
      content: json['content'] as String,
      clientMsgId: json['client_msg_id'] as String?,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'] as String)
          : DateTime.now(),
      isSending: false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'username': username,
      'type': type.toString().split('.').last,
      'content': content,
      'client_msg_id': clientMsgId,
      'created_at': createdAt.toIso8601String(),
    };
  }

  static MessageType _parseMessageType(String type) {
    switch (type.toLowerCase()) {
      case 'text':
        return MessageType.text;
      case 'image':
        return MessageType.image;
      case 'video':
        return MessageType.video;
      default:
        return MessageType.text;
    }
  }

  Message copyWith({
    int? id,
    bool? isSending,
  }) {
    return Message(
      id: id ?? this.id,
      userId: userId,
      username: username,
      type: type,
      content: content,
      clientMsgId: clientMsgId,
      createdAt: createdAt,
      isSending: isSending ?? this.isSending,
    );
  }

  @override
  String toString() =>
      'Message(id: $id, username: $username, type: $type, content: ${content.substring(0, content.length > 20 ? 20 : content.length)}...)';
}

@HiveType(typeId: 2)
enum MessageType {
  @HiveField(0)
  text,
  @HiveField(1)
  image,
  @HiveField(2)
  video,
}
