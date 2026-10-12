"""
User model — all database queries for admin, retailer, driver.

No HTTP. No Flask. Just SQL.
"""

from db_config import get_db_connection
import hashlib


# ---------- FIND ----------

def find_admin_by_email(email):
    """Return admin row by email, or None."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT admin_id, name, email, password_hash FROM admin WHERE email = %s", (email,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def find_retailer_by_email(email):
    """Return retailer row by email, or None."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT retailer_id as user_id, name, email, password_hash FROM retailer WHERE email = %s", (email,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def find_driver_by_email(email):
    """Return driver row by email, or None."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT driver_id as user_id, name, email, password_hash FROM driver WHERE email = %s", (email,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def email_exists_in_retailer(email):
    """Check if email already registered as retailer."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT retailer_id FROM retailer WHERE email = %s", (email,))
    exists = cursor.fetchone() is not None
    cursor.close()
    conn.close()
    return exists


def customer_code_exists(code):
    """Check if customer_code already used."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT retailer_id FROM retailer WHERE customer_code = %s", (code,))
    exists = cursor.fetchone() is not None
    cursor.close()
    conn.close()
    return exists


# ---------- CREATE ----------

def create_retailer(name, email, phone, password_hash, address, customer_code):
    """Insert new retailer, return retailer_id."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        "INSERT INTO retailer (name, email, phone, password_hash, address, customer_code) VALUES (%s, %s, %s, %s, %s, %s)",
        (name, email, phone, password_hash, address, customer_code)
    )
    conn.commit()
    retailer_id = cursor.lastrowid
    cursor.close()
    conn.close()
    return retailer_id
