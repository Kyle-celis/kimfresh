"""
Password hashing helpers.

Uses Argon2id — the modern standard for password hashing.
Argon2 is slow by design, making brute-force attacks impractical.
"""

from argon2 import PasswordHasher
from argon2.exceptions import VerifyMismatchError, VerificationError, InvalidHashError

# Create one hasher shared across the app
_ph = PasswordHasher()


def hash_password(password: str) -> str:
    """
    Hash a plain password using Argon2id.
    
    Returns a string that includes the salt and parameters.
    """
    return _ph.hash(password)


def verify_password(password: str, hashed: str) -> bool:
    """
    Check if a plain password matches an Argon2 hash.
    
    Returns True if matched, False otherwise.
    """
    try:
        return _ph.verify(hashed, password)
    except (VerifyMismatchError, VerificationError, InvalidHashError):
        return False


def needs_rehash(hashed: str) -> bool:
    """
    Check if a hash needs to be upgraded to newer parameters.
    
    Useful for future-proofing as Argon2 parameters improve over time.
    """
    return _ph.check_needs_rehash(hashed)
