"""
Dashboard model — all database queries for dashboard.
"""

from db_config import get_db_connection
from datetime import datetime, timedelta


def get_summary():
    """Return all summary counts."""
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

    return {
        "total_products": total_products,
        "total_drivers": total_drivers,
        "total_orders": total_orders,
        "pending_orders": pending_orders,
        "approved_orders": approved_orders,
        "rejected_orders": rejected_orders,
        "low_stock": low_stock,
        "this_week": this_week
    }


def get_orders_per_week(week_start):
    """Return 7-day order counts starting from week_start (YYYY-MM-DD)."""
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

    return {
        "days": days,
        "counts": counts,
        "week_start": week_start,
        "week_end": end_date.strftime('%Y-%m-%d')
    }


def get_recent_orders(limit=10):
    """Return last N orders with product names and qty."""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT 
            o.order_id,
            COALESCE(r.customer_code, o.retailer_id) AS customer,
            COALESCE(GROUP_CONCAT(p.name SEPARATOR ', '), '-') AS product,
            COALESCE(SUM(oi.quantity), 0) AS qty,
            o.total_amount,
            o.order_status
        FROM `order` o
        LEFT JOIN retailer r ON o.retailer_id = r.retailer_id
        LEFT JOIN order_item oi ON o.order_id = oi.order_id
        LEFT JOIN product p ON oi.product_id = p.product_id
        GROUP BY o.order_id
        ORDER BY o.order_id DESC
        LIMIT %s
    """, (limit,))
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return rows
