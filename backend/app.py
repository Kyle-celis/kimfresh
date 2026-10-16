"""
KimFresh Backend — Main Entry Point (MVC structure).

Connects all controllers (routes) to the Flask app.
"""

from flask import Flask, jsonify, send_from_directory
from flask_cors import CORS
from werkzeug.exceptions import HTTPException
from flask_limiter.errors import RateLimitExceeded
import os

# Shared extensions
from extensions import limiter

# Controllers (routes)
from controllers.auth_controller import auth_bp
from controllers.product_controller import products_bp
from controllers.order_controller import orders_bp
from controllers.driver_controller import drivers_bp
from controllers.sensor_controller import sensor_bp
from controllers.dashboard_controller import dashboard_bp
from controllers.retailer_controller import retailer_bp   # ← NEW

# Logger
from utils.logger import logger


# ---------- APP ----------
app = Flask(__name__)
CORS(app, resources={r"/api/*": {"origins": "*"}})
limiter.init_app(app)

# ---------- REGISTER CONTROLLERS ----------
app.register_blueprint(auth_bp)
app.register_blueprint(products_bp)
app.register_blueprint(orders_bp)
app.register_blueprint(drivers_bp)
app.register_blueprint(sensor_bp)
app.register_blueprint(dashboard_bp)
app.register_blueprint(retailer_bp)   # ← NEW


# ---------- HOME ----------
@app.route('/', methods=['GET'])
def home():
    return jsonify({"message": "KimFresh API is running!"})


# ---------- SERVE WEB FILES ----------
WEB_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'web-app')


@app.route('/app/<path:filename>')
def serve_web(filename):
    return send_from_directory(WEB_DIR, filename)


# ---------- SECURITY HEADERS ----------
@app.after_request
def add_security_headers(response):
    response.headers['X-Content-Type-Options'] = 'nosniff'
    response.headers['X-Frame-Options'] = 'DENY'
    response.headers['Content-Security-Policy'] = (
        "default-src 'self'; "
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        "style-src 'self' 'unsafe-inline'; "
        "img-src 'self' data:; "
        "connect-src 'self' http://localhost:5000 https://*.trycloudflare.com https://cdn.jsdelivr.net; "
        "frame-ancestors 'none';"
    )
    return response


# ---------- RATE LIMIT HANDLER ----------
@app.errorhandler(RateLimitExceeded)
def handle_rate_limit(e):
    return jsonify({"success": False, "message": "Too many requests."}), 429


# ---------- HTTP ERROR HANDLER ----------
@app.errorhandler(HTTPException)
def handle_http_exception(e):
    return jsonify({"success": False, "message": e.description}), e.code


# ---------- GLOBAL ERROR HANDLER ----------
@app.errorhandler(Exception)
def handle_exception(e):
    logger.error(f"Unhandled exception: {e}", exc_info=True)
    return jsonify({"success": False, "message": "Internal server error"}), 500


# ---------- RUN ----------
if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=False)