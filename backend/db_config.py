import mysql.connector
from mysql.connector import Error

def get_db_connection():
    try:
        connection = mysql.connector.connect(
            host="localhost",
            user="root",
            password="12345",
            database="kimfresh"
        )
        return connection
    except Error as e:
        print(f"❌ Database connection error: {e}")
        return None

# Test the connection when this file is run directly
if __name__ == "__main__":
    print("Testing database connection...")
    conn = get_db_connection()
    
    if conn:
        print("✅ Connection successful!")
        print(f"Connected to: {conn.get_server_info()}")
        conn.close()
        print("Connection closed.")
    else:
        print("❌ Connection failed!")
