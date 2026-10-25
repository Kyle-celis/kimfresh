import 'dart:async';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import '../services/api.dart';
import '../theme/colors.dart';
import 'login_screen.dart';

class ProfileScreen extends StatefulWidget {
  final int retailerId;
  final String retailerName;
  ProfileScreen({required this.retailerId, required this.retailerName});

  @override
  _ProfileScreenState createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Map<String, dynamic>? profile;
  bool loading = true;
  bool editing = false;
  bool saving = false;
  bool _pinningLocation = false; // GPS state
  String message = '';
  bool _ok = false;

  final nameController = TextEditingController();
  final phoneController = TextEditingController();
  final addressController = TextEditingController();

  @override
  void initState() {
    super.initState();
    loadProfile();
  }

  @override
  void dispose() {
    nameController.dispose();
    phoneController.dispose();
    addressController.dispose();
    super.dispose();
  }

  Future<void> loadProfile() async {
    setState(() => loading = true);
    final data = await ApiService.getRetailerProfile(widget.retailerId);
    if (!mounted) return;
    setState(() {
      profile = data;
      loading = false;
      nameController.text = data['name'] ?? '';
      phoneController.text = data['phone'] ?? '';
      addressController.text = data['address'] ?? '';
    });
  }

  Future<void> saveProfile() async {
    setState(() {
      saving = true;
      message = '';
    });

    final result = await ApiService.updateRetailerProfile(
      widget.retailerId,
      nameController.text.trim(),
      phoneController.text.trim(),
      addressController.text.trim(),
    );

    if (!mounted) return;
    setState(() => saving = false);

    if (result['success'] == true) {
      setState(() {
        editing = false;
        message = 'Profile updated';
        _ok = true;
      });
      loadProfile();
    } else {
      setState(() {
        message = result['message'] ?? 'Update failed';
        _ok = false;
      });
    }
  }

  // ──────────────────────────────────────────────────────────
  // PIN STORE LOCATION (Sends GPS to the driver)
  // ──────────────────────────────────────────────────────────
  Future<void> _pinStoreLocation() async {
    setState(() {
      _pinningLocation = true;
      message = '';
    });

    try {
      // 1. Check GPS
      final enabled = await Geolocator.isLocationServiceEnabled();
      if (!enabled) {
        _showMessage('Please enable location services', ok: false);
        return;
      }

      // 2. Check Permissions
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        _showMessage('Location permission denied', ok: false);
        return;
      }

      // 3. Get GPS
      final pos = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      ).timeout(const Duration(seconds: 12));

      // 4. Find active order
      final orders = await ApiService.getOrders(widget.retailerId);
      Map<dynamic, dynamic>? active;
      for (final o in orders) {
        if (o is Map) {
          final s = (o['order_status'] ?? '').toString();
          if (s == 'pending' || s == 'confirmed' || s == 'picked_up' || s == 'in_transit') {
            active = o;
            break;
          }
        }
      }

      if (active == null) {
        _showMessage('No active order to attach location to', ok: false);
        return;
      }

      final orderId = int.tryParse('${active['order_id']}');
      if (orderId == null) {
        _showMessage('Invalid order ID', ok: false);
        return;
      }

      // 5. Save to Server
      final ok = await ApiService.saveRetailerLocation(
        orderId,
        pos.latitude,
        pos.longitude,
      );

