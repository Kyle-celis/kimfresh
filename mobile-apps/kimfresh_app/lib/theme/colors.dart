import 'package:flutter/material.dart';
import 'app_theme.dart';

// This file is now just a copy of the unified AppColors.
// The Retailer screens will import this file and get the exact same colors.
class AppColors {
  static const Color brand        = Color(0xFFD13D00);
  static const Color burgundyDark = Color(0xFFA82F00); 
  static const Color background   = Color(0xFFF6F7F9);
  static const Color surface      = Color(0xFFFFFFFF);
  static const Color border       = Color(0xFFE5E7EB);
  static const Color text         = Color(0xFF1F2937);
  static const Color muted        = Color(0xFF6B7280);
  static const Color success      = Color(0xFF16A34A);
  static const Color warning      = Color(0xFFF59E0B);
  static const Color error        = Color(0xFFDC2626);
  static const Color info         = Color(0xFF2563EB);
  static const Color purple       = Color(0xFF7C3AED);

  static const double sXs = 4;
  static const double sSm = 8;
  static const double sMd = 12;
  static const double sLg = 16;

  static const double rSm = 8;
  static const double rMd = 10;
  static const double rLg = 12;
  static const double rXl = 16;

  static List<BoxShadow> get cardShadow => [
    BoxShadow(
      color: Colors.black.withOpacity(0.03),
      blurRadius: 8,
      offset: const Offset(0, 2),
    ),
  ];
}