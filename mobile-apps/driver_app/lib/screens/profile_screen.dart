import 'package:flutter/material.dart';
import '../services/api.dart';
import '../services/foreground_service.dart';
import '../theme/app_theme.dart';
import 'login_screen.dart';

class ProfileScreen extends StatefulWidget {
  final int driverId;
  final String driverName;
  ProfileScreen({required this.driverId, required this.driverName});

  @override
  _ProfileScreenState createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Map<String, dynamic>? profile;
  bool loading = true;
  bool editing = false;
  bool saving = false;
  String message = '';
  bool _messageIsSuccess = false;

  final nameController = TextEditingController();
  final phoneController = TextEditingController();
  final licenseController = TextEditingController();

  @override
  void initState() {
    super.initState();
    loadProfile();
  }

  @override
  void dispose() {
    nameController.dispose();
    phoneController.dispose();
    licenseController.dispose();
    super.dispose();
  }

  Future<void> loadProfile() async {
    setState(() => loading = true);
    final data = await ApiService.getDriverProfile(widget.driverId);
    if (!mounted) return;
    setState(() {
      profile = data;
      loading = false;
      nameController.text = data['name'] ?? '';
      phoneController.text = data['phone'] ?? '';
      licenseController.text = data['license_number'] ?? '';
    });
  }

  Future<void> saveProfile() async {
    setState(() {
      saving = true;
      message = '';
    });

    final result = await ApiService.updateDriverProfile(
      widget.driverId,
      nameController.text.trim(),
      phoneController.text.trim(),
      licenseController.text.trim(),
    );

    if (!mounted) return;
    setState(() => saving = false);

    if (result['success'] == true) {
      setState(() {
        editing = false;
        message = 'Profile updated successfully';
        _messageIsSuccess = true;
      });
      loadProfile();
    } else {
      setState(() {
        message = result['message'] ?? 'Update failed';
        _messageIsSuccess = false;
      });
    }
  }

