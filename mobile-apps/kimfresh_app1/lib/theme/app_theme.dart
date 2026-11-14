import 'package:flutter/material.dart';

class AppColors {
  // ---- Brand ----
  static const Color brand        = Color(0xFFD13D00); // Unified primary (Rust Orange)
  static const Color brandDark    = Color(0xFFA82F00); // Pressed / dark
  static const Color burgundyDark = brandDark; // <-- ADD THIS
  static const Color brandLight   = Color(0xFFFF7043); // Accents
  
  // Aliases for Driver app compatibility
  static const Color burgundy     = brand; 
  static const Color canvas       = Color(0xFFF6F7F9); // Same as background

  // ---- Surfaces ----
  static const Color background   = Color(0xFFF6F7F9);
  static const Color surface      = Color(0xFFFFFFFF);
  static const Color border       = Color(0xFFE5E7EB);

  // ---- Text ----
  static const Color textPrimary  = Color(0xFF1F2937);
  static const Color textSecondary= Color(0xFF6B7280);
  
  // Aliases for Retailer app compatibility
  static const Color text         = textPrimary;
  static const Color muted        = textSecondary;

  // ---- Status ----
  static const Color success      = Color(0xFF16A34A);
  static const Color warning      = Color(0xFFF59E0B);
  static const Color danger       = Color(0xFFDC2626);
  static const Color info         = Color(0xFF2563EB);
  static const Color purple       = Color(0xFF7C3AED);
  
  // Alias for Retailer app
  static const Color error        = danger;

  // ---- Spacing ----
  static const double sXs = 4;
  static const double sSm = 8;
  static const double sMd = 12;
  static const double sLg = 16;
  static const double sXl = 24;

  // ---- Radius ----
  static const double rSm = 8;
  static const double rMd = 10;
  static const double rLg = 12;
  static const double rXl = 16;

  // ---- Shadows ----
  static List<BoxShadow> get cardShadow => [
    BoxShadow(
      color: Colors.black.withOpacity(0.03),
      blurRadius: 8,
      offset: const Offset(0, 2),
    ),
  ];
}

class AppTheme {
  static ThemeData build() {
    return ThemeData(
      primaryColor: AppColors.brand,
      scaffoldBackgroundColor: AppColors.background,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.brand,
        primary: AppColors.brand,
        background: AppColors.background,
        surface: AppColors.surface,
        error: AppColors.danger,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.brand, // Unified top bar
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.brand,
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppColors.rMd),
          ),
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 24),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.brand,
          side: const BorderSide(color: AppColors.brand),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppColors.rMd),
          ),
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 24),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppColors.rMd),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppColors.rMd),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppColors.rMd),
          borderSide: const BorderSide(color: AppColors.brand, width: 2),
        ),
      ),
    );
  }
}

// ── BACKWARD COMPATIBILITY ALIASES ──
// This keeps the old Retailer screens working without changing every single file.
class RetailerColorsAlias {
  static const brand = AppColors.brand;
  static const background = AppColors.background;
  static const surface = AppColors.surface;
  static const border = AppColors.border;
  static const text = AppColors.text;
  static const muted = AppColors.muted;
  static const success = AppColors.success;
  static const error = AppColors.error;
  static const warning = AppColors.warning;
  static const info = AppColors.info;
  static const purple = AppColors.purple;
  static const rSm = AppColors.rSm;
  static const rMd = AppColors.rMd;
  static const rLg = AppColors.rLg;
  static const rXl = AppColors.rXl;
  static const sXs = AppColors.sXs;
  static const sSm = AppColors.sSm;
  static const sMd = AppColors.sMd;
  static const sLg = AppColors.sLg;
  static List<BoxShadow> get cardShadow => AppColors.cardShadow;
}



// Extra aliases for Driver app compatibility
class DriverColors {
  static const burgundyDark = Color(0xFFA82F00);
  static const danger = Color(0xFFDC2626);
}