"""
Retailer controller — retailer profile routes.
Note: login/signup stay in auth.py.
"""

from flask import Blueprint, request, jsonify
from extensions import require_auth
from utils.logger import logger
from models import retailer_model

retailer_bp = Blueprint('retailer', __name__)


# =========================================================
# RETAILER PROFILE — GET / UPDATE
# =========================================================

@retailer_bp.route('/api/retailer/<int:retailer_id>/profile', methods=['GET'])
@require_auth(['retailer', 'admin'])
def get_retailer_profile(retailer_id):
    """Return retailer profile info."""
    r = retailer_model.get_retailer_by_id(retailer_id)
    if not r:
        return jsonify({"success": False, "message": "Retailer not found"}), 404
    return jsonify(r)


@retailer_bp.route('/api/retailer/<int:retailer_id>/profile', methods=['PUT'])
@require_auth(['retailer', 'admin'])
def update_retailer_profile(retailer_id):
    """Update retailer name, phone, address. Email not editable."""
    data = request.get_json() or {}
    name = (data.get('name') or '').strip()
    phone = (data.get('phone') or '').strip()
    address = (data.get('address') or '').strip()

    if not name:
        return jsonify({"success": False, "message": "Name is required"}), 400

    if not retailer_model.get_retailer_by_id(retailer_id):
        return jsonify({"success": False, "message": "Retailer not found"}), 404

    try:
        retailer_model.update_retailer(retailer_id, name, phone, address)
    except Exception as e:
        logger.error(f"Failed to update retailer {retailer_id}: {e}")
        return jsonify({"success": False, "message": "Update failed"}), 400

    logger.info(f"Retailer {retailer_id} profile updated")
    return jsonify({"success": True, "message": "Profile updated"})
