"""
Authentication routes: login and retailer registration.

Uses Argon2id for password hashing.
"""

from flask import Blueprint, request, jsonify
from db_config import get_db_connection
from utils.security import hash_password, verify_password
from extensions import limiter
import random
import mysql.connector

auth_bp = Blueprint('auth', __name__)


@auth_bp.route('/api/login', methods=['POST'])
@limiter.limit("5 per 15 minutes")
def login():
    """
    Log in a user (admin, retailer, or driver).
    
    All roles require a matching Argon2 password hash.
    Users without a stored hash are rejected.
    """
    data = request.get_json()
    email = data.get('email')
    password = data.get('password')

    if not email or not password:
        return jsonify({"success": False, "message": "Email and password required"}), 400

    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    # Try admin
    cursor.execute("SELECT admin_id, name, email, password_hash FROM admin WHERE email = %s", (email,))
    user = cursor.fetchone()
    role = "admin"

    # Try retailer
    if not user:
        cursor.execute("SELECT retailer_id as user_id, name, email, password_hash FROM retailer WHERE email = %s", (email,))
        user = cursor.fetchone()
        role = "retailer"

    # Try driver
    if not user:
        cursor.execute("SELECT driver_id as user_id, name, email, password_hash FROM driver WHERE email = %s", (email,))
        user = cursor.fetchone()
        role = "driver"

    cursor.close()
    conn.close()

    if not user:
        return jsonify({"success": False, "message": "Invalid email or password"}), 401

    # Users must have a password hash set
    stored_hash = user.get('password_hash')
    if not stored_hash:
        return jsonify({"success": False, "message": "Account has no password set. Contact admin."}), 401

    # Verify password
    if not verify_password(password, stored_hash):
        return jsonify({"success": False, "message": "Invalid email or password"}), 401

    return jsonify({
        "success": True,
        "user_id": user['admin_id'] if role == 'admin' else user['user_id'],
        "name": user['name'],
        "email": user['email'],
        "role": role
    })


@auth_bp.route('/api/retailer/register', methods=['POST'])
@limiter.limit("10 per hour")
def retailer_register():
    """
    Register a new retailer.
    
    Generates a unique 6-digit customer code on success.
    Passwords are hashed with Argon2id.
    """
    data = request.get_json()
    name = data.get('name')
    email = data.get('email')
    password = data.get('password')
    phone = data.get('phone', '')
    address = data.get('address', '')

    if not name or not email or not password:
        return jsonify({"success": False, "message": "Name, email, and password required"}), 400

    if len(password) < 6:
        return jsonify({"success": False, "message": "Password must be at least 6 characters"}), 400

    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT retailer_id FROM retailer WHERE email = %s", (email,))
    if cursor.fetchone():
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": "Email already registered"}), 400

    # Generate unique customer code
    while True:
        code = str(random.randint(100000, 999999))
        cursor.execute("SELECT retailer_id FROM retailer WHERE customer_code = %s", (code,))
        if not cursor.fetchone():
            break

    password_hash = hash_password(password)

    try:
        cursor.execute(
            "INSERT INTO retailer (name, email, phone, password_hash, address, customer_code) VALUES (%s, %s, %s, %s, %s, %s)",
            (name, email, phone, password_hash, address, code)
        )
        conn.commit()
        retailer_id = cursor.lastrowid
        cursor.close()
        conn.close()
        return jsonify({
            "success": True,
            "retailer_id": retailer_id,
            "customer_code": code,
            "name": name,
            "message": f"Registered! Your customer code: {code}"
        })
    except mysql.connector.Error as err:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(err)}), 400
