import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_chat_products/models/user.dart';
import 'package:flutter_chat_products/services/api_service.dart';
import 'package:flutter_chat_products/services/storage_service.dart';

// Services providers
final apiServiceProvider = Provider((ref) => ApiService());
final storageServiceProvider = Provider((ref) => StorageService());

// Auth state
final authStateProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(
    ref.read(apiServiceProvider),
    ref.read(storageServiceProvider),
  );
});

class AuthState {
  final User? user;
  final String? token;
  final bool isLoading;
  final String? error;

  AuthState({
    this.user,
    this.token,
    this.isLoading = false,
    this.error,
  });

  bool get isAuthenticated => user != null && token != null;

  AuthState copyWith({
    User? user,
    String? token,
    bool? isLoading,
    String? error,
  }) {
    return AuthState(
      user: user ?? this.user,
      token: token ?? this.token,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final ApiService _apiService;
  final StorageService _storageService;

  AuthNotifier(this._apiService, this._storageService) : super(AuthState()) {
    _checkAuthStatus();
  }

  Future<void> _checkAuthStatus() async {
    final user = _storageService.getUser();
    final token = await _storageService.getToken();

    if (user != null && token != null) {
      _apiService.setToken(token);
      state = state.copyWith(user: user, token: token);
    }
  }

  Future<bool> login(String username) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final result = await _apiService.guestLogin(username);

      if (result['success'] == true) {
        final user = result['user'] as User;
        final token = result['token'] as String;

        // Save to storage
        await _storageService.saveUser(user);
        await _storageService.saveToken(token);

        // Update API service
        _apiService.setToken(token);

        state = state.copyWith(
          user: user,
          token: token,
          isLoading: false,
        );

        return true;
      } else {
        state = state.copyWith(
          isLoading: false,
          error: result['message'] as String? ?? 'Login failed',
        );
        return false;
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: 'Network error: ${e.toString()}',
      );
      return false;
    }
  }

  Future<void> logout() async {
    await _storageService.clearAll();
    _apiService.setToken(null);
    state = AuthState();
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}
