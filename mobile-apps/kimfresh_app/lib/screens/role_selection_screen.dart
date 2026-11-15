import 'package:flutter/material.dart';
import 'package:flutter/services.dart'; // Required for HapticFeedback
import '../theme/app_theme.dart';
import 'login_screen.dart';
import '../theme/transitions.dart'; // Import the new transition

class RoleSelectionScreen extends StatefulWidget {
  const RoleSelectionScreen({super.key});

  @override
  State<RoleSelectionScreen> createState() => _RoleSelectionScreenState();
}

class _RoleSelectionScreenState extends State<RoleSelectionScreen> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      body: Stack(
        children: [
          // ── 1. Ultra-Soft Blurred Background Blobs ──
          Positioned(
            top: -100,
            left: -100,
            child: _buildBlurredBlob(AppColors.burgundy.withOpacity(0.15), 320),
          ),
          Positioned(
            bottom: -50,
            right: -100,
            child: _buildBlurredBlob(AppColors.burgundy.withOpacity(0.10), 280),
          ),

          // ── Main Content ──
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 16.0),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Spacer(),

                  // ── 2. Elevated Logo Presentation ──
                  Center(
                    child: Container(
                      padding: const EdgeInsets.all(24),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.burgundy.withOpacity(0.15),
                            blurRadius: 40,
                            spreadRadius: 10,
                            offset: const Offset(0, 10),
                          ),
                        ],
                      ),
                      child: Image.asset(
                        'assets/logo.png',
                        height: 80,
                        fit: BoxFit.contain,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ),
                  const SizedBox(height: 40),

                  // ── Titles ──
                  const Text(
                    'Welcome back',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.w900,
                      color: AppColors.textPrimary,
                      letterSpacing: -0.8,
                    ),
                  ),
                  const SizedBox(height: 10),
                  const Text(
                    'Choose your role to continue',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 16,
                      color: AppColors.textSecondary,
                      fontWeight: FontWeight.w500,
                    ),
                  ),

                  const Spacer(),

                  // ── 3. Animated Interactive Cards ──
                  _EntranceAnimation(
                    delay: 100,
                    child: _ModernRoleCard(
                      title: 'Driver',
                      subtitle: 'Manage your deliveries',
                      icon: Icons.local_shipping_rounded,
                      isPrimary: true,
                      onTap: () {
                        HapticFeedback.lightImpact(); // Subtle vibration on tap
                        Navigator.pushReplacement(
                          context,
                          FadeSlideRoute(page: const LoginScreen(role: 'driver')),
                        );
                      },
                    ),
                  ),

                  const SizedBox(height: 16),

                  _EntranceAnimation(
                    delay: 250, // Slight stagger effect
                    child: _ModernRoleCard(
                      title: 'Retailer',
                      subtitle: 'Order fresh products',
                      icon: Icons.storefront_rounded,
                      isPrimary: false,
                      onTap: () {
                        HapticFeedback.lightImpact(); // Subtle vibration on tap
                        Navigator.pushReplacement(
                          context,
                          FadeSlideRoute(page: const LoginScreen(role: 'retailer')),
                        );
                      },
                    ),
                  ),

                  const Spacer(),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // Helper to generate smooth, blurred background blobs
  Widget _buildBlurredBlob(Color color, double size) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: color,
        shape: BoxShape.circle,
        // Adding a subtle shadow acts as a cheap way to blur the edges
        boxShadow: [
          BoxShadow(color: color, blurRadius: 60, spreadRadius: 20),
        ],
      ),
    );
  }
}

/// Wraps a widget with a smooth Fade + Slide Up entrance animation
class _EntranceAnimation extends StatefulWidget {
  final Widget child;
  final int delay; // in milliseconds

  const _EntranceAnimation({required this.child, required this.delay});

  @override
  State<_EntranceAnimation> createState() => _EntranceAnimationState();
}

class _EntranceAnimationState extends State<_EntranceAnimation> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOut),
    );

    _slideAnimation = Tween<Offset>(begin: const Offset(0, 0.15), end: Offset.zero).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic),
    );

    // Delay the start of the animation
    Future.delayed(Duration(milliseconds: widget.delay), () {
      if (mounted) _controller.forward();
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _fadeAnimation,
      child: SlideTransition(
        position: _slideAnimation,
        child: widget.child,
      ),
    );
  }
}

/// A highly interactive, modern card with a "press" animation.
class _ModernRoleCard extends StatefulWidget {
  final String title;
  final String subtitle;
  final IconData icon;
  final bool isPrimary;
  final VoidCallback onTap;

  const _ModernRoleCard({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.isPrimary,
    required this.onTap,
  });

  @override
  State<_ModernRoleCard> createState() => _ModernRoleCardState();
}

class _ModernRoleCardState extends State<_ModernRoleCard> {
  bool _isPressed = false;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: (_) => setState(() => _isPressed = true),
      onTapUp: (_) => setState(() => _isPressed = false),
      onTapCancel: () => setState(() => _isPressed = false),
      onTap: widget.onTap,
      child: AnimatedScale(
        scale: _isPressed ? 0.96 : 1.0, // The "press" effect
        duration: const Duration(milliseconds: 100),
        curve: Curves.easeInOut,
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: widget.isPrimary ? AppColors.burgundy : Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [
              BoxShadow(
                color: widget.isPrimary
                    ? AppColors.burgundy.withOpacity(0.25)
                    : Colors.black.withOpacity(0.04),
                blurRadius: 24,
                offset: const Offset(0, 10),
              ),
            ],
            border: widget.isPrimary ? null : Border.all(color: Colors.grey.withOpacity(0.1)),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: widget.isPrimary
                      ? Colors.white.withOpacity(0.2)
                      : AppColors.burgundy.withOpacity(0.08),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Icon(
                    widget.icon,
                    color: widget.isPrimary ? Colors.white : AppColors.burgundy,
                    size: 28
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.title,
                      style: TextStyle(
                        color: widget.isPrimary ? Colors.white : AppColors.textPrimary,
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        letterSpacing: -0.3,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      widget.subtitle,
                      style: TextStyle(
                        color: widget.isPrimary ? Colors.white.withOpacity(0.8) : AppColors.textSecondary,
                        fontSize: 13,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.arrow_forward_rounded,
                color: widget.isPrimary ? Colors.white : AppColors.burgundy,
                size: 24,
              ),
            ],
          ),
        ),
      ),
    );
  }
}