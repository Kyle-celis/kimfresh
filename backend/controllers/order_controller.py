"""
Order controller — order routes.
"""

from flask import Blueprint, request, jsonify
from extensions import require_auth
from utils.logger import logger
from utils.validators import validate_positive_int, validate_items_list
from models import order_model
from models import order_model, driver_model
from utils.logger import logger

orders_bp = Blueprint('orders', __name__)


@orders_bp.route('/api/orders', methods=['POST'])
@require_auth(['retailer'])
def place_order():
    """Place new order."""
    data = request.get_json()
    retailer_id = data.get('retailer_id')
    items = data.get('items', [])

    is_valid, err = validate_positive_int(retailer_id, "Retailer ID")
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    is_valid, err = validate_items_list(items)
    if not is_valid:
        return jsonify({"success": False, "message": err}), 400

    try:
        # Fetch all products in one query
        product_ids = [item['product_id'] for item in items]
        products_list = order_model.get_products_by_ids(product_ids)
        products = {p['product_id']: p for p in products_list}

        # Validate stock
        total = 0
        for item in items:
            product = products.get(item['product_id'])
            if not product:
                return jsonify({"success": False, "message": f"Product {item['product_id']} not found"}), 400
            if product['stock_quantity'] < item['quantity']:
                return jsonify({
                    "success": False,
                    "message": f"Not enough stock for {product['name']}. Available: {product['stock_quantity']}"
                }), 400
            total += product['unit_price'] * item['quantity']

        # Create order
        order_id = order_model.create_order(retailer_id, total)

        # Create items + reduce stock
        for item in items:
            product = products[item['product_id']]
            unit_price = product['unit_price']
            subtotal = unit_price * item['quantity']
            order_model.create_order_item(order_id, item['product_id'], item['quantity'], unit_price, subtotal)
            order_model.reduce_stock(item['product_id'], product['stock_quantity'] - item['quantity'])

        logger.info(f"Order #{order_id} placed by retailer {retailer_id} total ₱{total}")

        return jsonify({
            "success": True,
            "order_id": order_id,
            "total": total,
            "message": "Order placed successfully"
        })
    except Exception as e:
        logger.error(f"Order placement failed: {e}", exc_info=True)
        return jsonify({"success": False, "message": str(e)}), 500


@orders_bp.route('/api/orders/<int:retailer_id>', methods=['GET'])
@require_auth(['admin', 'retailer'])
def get_orders(retailer_id):
    """Return orders for a retailer."""
    requester_role = request.user.get('role')
    requester_id = request.user.get('user_id')

    if requester_role != 'admin':
        if requester_role != 'retailer' or str(retailer_id) != str(requester_id):
            return jsonify({"success": False, "message": "Unauthorized"}), 403

    return jsonify(order_model.get_orders_by_retailer(retailer_id))


@orders_bp.route('/api/orders/all', methods=['GET'])
@require_auth(['admin'])
def get_all_orders():
    """Return all orders."""
    return jsonify(order_model.get_all_orders())


@orders_bp.route('/api/orders/<int:order_id>/status', methods=['PUT'])
@require_auth(['admin'])
def update_order_status(order_id):
    """Update order status. Auto-assigns driver when approved."""
    data = request.get_json()
    new_status = data.get('status')

    order_model.update_order_status(order_id, new_status)

    assigned_driver = None
    auto_assigned = False

    if new_status == 'approved':
        # Check if already assigned
        existing = driver_model.get_delivery_by_order(order_id)
        if not existing:
            driver_id = driver_model.auto_pick_driver()
            if driver_id:
                driver_model.assign_delivery(order_id, driver_id)
                assigned_driver = driver_id
                auto_assigned = True
                logger.info(f"Order #{order_id} auto-assigned to driver {driver_id}")
            else:
                logger.warning(f"Order #{order_id} approved but no driver available")

    return jsonify({
        "success": True,
        "message": f"Order {order_id} set to {new_status}",
        "auto_assigned": auto_assigned,
        "assigned_driver": assigned_driver
    })