      if (!mounted) return;
      _showMessage(
        ok ? 'Store location shared for order #$orderId' : 'Failed to save location. Try again.',
        ok: ok,
      );
    } on TimeoutException {
      _showMessage('GPS timed out. Try again outdoors.', ok: false);
    } catch (e) {
      _showMessage('GPS error: $e', ok: false);
    } finally {
      if (mounted) setState(() => _pinningLocation = false);
    }
  }

  void _showMessage(String msg, {required bool ok}) {
    if (!mounted) return;
    setState(() {
      message = msg;
      _ok = ok;
    });
  }

  Future<void> _confirmLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: AppColors.surface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppColors.rXl),
        ),
        title: const Text(
          'Log out?',
          style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: AppColors.text),
        ),
        content: const Text(
          'You will need to sign in again to continue.',
          style: TextStyle(fontSize: 14, color: AppColors.muted, height: 1.4),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            style: TextButton.styleFrom(foregroundColor: AppColors.text),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: AppColors.brand),
            child: const Text('Log out', style: TextStyle(fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
    if (confirmed == true) logout();
  }

  void logout() {
    ApiService.clearToken();
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (_) => LoginScreen()),
          (route) => false,
    );
  }

  InputDecoration _decoration({required String label, required IconData icon}) {
    return InputDecoration(
      labelText: label,
      labelStyle: const TextStyle(color: AppColors.muted, fontSize: 14),
      floatingLabelStyle: const TextStyle(color: AppColors.brand, fontSize: 14),
      prefixIcon: Icon(icon, color: AppColors.muted, size: 20),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        foregroundColor: AppColors.text,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: Text(
          editing ? 'Edit profile' : 'Profile',
          style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w600, color: AppColors.text),
        ),
        actions: [
          if (!editing && !loading)
            TextButton(
              onPressed: () => setState(() => editing = true),
              style: TextButton.styleFrom(
                foregroundColor: AppColors.brand,
                padding: const EdgeInsets.symmetric(horizontal: 12),
              ),
              child: const Text('Edit', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
            ),
        ],
      ),
      body: loading
          ? const Center(child: CircularProgressIndicator(color: AppColors.brand))
          : RefreshIndicator(
        color: AppColors.brand,
        onRefresh: loadProfile,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  editing ? _editView() : _readView(),
                  if (message.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    _banner(),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  // ---------- READ VIEW ----------
  Widget _readView() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppColors.rLg),
            border: Border.all(color: AppColors.border),
            boxShadow: AppColors.cardShadow,
          ),
          child: Column(
            children: [
              Container(
                height: 64,
                width: 64,
                decoration: const BoxDecoration(color: AppColors.brand, shape: BoxShape.circle),
                child: Center(
                  child: Text(
                    _initials(profile?['name']),
                    style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700),
                  ),
                ),
              ),
              const SizedBox(height: 14),
              Text(
                profile?['name'] ?? 'Retailer',
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.text),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 4),
              const Text('Retailer account', style: TextStyle(fontSize: 13, color: AppColors.muted)),
              if (profile?['customer_code'] != null) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: AppColors.background,
                    borderRadius: BorderRadius.circular(AppColors.rSm),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.qr_code_rounded, size: 14, color: AppColors.muted),
                      const SizedBox(width: 6),
                      Text(
                        '${profile?['customer_code']}',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.text, letterSpacing: 0.8),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: 12),

        // ── PIN LOCATION CARD ──
        _pinLocationCard(),
        const SizedBox(height: 12),

        Container(
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppColors.rLg),
            border: Border.all(color: AppColors.border),
            boxShadow: AppColors.cardShadow,
          ),
          child: Column(
            children: [
              _info(icon: Icons.mail_outline_rounded, label: 'Email', value: profile?['email']),
              _divider(),
              _info(icon: Icons.phone_outlined, label: 'Phone', value: profile?['phone']),
              _divider(),
              _info(icon: Icons.location_on_outlined, label: 'Address', value: profile?['address']),
            ],
          ),
        ),
        const SizedBox(height: 24),
        SizedBox(
          height: 48,
          child: OutlinedButton.icon(
            onPressed: _confirmLogout,
            icon: const Icon(Icons.logout_rounded, size: 16),
            label: const Text('Log out', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.brand,
              side: BorderSide(color: AppColors.brand.withOpacity(0.4)),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppColors.rMd)),
            ),
          ),
        ),
      ],
    );
  }

  Widget _pinLocationCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppColors.rLg),
        border: Border.all(color: AppColors.border),
        boxShadow: AppColors.cardShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Container(
                height: 36,
                width: 36,
                decoration: BoxDecoration(
                  color: AppColors.brand.withOpacity(0.08),
                  borderRadius: BorderRadius.circular(AppColors.rSm),
                ),
                child: const Icon(Icons.my_location_rounded, size: 18, color: AppColors.brand),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Store location', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.text)),
                    SizedBox(height: 2),
                    Text('Share your GPS so the driver can find you.', style: TextStyle(fontSize: 12, color: AppColors.muted)),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 44,
            child: ElevatedButton.icon(
              onPressed: _pinningLocation ? null : _pinStoreLocation,
              icon: _pinningLocation
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.pin_drop_rounded, size: 18),
              label: Text(_pinningLocation ? 'Getting your location…' : 'Pin my store location'),
            ),
          ),
        ],
      ),
    );
  }

  // ---------- EDIT VIEW ----------
  Widget _editView() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppColors.rLg),
        border: Border.all(color: AppColors.border),
        boxShadow: AppColors.cardShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text('Edit details', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.text)),
          const SizedBox(height: 4),
          const Text('Keep your contact information up to date.', style: TextStyle(fontSize: 13, color: AppColors.muted, height: 1.4)),
          const SizedBox(height: 20),
          TextField(
            controller: nameController,
            maxLength: 100,
            decoration: _decoration(label: 'Name', icon: Icons.person_outline_rounded).copyWith(counterText: ''),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: phoneController,
            maxLength: 20,
            keyboardType: TextInputType.phone,
            decoration: _decoration(label: 'Phone', icon: Icons.phone_outlined).copyWith(counterText: ''),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: addressController,
            maxLength: 200,
            maxLines: 2,
            decoration: _decoration(label: 'Address', icon: Icons.location_on_outlined).copyWith(counterText: ''),
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            decoration: BoxDecoration(
              color: AppColors.background,
              borderRadius: BorderRadius.circular(AppColors.rMd),
              border: Border.all(color: AppColors.border),
            ),
            child: Row(
              children: [
                const Icon(Icons.mail_outline_rounded, color: AppColors.muted, size: 20),
                const SizedBox(width: 12),
                Expanded(child: Text(profile?['email'] ?? '-', style: const TextStyle(fontSize: 14, color: AppColors.muted))),
                const Icon(Icons.lock_outline_rounded, color: AppColors.muted, size: 14),
              ],
            ),
          ),
          const SizedBox(height: 6),
          const Text('Email cannot be changed', style: TextStyle(fontSize: 11, color: AppColors.muted)),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: SizedBox(
                  height: 48,
                  child: OutlinedButton(
                    onPressed: saving
                        ? null
                        : () {
                      setState(() {
                        editing = false;
                        message = '';
                      });
                      loadProfile();
                    },
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.text,
                      side: const BorderSide(color: AppColors.border),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppColors.rMd)),
                    ),
                    child: const Text('Cancel'),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: SizedBox(
                  height: 48,
                  child: ElevatedButton(
                    onPressed: saving ? null : saveProfile,
                    child: saving
                        ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Text('Save changes'),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // ---------- HELPERS ----------
  Widget _info({required IconData icon, required String label, String? value}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: AppColors.muted),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: const TextStyle(fontSize: 12, color: AppColors.muted, fontWeight: FontWeight.w500)),
                const SizedBox(height: 2),
                Text((value == null || value.isEmpty) ? '—' : value, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500, color: AppColors.text)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _divider() => const Divider(height: 1, indent: 48, color: AppColors.border);

  Widget _banner() {
    final color = _ok ? AppColors.success : AppColors.error;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: color.withOpacity(0.06),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withOpacity(0.25)),
      ),
      child: Row(
        children: [
          Icon(_ok ? Icons.check_circle_outline_rounded : Icons.error_outline_rounded, size: 18, color: color),
          const SizedBox(width: 8),
          Expanded(child: Text(message, style: TextStyle(fontSize: 13, color: color))),
        ],
      ),
    );
  }

  String _initials(String? name) {
    if (name == null || name.trim().isEmpty) return 'R';
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.length == 1) return parts.first.substring(0, 1).toUpperCase();
    return (parts.first.substring(0, 1) + parts.last.substring(0, 1)).toUpperCase();
  }
}