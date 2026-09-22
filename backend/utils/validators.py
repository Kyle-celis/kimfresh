"""
Input validation helpers.

These functions check that user input is safe and correct
before we save it to the database.
"""

import re


def validate_email(email):
    """
    Check if an email looks valid.
    
    Returns (is_valid, error_message).
    """
    if not email or not isinstance(email, str):
        return False, "Email is required"

    email = email.strip().lower()

    if len(email) > 100:
        return False, "Email is too long"

    pattern = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
    if not re.match(pattern, email):
        return False, "Invalid email format"

    return True, None


def validate_password(password):
    """
    Check if a password is strong enough.
    
    Rules:
    - At least 6 characters
    - No more than 100 characters
    
    Returns (is_valid, error_message).
    """
    if not password or not isinstance(password, str):
        return False, "Password is required"

    if len(password) < 6:
        return False, "Password must be at least 6 characters"

    if len(password) > 100:
        return False, "Password is too long"

    return True, None


def validate_name(name, field_name="Name"):
    """
    Check if a name is valid.
    
    Rules:
    - Required
    - 1 to 100 characters
    
    Returns (is_valid, error_message).
    """
    if not name or not isinstance(name, str):
        return False, f"{field_name} is required"

    name = name.strip()

    if len(name) < 1:
        return False, f"{field_name} cannot be empty"

    if len(name) > 100:
        return False, f"{field_name} is too long (max 100 characters)"

    return True, None


def validate_positive_int(value, field_name="Value"):
    """
    Check if a value is a positive integer.
    
    Returns (is_valid, error_message).
    """
    try:
        number = int(value)
    except (ValueError, TypeError):
        return False, f"{field_name} must be a number"

    if number <= 0:
        return False, f"{field_name} must be greater than 0"

    if number > 100000:
        return False, f"{field_name} is too large"

    return True, None


def validate_positive_float(value, field_name="Value"):
    """
    Check if a value is a positive number (float).
    
    Returns (is_valid, error_message).
    """
    try:
        number = float(value)
    except (ValueError, TypeError):
        return False, f"{field_name} must be a number"

    if number < 0:
        return False, f"{field_name} cannot be negative"

    if number > 1000000:
        return False, f"{field_name} is too large"

    return True, None


def validate_items_list(items):
    """
    Check if an order items list is valid.
    
    Returns (is_valid, error_message).
    """
    if not items or not isinstance(items, list):
        return False, "Items must be a non-empty list"

    if len(items) > 50:
        return False, "Too many items in one order (max 50)"

    for i, item in enumerate(items):
        if not isinstance(item, dict):
            return False, f"Item {i+1} is invalid"

        if 'product_id' not in item or 'quantity' not in item:
            return False, f"Item {i+1} must have product_id and quantity"

        is_valid, err = validate_positive_int(item['product_id'], f"Item {i+1} product_id")
        if not is_valid:
            return False, err

        is_valid, err = validate_positive_int(item['quantity'], f"Item {i+1} quantity")
        if not is_valid:
            return False, err

    return True, None
