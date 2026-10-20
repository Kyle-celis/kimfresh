"""
Product model — all database queries for products.
"""

from db_config import get_db_connection


def get_active_products():
    """Return all available products."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM product WHERE status = 'available' ORDER BY product_id ASC")
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows


def get_all_products():
    """Return all products including inactive."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM product ORDER BY product_id ASC")
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows


def get_product_by_id(product_id):
    """Return one product."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM product WHERE product_id = %s", (product_id,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def get_product_by_sku(sku):
    """Return product by SKU."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM product WHERE sku = %s", (sku,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def get_product_by_name(name):
    """Return product by name."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM product WHERE name = %s", (name,))
    row = cursor.fetchone()
    cursor.close()
    conn.close()
    return row


def count_products():
    """Return count of all products."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT COUNT(*) FROM product")
    count = cursor.fetchone()[0]
    cursor.close()
    conn.close()
    return count


def create_product(name, sku, description, unit_price, reorder_level, stock_quantity):
    """Insert new product."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute(
        """INSERT INTO product 
           (name, sku, description, unit_price, reorder_level, stock_quantity, status) 
           VALUES (%s, %s, %s, %s, %s, %s, 'available')""",
        (name, sku, description, unit_price, reorder_level, stock_quantity)
    )
    conn.commit()
    cursor.close()
    conn.close()


def update_product(product_id, name, sku, unit_price, stock_quantity, reorder_level):
    """Update an existing product."""
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        cursor.execute("""
            UPDATE product 
            SET name = %s, sku = %s, unit_price = %s, stock_quantity = %s, reorder_level = %s 
            WHERE product_id = %s
        """, (name, sku, unit_price, stock_quantity, reorder_level, product_id))
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        cursor.close()
        conn.close()


def hide_product(product_id):
    """Set product status to inactive."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE product SET status = 'inactive' WHERE product_id = %s", (product_id,))
    conn.commit()
    cursor.close()
    conn.close()


def restore_product(product_id):
    """Set product status to available."""
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE product SET status = 'available' WHERE product_id = %s", (product_id,))
    conn.commit()
    cursor.close()
    conn.close()