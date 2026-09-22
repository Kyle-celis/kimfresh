"""Dashboard summary and chart routes."""
from flask import Blueprint, request, jsonify
from datetime import datetime, timedelta
from db_config import get_db_connection

dashboard_bp = Blueprint('dashboard', __name__)


@dashboard_bp.route('/api/dashboard/summary', methods=['GET'])
def dashboard_summary():
    """Return all summary counts for the dashboard cards."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT COUNT(*) AS count FROM product WHERE status='available'")
    total_products = cursor.fetchone()['count']

    cursor.execute("SELECT COUNT(*) AS count FROM driver WHERE status='available'")
    total_drivers = cursor.fetchone()['count']

    cursor.execute("SELECT COUNT(*) AS count FROM `order`")
    total_orders = cursor.fetchone()['count']

    cursor.execute("SELECT COUNT(*) AS count FROM `order` WHERE order_status='pending'")
    pending_orders = cursor.fetchone()['count']

    cursor.execute("SELECT COUNT(*) AS count FROM `order` WHERE order_status='approved'")
    approved_orders = cursor.fetchone()['count']

    cursor.execute("SELECT COUNT(*) AS count FROM `order` WHERE order_status='rejected'")
    rejected_orders = cursor.fetchone()['count']

    cursor.execute("SELECT COUNT(*) AS count FROM product WHERE stock_quantity <= reorder_level AND status='available'")
    low_stock = cursor.fetchone()['count']

    cursor.execute("SELECT COUNT(*) AS count FROM `order` WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
    this_week = cursor.fetchone()['count']

    cursor.close()
    conn.close()

    return jsonify({
        "total_products": total_products,
        "total_drivers": total_drivers,
        "total_orders": total_orders,
        "pending_orders": pending_orders,
        "approved_orders": approved_orders,
        "rejected_orders": rejected_orders,
        "low_stock": low_stock,
        "this_week": this_week
    })


@dashboard_bp.route('/api/dashboard/orders-per-week', methods=['GET'])
def orders_per_week():
    """
    Return order counts for each day in a 7-day window.
    
    Optional query param: week_start (YYYY-MM-DD).
    Defaults to the current Monday.
    """
    week_start = request.args.get('week_start')

    if not week_start:
        week_start = (datetime.now() - timedelta(days=datetime.now().weekday())).strftime('%Y-%m-%d')

    start_date = datetime.strptime(week_start, '%Y-%m-%d')
    end_date = start_date + timedelta(days=6)

    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT 
            DATE(order_date) AS day,
            COUNT(*) AS count
        FROM `order`
        WHERE DATE(order_date) >= %s AND DATE(order_date) <= %s
        GROUP BY DATE(order_date)
        ORDER BY day ASC
    """, (week_start, end_date.strftime('%Y-%m-%d')))
    rows = cursor.fetchall()
    cursor.close()
    conn.close()

    days = []
    counts = []
    for i in range(7):
        d = (start_date + timedelta(days=i)).strftime('%Y-%m-%d')
        days.append(d)
        found = next((r for r in rows if str(r['day']) == d), None)
        counts.append(found['count'] if found else 0)

    return jsonify({
        "days": days,
        "counts": counts,
        "week_start": week_start,
        "week_end": end_date.strftime('%Y-%m-%d')
    })
