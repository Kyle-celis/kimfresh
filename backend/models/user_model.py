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
    cursor.execute("SELECT driver_id as user_id, name, email, password_hash, account_status FROM driver WHERE email = %s", (email,))
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

def create_driver_signup(name, email, phone, license_number, password_hash):
    """Create new driver with pending account_status."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        """INSERT INTO driver 
           (name, email, phone, license_number, password_hash, status, account_status) 
           VALUES (%s, %s, %s, %s, %s, 'available', 'pending')""",
        (name, email, phone, license_number, password_hash)
    )
    conn.commit()
    driver_id = cursor.lastrowid
    cursor.close()
    conn.close()
    return driver_id


def driver_email_exists(email):
    """Check if email exists in driver table."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT driver_id FROM driver WHERE email = %s", (email,))
    exists = cursor.fetchone() is not None
    cursor.close()
    conn.close()
    return exists


def get_driver_account_status(driver_id):
    """Return account_status for a driver."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT account_status FROM driver WHERE driver_id = %s", (driver_id,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row[0] if row else None

def email_exists_in_admin(email):
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT admin_id FROM admin WHERE email = %s", (email,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return bool(row)

def create_admin(name, email, password_hash):
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        cursor.execute(
            "INSERT INTO admin (name, email, password_hash) VALUES (%s, %s, %s)",
            (name, email, password_hash)
        )
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        cursor.close()
        conn.close()