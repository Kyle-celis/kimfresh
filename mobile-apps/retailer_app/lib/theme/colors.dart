import 'package:flutter/material.dart';

// ============================================================
// KimFresh — Central design tokens
// ============================================================
class AppColors {
  // ---- Brand ----
  static const Color brand        = Color(0xFFD13D00); // new primary
  static const Color brandDark    = Color(0xFFA82F00); // pressed / dark
  static const Color brandLight   = Color(0xFFFF7043); // accents

  // ---- Surfaces ----
  static const Color background   = Color(0xFFF6F7F9);
  static const Color surface      = Color(0xFFFFFFFF);
  static const Color border       = Color(0xFFE5E7EB);

  // ---- Text ----
  static const Color text         = Color(0xFF1F2937);
  static const Color muted        = Color(0xFF6B7280);

  // ---- Status ----
  static const Color success      = Color(0xFF16A34A);
  static const Color warning      = Color(0xFFF59E0B);
  static const Color error        = Color(0xFFDC2626);
  static const Color info         = Color(0xFF2563EB);
  static const Color purple       = Color(0xFF7C3AED);

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

// Legacy aliases so existing code keeps compiling during the transition.
const Color kBrand      = AppColors.brand;
const Color kBackground = AppColors.background;
const Color kSurface    = AppColors.surface;
const Color kText       = AppColors.text;
const Color kMuted      = AppColors.muted;
const Color kBorder     = AppColors.border;