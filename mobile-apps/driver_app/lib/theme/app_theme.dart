import 'package:flutter/material.dart';

class AppColors {
  // Changed primary brand color to #D13D00
  static const burgundy = Color(0xFFD13D00);

  // Adjusted dark and light variations to match the new color
  static const burgundyDark = Color(0xFF9C2D00);
  static const burgundyLight = Color(0xFFE8581A);

  static const canvas = Color(0xFFF5F6FA);
  static const surface = Color(0xFFFFFFFF);
  static const textPrimary = Color(0xFF1F1F23);
  static const textSecondary = Color(0xFF6B6B76);
  static const border = Color(0xFFE4E6EC);
  static const success = Color(0xFF2E7D32);
  static const warning = Color(0xFFE65100);
  static const danger = Color(0xFFC62828);
}

class AppTheme {
  static ThemeData build() {
    final base = ThemeData.light(useMaterial3: true);

    return base.copyWith(
      scaffoldBackgroundColor: AppColors.canvas,
      colorScheme: base.colorScheme.copyWith(
        primary: AppColors.burgundy,
        secondary: AppColors.burgundyLight,
        surface: AppColors.surface,
        error: AppColors.danger,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.burgundy,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          color: Colors.white,
          fontSize: 18,
          fontWeight: FontWeight.w600,
          letterSpacing: 0.2,
        ),
        iconTheme: IconThemeData(color: Colors.white),
      ),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: const BorderSide(color: AppColors.border),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.burgundy,
          foregroundColor: Colors.white,
          elevation: 0,
          minimumSize: const Size(0, 48),
          padding: const EdgeInsets.symmetric(horizontal: 20),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: const TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w600,
            letterSpacing: 0.2,
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.burgundy,
          textStyle: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        contentPadding:
        const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        hintStyle: const TextStyle(color: AppColors.textSecondary),
        labelStyle: const TextStyle(color: AppColors.textSecondary),
        floatingLabelStyle: const TextStyle(color: AppColors.burgundy),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.burgundy, width: 1.6),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.danger),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.danger, width: 1.6),
        ),
      ),
      dividerTheme:
      const DividerThemeData(color: AppColors.border, thickness: 1),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: AppColors.textPrimary,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
      ),
      progressIndicatorTheme:
      const ProgressIndicatorThemeData(color: AppColors.burgundy),
    );
  }
}