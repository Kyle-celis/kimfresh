"""Product management routes."""
from utils.validators import validate_name, validate_positive_float, validate_positive_int
from extensions import require_auth
from flask import Blueprint, request, jsonify
from db_config import get_db_connection
from config import (
    DEFAULT_REORDER_LEVEL,
    PRODUCT_STATUS_AVAILABLE,
    PRODUCT_STATUS_INACTIVE,
)
import mysql.connector

products_bp = Blueprint('products', __name__)


@products_bp.route('/api/products', methods=['GET'])
def get_products():
    """Return all active products ordered by ID ascending."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute(
        "SELECT * FROM product WHERE status = %s ORDER BY product_id ASC",
        (PRODUCT_STATUS_AVAILABLE,)
    )
    products = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(products)


@products_bp.route('/api/products/all', methods=['GET'])
def get_all_products():
    """Return all products including inactive ones."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM product ORDER BY product_id ASC")
    products = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(products)


@products_bp.route('/api/products/<int:product_id>', methods=['GET'])
def get_product(product_id):
    """Return a single product by its ID."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM product WHERE product_id = %s", (product_id,))
    product = cursor.fetchone()
    cursor.close()
    conn.close()

    if product:
        return jsonify(product)
    return jsonify({"message": "Product not found"}), 404


@products_bp.route('/api/products', methods=['POST'])
@require_auth(['admin'])
def add_or_update_product():
    """
    Add a new product or update an existing one.

    If SKU or name matches an existing product, it is updated.
    Otherwise, a new product is created with an auto-generated SKU.
    """
    data = request.get_json()
    name = data.get('name')
    sku = data.get('sku')

    if not name and not sku:
        return jsonify({"success": False, "message": "Name or SKU required"}), 400

    if name:
        is_valid, err = validate_name(name, "Product name")
        if not is_valid:
            return jsonify({"success": False, "message": err}), 400

    if data.get('unit_price') not in [None, '']:
        is_valid, err = validate_positive_float(data['unit_price'], "Unit price")
        if not is_valid:
            return jsonify({"success": False, "message": err}), 400

    if data.get('reorder_level') not in [None, '']:
        is_valid, err = validate_positive_int(data['reorder_level'], "Reorder level")
        if not is_valid:
            return jsonify({"success": False, "message": err}), 400

    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    try:
        existing = _find_existing_product(cursor, sku, name)

        if existing:
            return _update_existing_product(cursor, conn, existing, data, name, sku)
        else:
            return _create_new_product(cursor, conn, data, name, sku)

    except mysql.connector.Error as err:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(err)}), 400


def _find_existing_product(cursor, sku, name):
    """Find product by SKU first, then by name."""
    if sku:
        cursor.execute("SELECT * FROM product WHERE sku = %s", (sku,))
        existing = cursor.fetchone()
        if existing:
            return existing

    if name:
        cursor.execute("SELECT * FROM product WHERE name = %s", (name,))
        return cursor.fetchone()

    return None


def _update_existing_product(cursor, conn, existing, data, name, sku):
    """Update fields on an existing product, keeping old values if not provided."""
    new_name = name or existing['name']
    new_sku = sku or existing['sku']
    new_price = data.get('unit_price') if data.get('unit_price') not in [None, ''] else existing['unit_price']
    new_stock = data.get('reorder_level') if data.get('reorder_level') not in [None, ''] else existing['reorder_level']

    cursor.execute(
        """UPDATE product 
           SET name = %s, sku = %s, unit_price = %s, reorder_level = %s, status = %s 
           WHERE product_id = %s""",
        (new_name, new_sku, new_price, new_stock, PRODUCT_STATUS_AVAILABLE, existing['product_id'])
    )
    conn.commit()
    cursor.close()
    conn.close()
    return jsonify({"success": True, "message": f"Product '{new_name}' updated"})


def _create_new_product(cursor, conn, data, name, sku):
    """Create a new product with auto-generated SKU if none provided."""
    if not sku:
        cursor.execute("SELECT COUNT(*) as count FROM product")
        count = cursor.fetchone()['count'] + 1
        sku = f"KIM-{count:03d}"

    price = data.get('unit_price') if data.get('unit_price') not in [None, ''] else 0
    stock = data.get('reorder_level') if data.get('reorder_level') not in [None, ''] else DEFAULT_REORDER_LEVEL

    cursor.execute(
        """INSERT INTO product 
           (name, sku, description, unit_price, reorder_level, stock_quantity, status) 
           VALUES (%s, %s, %s, %s, %s, %s, %s)""",
        (name, sku, '', price, stock, stock, PRODUCT_STATUS_AVAILABLE)
    )
    conn.commit()
    cursor.close()
    conn.close()
    return jsonify({"success": True, "message": f"Product added with SKU {sku}"})


@products_bp.route('/api/products/<int:product_id>/hide', methods=['PUT'])
@require_auth(['admin'])
def hide_product(product_id):
    """Soft-delete a product by marking it inactive."""
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        cursor.execute(
            "UPDATE product SET status = %s WHERE product_id = %s",
            (PRODUCT_STATUS_INACTIVE, product_id)
        )
        conn.commit()
        cursor.close()
        conn.close()
        return jsonify({"success": True, "message": f"Product {product_id} hidden"})
    except Exception as e:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(e)}), 400


@products_bp.route('/api/products/<int:product_id>/restore', methods=['PUT'])
@require_auth(['admin'])
def restore_product(product_id):
    """Restore a hidden product back to available."""
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        cursor.execute(
            "UPDATE product SET status = %s WHERE product_id = %s",
            (PRODUCT_STATUS_AVAILABLE, product_id)
        )
        conn.commit()
        cursor.close()
        conn.close()
        return jsonify({"success": True, "message": f"Product {product_id} restored"})
    except Exception as e:
        conn.rollback()
        cursor.close()
        conn.close()
        return jsonify({"success": False, "message": str(e)}), 400