  Future<void> logout() async {
    await ForegroundService.stop();
    ApiService.clearToken();
    if (!mounted) return;
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (_) => LoginScreen()),
          (route) => false,
    );
  }

  Future<void> _confirmLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => Dialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 60,
                height: 60,
                decoration: BoxDecoration(
                  color: AppColors.danger.withOpacity(0.08),
                  shape: BoxShape.circle,
                ),
                alignment: Alignment.center,
                child: const Icon(Icons.logout_rounded,
                    color: AppColors.danger, size: 28),
              ),
              const SizedBox(height: 16),
              const Text(
                'Log out?',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'You will need to sign in again to view your deliveries.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 13,
                  height: 1.45,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 22),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => Navigator.pop(context, false),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size(0, 46),
                        side: const BorderSide(color: AppColors.border),
                        foregroundColor: AppColors.textPrimary,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: const Text('Cancel'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: () => Navigator.pop(context, true),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.danger,
                        minimumSize: const Size(0, 46),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: const Text('Log out'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
    if (confirmed == true) await logout();
  }

  void _startEditing() {
    setState(() {
      editing = true;
      message = '';
    });
  }

  void _cancelEditing() {
    setState(() {
      editing = false;
      message = '';
    });
    loadProfile();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Text(editing ? 'Edit profile' : 'Profile'),
        actions: [
          if (!editing && !loading)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: TextButton.icon(
                onPressed: _startEditing,
                icon: const Icon(Icons.edit_outlined,
                    color: AppColors.burgundy, size: 16),
                label: const Text(
                  'Edit',
                  style: TextStyle(
                    color: AppColors.burgundy,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
        ],
      ),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (!editing) _buildHeader(),
            if (!editing) const SizedBox(height: 28),
            if (editing) _buildEditForm() else _buildInfoCard(),
            if (message.isNotEmpty) ...[
              const SizedBox(height: 16),
              _buildMessageBanner(),
            ],
            if (!editing) ...[
              const SizedBox(height: 32),
              _buildLogoutButton(),
            ],
            const SizedBox(height: 16),
            if (!editing)
              Center(
                child: Text(
                  'KimFresh Driver · v1.0',
                  style: TextStyle(
                    fontSize: 11,
                    color:
                    AppColors.textSecondary.withOpacity(0.55),
                    letterSpacing: 0.3,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  // ── HEADER ───────────────────────────────────────────────────
  Widget _buildHeader() {
    final name = profile?['name'] ?? widget.driverName;
    final initials = _initialsFor(name);

    return Column(
      children: [
        // Avatar with soft ring
        Container(
          padding: const EdgeInsets.all(4),
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: AppColors.surface,
            border: Border.all(
              color: AppColors.burgundy.withOpacity(0.12),
              width: 4,
            ),
            boxShadow: [
              BoxShadow(
                color: AppColors.burgundy.withOpacity(0.10),
                blurRadius: 24,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Container(
            width: 88,
            height: 88,
            decoration: const BoxDecoration(
              color: AppColors.burgundy,
              shape: BoxShape.circle,
            ),
            alignment: Alignment.center,
            child: Text(
              initials,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 30,
                fontWeight: FontWeight.w700,
                letterSpacing: 1,
              ),
            ),
          ),
        ),
        const SizedBox(height: 16),
        Text(
          name,
          textAlign: TextAlign.center,
          style: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.w700,
            color: AppColors.textPrimary,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          profile?['email'] ?? '',
          textAlign: TextAlign.center,
          style: const TextStyle(
            fontSize: 13,
            color: AppColors.textSecondary,
          ),
        ),
      ],
    );
  }

  // ── READ-ONLY INFO CARD ──────────────────────────────────────
  Widget _buildInfoCard() {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          _infoRow(
            icon: Icons.phone_outlined,
            label: 'Phone',
            value: profile?['phone'],
          ),
          _divider(),
          _infoRow(
            icon: Icons.badge_outlined,
            label: 'License number',
            value: profile?['license_number'],
          ),
          _divider(),
          _infoRow(
            icon: Icons.verified_user_outlined,
            label: 'Account status',
            value: profile?['status'],
            isPill: true,
          ),
        ],
      ),
    );
  }

  Widget _infoRow({
    required IconData icon,
    required String label,
    required String? value,
    bool isPill = false,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: AppColors.burgundy.withOpacity(0.06),
              borderRadius: BorderRadius.circular(12),
            ),
            alignment: Alignment.center,
            child: Icon(icon, size: 18, color: AppColors.burgundy),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                    letterSpacing: 0.5,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 3),
                if (isPill)
                  _buildStatusPill(value)
                else
                  Text(
                    (value == null || value.isEmpty) ? '—' : value,
                    style: const TextStyle(
                      fontSize: 15,
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _divider() => const Divider(
    height: 1,
    thickness: 1,
    color: AppColors.border,
    indent: 18,
    endIndent: 18,
  );

  Widget _buildStatusPill(String? status) {
    final value = (status ?? 'unknown').toLowerCase();
    Color color;
    switch (value) {
      case 'active':
      case 'approved':
        color = AppColors.success;
        break;
      case 'pending':
        color = AppColors.warning;
        break;
      case 'suspended':
      case 'rejected':
        color = AppColors.danger;
        break;
      default:
        color = AppColors.textSecondary;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withOpacity(0.10),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 6),
          Text(
            (status ?? 'Unknown').toUpperCase(),
            style: TextStyle(
              color: color,
              fontSize: 11,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.5,
            ),
          ),
        ],
      ),
    );
  }

  // ── EDIT FORM ────────────────────────────────────────────────
  Widget _buildEditForm() {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 20),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'Personal information',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
              letterSpacing: 0.2,
            ),
          ),
          const SizedBox(height: 18),

          const _FieldLabel('Full name'),
          TextField(
            controller: nameController,
            maxLength: 100,
            textCapitalization: TextCapitalization.words,
            decoration: const InputDecoration(
              counterText: '',
              prefixIcon: Icon(Icons.person_outline_rounded,
                  color: AppColors.textSecondary),
            ),
          ),
          const SizedBox(height: 14),

          const _FieldLabel('Phone'),
          TextField(
            controller: phoneController,
            maxLength: 20,
            keyboardType: TextInputType.phone,
            decoration: const InputDecoration(
              counterText: '',
              prefixIcon: Icon(Icons.phone_outlined,
                  color: AppColors.textSecondary),
            ),
          ),
          const SizedBox(height: 14),

          const _FieldLabel('License number'),
          TextField(
            controller: licenseController,
            maxLength: 50,
            textCapitalization: TextCapitalization.characters,
            decoration: const InputDecoration(
              counterText: '',
              prefixIcon: Icon(Icons.badge_outlined,
                  color: AppColors.textSecondary),
            ),
          ),
          const SizedBox(height: 16),

          const _FieldLabel('Email'),
          Container(
            padding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            decoration: BoxDecoration(
              color: AppColors.canvas,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppColors.border),
            ),
            child: Row(
              children: [
                const Icon(Icons.mail_outline_rounded,
                    size: 18, color: AppColors.textSecondary),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    profile?['email'] ?? '—',
                    style: const TextStyle(
                      color: AppColors.textSecondary,
                      fontSize: 14,
                    ),
                  ),
                ),
                const Icon(Icons.lock_outline_rounded,
                    size: 15, color: AppColors.textSecondary),
              ],
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Email address cannot be changed.',
            style: TextStyle(
              fontSize: 11,
              color: AppColors.textSecondary,
              fontStyle: FontStyle.italic,
            ),
          ),
          const SizedBox(height: 24),

          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: saving ? null : _cancelEditing,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.textPrimary,
                    minimumSize: const Size(0, 48),
                    side: const BorderSide(color: AppColors.border),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    textStyle: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  child: const Text('Cancel'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton(
                  onPressed: saving ? null : saveProfile,
                  style: ElevatedButton.styleFrom(
                    minimumSize: const Size(0, 48),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: saving
                      ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                      : const Text('Save changes'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // ── MESSAGE BANNER ───────────────────────────────────────────
  Widget _buildMessageBanner() {
    final color =
    _messageIsSuccess ? AppColors.success : AppColors.danger;
    final icon = _messageIsSuccess
        ? Icons.check_circle_outline_rounded
        : Icons.error_outline_rounded;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.25)),
      ),
      child: Row(
        children: [
          Icon(icon, size: 18, color: color),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                color: color,
                fontSize: 13,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ── LOGOUT BUTTON ────────────────────────────────────────────
  Widget _buildLogoutButton() {
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: _confirmLogout,
        icon: const Icon(Icons.logout_rounded, size: 18),
        label: const Text('Log out'),
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.danger,
          minimumSize: const Size(0, 50),
          side: BorderSide(color: AppColors.danger.withOpacity(0.35)),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          textStyle: const TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    );
  }

  String _initialsFor(String name) {
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.isEmpty || parts.first.isEmpty) return '?';
    if (parts.length == 1) {
      return parts.first.substring(0, 1).toUpperCase();
    }
    return (parts.first.substring(0, 1) + parts.last.substring(0, 1))
        .toUpperCase();
  }
}

class _FieldLabel extends StatelessWidget {
  final String text;
  const _FieldLabel(this.text);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6, left: 2),
      child: Text(
        text,
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: AppColors.textSecondary,
          letterSpacing: 0.3,
        ),
      ),
    );
  }
}