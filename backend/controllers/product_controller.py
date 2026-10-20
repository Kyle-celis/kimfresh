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
def add_product():
    """Add a new product."""
    data = request.get_json() or {}
    name = data.get('name')
    sku = data.get('sku')
    price = data.get('unit_price')
    stock = data.get('stock_quantity')
    reorder = data.get('reorder_level')

    if not name or price is None or stock is None:
        return jsonify({"success": False, "message": "Missing required fields"}), 400

    is_valid, err = validate_name(name, "Product name")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    try:
        # Auto-generate SKU if blank
        if not sku:
            count = product_model.count_products() + 1
            sku = f"KIM-{count:03d}"

        product_model.create_product(name, sku, '', price, stock, reorder)
        return jsonify({"success": True, "message": "Product added successfully"})
    except Exception as e:
        return jsonify({"success": False, "message": str(e)}), 500


@products_bp.route('/api/products/<int:product_id>', methods=['PUT'])
@require_auth(['admin'])
def update_product_details(product_id):
    """Update an existing product."""
    data = request.get_json() or {}
    name = data.get('name')
    sku = data.get('sku')
    price = data.get('unit_price')
    stock = data.get('stock_quantity')
    reorder = data.get('reorder_level')

    if not name or price is None or stock is None:
        return jsonify({"success": False, "message": "Missing required fields"}), 400

    try:
        product_model.update_product(product_id, name, sku, price, stock, reorder)
        return jsonify({"success": True, "message": "Product updated successfully"})
    except Exception as e:
        return jsonify({"success": False, "message": str(e)}), 500


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