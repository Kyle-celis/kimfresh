"""
Retailer model — database queries for retailer profile and operations.
Note: auth-related queries (login, signup, customer_code check) stay in user_model.py.
"""

from db_config import get_db_connection


def get_retailer_by_id(retailer_id):
    """Return single retailer row by ID (for profile view)."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT retailer_id, name, email, phone, address, customer_code
        FROM retailer WHERE retailer_id = %s
    """, (retailer_id,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def update_retailer(retailer_id, name, phone, address):
    """Update retailer name, phone, address. Email/customer_code not editable."""
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        cursor.execute("""
            UPDATE retailer
            SET name = %s, phone = %s, address = %s
            WHERE retailer_id = %s
        """, (name, phone, address, retailer_id))
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        cursor.close()
        conn.close()
