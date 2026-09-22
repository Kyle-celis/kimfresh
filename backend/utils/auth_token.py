"""
JWT token helpers.

Issues signed tokens on login and verifies them on protected routes.
"""

import jwt
import os
from datetime import datetime, timedelta, timezone
from dotenv import load_dotenv

load_dotenv()

SECRET_KEY = os.environ.get('JWT_SECRET')
if not SECRET_KEY:
    raise RuntimeError(
        "JWT_SECRET environment variable is not set. "
        "Add it to your .env file."
    )

ALGORITHM = 'HS256'
TOKEN_EXPIRY_HOURS = 8

def create_token(user_id, role, email):
    """
    Create a JWT token with user info.

    Returns a signed token string.
    """
    payload = {
        'user_id': user_id,
        'role': role,
        'email': email,
        'exp': datetime.now(timezone.utc) + timedelta(hours=TOKEN_EXPIRY_HOURS),
        'iat': datetime.now(timezone.utc),
    }
    return jwt.encode(payload, SECRET_KEY, algorithm=ALGORITHM)


def verify_token(token):
    """
    Verify a JWT token.

    Returns the payload dict if valid, None if invalid or expired.
    """
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        return payload
    except jwt.ExpiredSignatureError:
        return None
    except jwt.InvalidTokenError:
        return None


def get_token_from_header(request):
    """
    Extract the JWT token from the Authorization header.

    Expected format: "Bearer <token>"
    """
    auth_header = request.headers.get('Authorization', '')
    if not auth_header.startswith('Bearer '):
        return None
    return auth_header[7:]
