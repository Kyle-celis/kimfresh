"""
Auth controller — login and retailer registration routes.
"""

from flask import Blueprint, request, jsonify
from extensions import limiter
from utils.auth_token import create_token
from utils.logger import logger
from utils.security import hash_password, verify_password
from utils.validators import validate_email, validate_password, validate_name
from models import user_model
import random

auth_bp = Blueprint('auth', __name__)


@auth_bp.route('/api/login', methods=['POST'])
@limiter.limit("50 per 15 minutes")
def login():
    """Log in admin, retailer, or driver."""
    data = request.get_json()
    email = data.get('email')
    password = data.get('password')

    if not email or not password:
        return jsonify({"success": False, "message": "Email and password required"}), 400

    # Try admin
    user = user_model.find_admin_by_email(email)
    role = "admin"

    if not user:
        user = user_model.find_retailer_by_email(email)
        role = "retailer"

    if not user:
        user = user_model.find_driver_by_email(email)
        role = "driver"

    if not user:
        logger.warning(f"Login failed: unknown email {email}")
        return jsonify({"success": False, "message": "Invalid email or password"}), 401

    stored_hash = user.get('password_hash')
    if not stored_hash:
        logger.warning(f"Login failed: no password hash for {email}")
        return jsonify({"success": False, "message": "Account has no password set."}), 401

    if not verify_password(password, stored_hash):
        logger.warning(f"Login failed: wrong password for {email}")
        return jsonify({"success": False, "message": "Invalid email or password"}), 401

    user_id = user['admin_id'] if role == 'admin' else user['user_id']
    token = create_token(user_id=user_id, role=role, email=user['email'])

    logger.info(f"Login success: {email} as {role}")

    return jsonify({
        "success": True,
        "token": token,
        "user_id": user_id,
        "name": user['name'],
        "email": user['email'],
        "role": role
    })


@auth_bp.route('/api/retailer/register', methods=['POST'])
@limiter.limit("100 per hour")
def retailer_register():
    """Register new retailer with auto customer code."""
    data = request.get_json()
    name = data.get('name')
    email = data.get('email')
    password = data.get('password')
    phone = data.get('phone', '')
    address = data.get('address', '')

    is_valid, err = validate_name(name, "Name")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    is_valid, err = validate_email(email)
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    is_valid, err = validate_password(password)
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    if user_model.email_exists_in_retailer(email):
        return jsonify({"success": False, "message": "Email already registered"}), 400

    # Generate unique customer code
    while True:
        code = str(random.randint(100000, 999999))
        if not user_model.customer_code_exists(code):
            break

    password_hash = hash_password(password)

    try:
        retailer_id = user_model.create_retailer(name, email, phone, password_hash, address, code)
        logger.info(f"New retailer registered: {email}")
        return jsonify({
            "success": True,
            "retailer_id": retailer_id,
            "customer_code": code,
            "name": name,
            "message": f"Registered! Your customer code: {code}"
        })
    except Exception as e:
        logger.error(f"Retailer registration failed: {e}", exc_info=True)
        return jsonify({"success": False, "message": str(e)}), 400
