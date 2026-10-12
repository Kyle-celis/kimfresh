"""
Dashboard controller — dashboard routes.
"""

from flask import Blueprint, request, jsonify
from datetime import datetime, timedelta
from models import dashboard_model

dashboard_bp = Blueprint('dashboard', __name__)


@dashboard_bp.route('/api/dashboard/summary', methods=['GET'])
def dashboard_summary():
    """Return summary counts."""
    return jsonify(dashboard_model.get_summary())


@dashboard_bp.route('/api/dashboard/orders-per-week', methods=['GET'])
def orders_per_week():
    """Return weekly order counts."""
    week_start = request.args.get('week_start')
    if not week_start:
        week_start = (datetime.now() - timedelta(days=datetime.now().weekday())).strftime('%Y-%m-%d')
    return jsonify(dashboard_model.get_orders_per_week(week_start))


@dashboard_bp.route('/api/dashboard/recent-orders', methods=['GET'])
def recent_orders():
    """Return last 10 orders."""
    return jsonify(dashboard_model.get_recent_orders(10))
