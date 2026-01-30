import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_chat_products/models/product.dart';
import 'package:flutter_chat_products/services/api_service.dart';
import 'package:flutter_chat_products/services/storage_service.dart';
import 'package:flutter_chat_products/providers/auth_provider.dart';

final productsProvider = StateNotifierProvider<ProductsNotifier, ProductsState>((ref) {
  return ProductsNotifier(
    ref.read(apiServiceProvider),
    ref.read(storageServiceProvider),
  );
});

class ProductsState {
  final List<Product> products;
  final List<Product> filteredProducts;
  final List<String> categories;
  final String? selectedCategory;
  final String searchQuery;
  final bool isLoading;
  final String? error;

  ProductsState({
    this.products = const [],
    this.filteredProducts = const [],
    this.categories = const [],
    this.selectedCategory,
    this.searchQuery = '',
    this.isLoading = false,
    this.error,
  });

  ProductsState copyWith({
    List<Product>? products,
    List<Product>? filteredProducts,
    List<String>? categories,
    String? selectedCategory,
    String? searchQuery,
    bool? isLoading,
    String? error,
  }) {
    return ProductsState(
      products: products ?? this.products,
      filteredProducts: filteredProducts ?? this.filteredProducts,
      categories: categories ?? this.categories,
      selectedCategory: selectedCategory ?? this.selectedCategory,
      searchQuery: searchQuery ?? this.searchQuery,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class ProductsNotifier extends StateNotifier<ProductsState> {
  final ApiService _apiService;
  final StorageService _storageService;

  ProductsNotifier(this._apiService, this._storageService)
      : super(ProductsState()) {
    _init();
  }

  void _init() {
    // Load cached products
    final cachedProducts = _storageService.getProducts();
    if (cachedProducts.isNotEmpty) {
      state = state.copyWith(
        products: cachedProducts,
        filteredProducts: cachedProducts,
      );
    }

    // Fetch from API
    fetchProducts();
    fetchCategories();
  }

  Future<void> fetchProducts({String? category}) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final products = await _apiService.getProducts(category: category);

      if (products.isNotEmpty) {
        state = state.copyWith(products: products);
        _applyFilters();

        // Cache products
        await _storageService.saveProducts(products);
      }
    } catch (e) {
      state = state.copyWith(error: 'Failed to load products');
    } finally {
      state = state.copyWith(isLoading: false);
    }
  }

  Future<void> fetchCategories() async {
    try {
      final categories = await _apiService.getCategories();
      state = state.copyWith(categories: categories);
    } catch (e) {
      print('Failed to fetch categories: $e');
    }
  }

  void setCategory(String? category) {
    state = state.copyWith(selectedCategory: category);
    _applyFilters();
  }

  void setSearchQuery(String query) {
    state = state.copyWith(searchQuery: query);
    _applyFilters();
  }

  void _applyFilters() {
    var filtered = state.products;

    // Filter by category
    if (state.selectedCategory != null && state.selectedCategory!.isNotEmpty) {
      filtered = filtered
          .where((p) => p.category == state.selectedCategory)
          .toList();
    }

    // Filter by search query
    if (state.searchQuery.isNotEmpty) {
      final query = state.searchQuery.toLowerCase();
      filtered = filtered.where((p) {
        return p.name.toLowerCase().contains(query) ||
            (p.description?.toLowerCase().contains(query) ?? false);
      }).toList();
    }

    state = state.copyWith(filteredProducts: filtered);
  }

  Future<void> refresh() async {
    await fetchProducts(category: state.selectedCategory);
    await fetchCategories();
  }
}
