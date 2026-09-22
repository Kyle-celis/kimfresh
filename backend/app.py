"""
KimFresh Backend — Main Entry Point

This is where the whole server starts. It connects all the different
parts of our system together (login, products, orders, sensors, etc.)
so they can all run from one place.

Think of it like the main door of a building — everything else is
rooms inside, but this is where you enter.
"""

from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import os

# Import the shared limiter
from extensions import limiter

# Import the different "blueprints" (mini-apps) that handle separate jobs
from products import products_bp   # Handles products (kimchi items)
from orders import orders_bp       # Handles customer orders
from sensor import sensor_bp       # Handles temperature/humidity data
from drivers import drivers_bp     # Handles delivery drivers
from dashboard import dashboard_bp # Handles summary counts and charts
from auth import auth_bp           # Handles login and sign up


# Create the main app. This is our server.
app = Flask(__name__)

# Allow the app to be accessed from other devices (like the phone app)
CORS(app, resources={r"/api/*": {"origins": "*"}}, supports_credentials=True)

# Attach the rate limiter to the app
limiter.init_app(app)


# ---------- CONNECT ALL THE BLUEPRINTS ----------
# This tells our server: "use all these mini-apps too"
app.register_blueprint(auth_bp)
app.register_blueprint(products_bp)
app.register_blueprint(orders_bp)
app.register_blueprint(sensor_bp)
app.register_blueprint(drivers_bp)
app.register_blueprint(dashboard_bp)


# ---------- HOME PAGE ----------
@app.route('/', methods=['GET'])
def home():
    """
    Simple test route.

    When someone visits the main URL, this just says the server is alive.
    Useful for checking if the backend is running.
    """
    return jsonify({"message": "KimFresh API is running!"})


# ---------- SERVE WEB FILES ----------
# This tells Flask where our HTML files (login, dashboard, etc.) live
WEB_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'web-app')


@app.route('/app/<path:filename>')
def serve_web(filename):
    """
    Sends a web page file to the browser.

    For example, if someone goes to /app/login.html, this function
    will send them the login.html file so they can see the page.
    """
    return send_from_directory(WEB_DIR, filename)


# ---------- SECURITY HEADERS ----------
@app.after_request
def add_security_headers(response):
    """
    Adds safety rules to every response we send back to the browser.

    These rules tell the browser things like:
    - "Don't guess what type of file this is" (nosniff)
    - "Don't let anyone put this page inside another page" (X-Frame-Options)
    - "Only trust scripts from these specific places" (Content Security Policy)

    Think of it like adding a security guard at the exit door.
    """
    response.headers['X-Content-Type-Options'] = 'nosniff'
    response.headers['X-Frame-Options'] = 'DENY'
    response.headers['Content-Security-Policy'] = (
        "default-src 'self'; "
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        "style-src 'self' 'unsafe-inline'; "
        "img-src 'self' data:; "
        "frame-ancestors 'none';"
    )
    return response


# ---------- START THE SERVER ----------
if __name__ == '__main__':
    # host='0.0.0.0' means: accept connections from other devices on the network
    # port=5000 is the door number the server listens on
    # debug=False means: don't show error details (safer for real use)
    app.run(host='0.0.0.0', port=5000, debug=False)