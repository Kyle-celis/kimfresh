"""
Product controller — product management routes.
"""

from flask import Blueprint, request, jsonify
from extensions import require_auth
from utils.validators import validate_name, validate_positive_float, validate_positive_int
from models import product_model

products_bp = Blueprint('products', __name__)


@products_bp.route('/api/products', methods=['GET'])
def get_products():
    """Return active products."""
    return jsonify(product_model.get_active_products())


@products_bp.route('/api/products/all', methods=['GET'])
def get_all_products():
    """Return all products."""
    return jsonify(product_model.get_all_products())


@products_bp.route('/api/products/<int:product_id>', methods=['GET'])
def get_product(product_id):
    """Return one product."""
    product = product_model.get_product_by_id(product_id)
    if product:
        return jsonify(product)
    return jsonify({"message": "Product not found"}), 404


@products_bp.route('/api/products', methods=['POST'])
@require_auth(['admin'])
def add_or_update_product():
    """Add new product or update existing."""
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

    # Find existing by SKU then name
    existing = None
    if sku:
        existing = product_model.get_product_by_sku(sku)
    if not existing and name:
        existing = product_model.get_product_by_name(name)

    if existing:
        new_name = name or existing['name']
        new_sku = sku or existing['sku']
        new_price = data.get('unit_price') if data.get('unit_price') not in [None, ''] else existing['unit_price']
        new_stock = data.get('reorder_level') if data.get('reorder_level') not in [None, ''] else existing['reorder_level']
        product_model.update_product(existing['product_id'], new_name, new_sku, new_price, new_stock)
        return jsonify({"success": True, "message": f"Product '{new_name}' updated"})

    # New product
    if not sku:
        count = product_model.count_products() + 1
        sku = f"KIM-{count:03d}"

    price = data.get('unit_price') if data.get('unit_price') not in [None, ''] else 0
    stock = data.get('reorder_level') if data.get('reorder_level') not in [None, ''] else 5

    product_model.create_product(name, sku, '', price, stock, stock)
    return jsonify({"success": True, "message": f"Product added with SKU {sku}"})


@products_bp.route('/api/products/<int:product_id>/hide', methods=['PUT'])
@require_auth(['admin'])
def hide_product(product_id):
    """Set product to inactive."""
    product_model.hide_product(product_id)
    return jsonify({"success": True, "message": f"Product {product_id} hidden"})


@products_bp.route('/api/products/<int:product_id>/restore', methods=['PUT'])
@require_auth(['admin'])
def restore_product(product_id):
    """Set product to available."""
    product_model.restore_product(product_id)
    return jsonify({"success": True, "message": f"Product {product_id} restored"})
