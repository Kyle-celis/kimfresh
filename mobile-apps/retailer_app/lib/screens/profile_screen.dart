import 'package:flutter/material.dart';
import '../services/api.dart';
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
  String message = '';

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
      });
      loadProfile();
    } else {
      setState(() => message = result['message'] ?? 'Update failed');
    }
  }

  void logout() {
    ApiService.clearToken();
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (_) => LoginScreen()),
          (route) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(editing ? 'Edit Profile' : 'Profile'),
        actions: [
          if (!editing && !loading)
            TextButton(
              onPressed: () => setState(() => editing = true),
              child: Text('Edit', style: TextStyle(color: Colors.black)),
            ),
        ],
      ),
      body: loading
          ? Center(child: CircularProgressIndicator())
          : Padding(
        padding: EdgeInsets.all(20),
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (editing) ...[
                Text('Name'),
                TextField(controller: nameController, maxLength: 100),
                SizedBox(height: 10),
                Text('Phone'),
                TextField(
                    controller: phoneController,
                    maxLength: 20,
                    keyboardType: TextInputType.phone),
                SizedBox(height: 10),
                Text('Address'),
                TextField(controller: addressController, maxLength: 200),
                SizedBox(height: 10),
                Text('Email (cannot change)'),
                Text(profile?['email'] ?? '',
                    style: TextStyle(color: Colors.grey)),
                SizedBox(height: 20),
                Row(
                  children: [
                    ElevatedButton(
                      onPressed: saving ? null : saveProfile,
                      child: saving
                          ? SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                              strokeWidth: 2, color: Colors.white))
                          : Text('Save'),
                    ),
                    SizedBox(width: 10),
                    TextButton(
                      onPressed: () {
                        setState(() {
                          editing = false;
                          message = '';
                        });
                        loadProfile();
                      },
                      child: Text('Cancel'),
                    ),
                  ],
                ),
              ] else ...[
                Text(profile?['name'] ?? '',
                    style: TextStyle(
                        fontSize: 22, fontWeight: FontWeight.bold)),
                SizedBox(height: 5),
                Text('Retailer',
                    style: TextStyle(color: Colors.grey, fontSize: 14)),
                Divider(height: 30),
                _field('Email', profile?['email']),
                _field('Phone', profile?['phone']),
                _field('Address', profile?['address']),
                _field('Customer Code', profile?['customer_code']),
                SizedBox(height: 30),
                ElevatedButton(
                  onPressed: logout,
                  style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red),
                  child: Text('Logout',
                      style: TextStyle(color: Colors.white)),
                ),
              ],
              SizedBox(height: 20),
              Text(message,
                  style: TextStyle(
                      color: message.contains('updated')
                          ? Colors.green
                          : Colors.red)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _field(String label, String? value) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(color: Colors.grey, fontSize: 13)),
          SizedBox(height: 4),
          Text(value ?? '-', style: TextStyle(fontSize: 16)),
        ],
      ),
    );
  }
}