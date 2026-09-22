"""Shared extensions for the Flask app."""
import os
from functools import wraps
from flask import request, jsonify
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
from utils.auth_token import verify_token, get_token_from_header

TESTING = os.environ.get('TESTING') == '1'

# Rate limiter
limiter = Limiter(
    key_func=get_remote_address,
    default_limits=["200 per day", "50 per hour"],
    storage_uri="memory://",
    enabled=os.environ.get('TESTING') != '1',
)


# JWT auth decorator
def require_auth(roles=None):
    """
    Decorator to protect routes with JWT tokens.

    Usage:
        @require_auth(['admin'])
        @require_auth(['admin', 'retailer'])
        @require_auth()
    """
    def decorator(f):
        @wraps(f)
        def wrapper(*args, **kwargs):
            token = get_token_from_header(request)
            if not token:
                return jsonify({"success": False, "message": "Token required"}), 401

            payload = verify_token(token)
            if not payload:
                return jsonify({"success": False, "message": "Invalid or expired token"}), 401

            if roles and payload.get('role') not in roles:
                return jsonify({"success": False, "message": "Forbidden"}), 403

            request.user = payload
            return f(*args, **kwargs)
        return wrapper
    return decorator