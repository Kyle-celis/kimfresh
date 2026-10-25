from flask import Blueprint, jsonify
from db_config import get_db_connection
from datetime import datetime, timedelta

store_bp = Blueprint('store', __name__)

@store_bp.route('/api/store/status', methods=['GET'])
def get_store_status():
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM store_settings WHERE id = 1")
        store = cursor.fetchone()
        cursor.close()
        conn.close()

        if not store:
            return jsonify({"is_open": True, "message": "", "status_type": "normal"})

        is_open = True
        warning_message = ""
        status_type = "normal" # Can be "normal", "closing_soon", "opening_soon", or "closed"

        if store['manual_override'] == 0:
            is_open = False
            warning_message = "The store is currently closed."
            status_type = "closed"
        elif store['manual_override'] == 1:
            is_open = True
        else:
            current_dt = datetime.now()
            
            open_time = datetime.strptime(str(store['open_time']), '%H:%M:%S').time()
            close_time = datetime.strptime(str(store['close_time']), '%H:%M:%S').time()
            
            open_dt = datetime.combine(current_dt.date(), open_time)
            close_dt = datetime.combine(current_dt.date(), close_time)

            if open_time <= close_time:
                # Normal schedule (e.g., 08:00 to 17:00)
                is_open = (current_dt.time() >= open_time and current_dt.time() <= close_time)
                
                if is_open:
                    # Check if closing soon
                    if (close_dt - current_dt).total_seconds() <= 900:
                        mins = int((close_dt - current_dt).total_seconds() / 60)
                        warning_message = f"The store will close in {mins} minutes!"
                        status_type = "closing_soon"
                else:
                    # Store is closed. Check if it opens soon.
                    if current_dt < open_dt:
                        # Opening later today
                        if (open_dt - current_dt).total_seconds() <= 900:
                            mins = int((open_dt - current_dt).total_seconds() / 60)
                            warning_message = f"The store will open in {mins} minutes!"
                            status_type = "opening_soon"
                        else:
                            warning_message = f"The store is currently closed. It opens at {open_time.strftime('%I:%M %p')}."
                            status_type = "closed"
                    else:
                        # Opening tomorrow
                        warning_message = f"The store is currently closed. It opens tomorrow at {open_time.strftime('%I:%M %p')}."
                        status_type = "closed"
            else:
                # Overnight schedule (e.g., 22:00 to 06:00)
                is_open = (current_dt.time() >= open_time or current_dt.time() <= close_time)
                if not is_open:
                    warning_message = "The store is currently closed."
                    status_type = "closed"

        return jsonify({
            "is_open": is_open,
            "message": warning_message,
            "status_type": status_type
        })
    except Exception as e:
        return jsonify({"is_open": True, "message": "", "status_type": "normal"})