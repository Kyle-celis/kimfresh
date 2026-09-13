import os
import mysql.connector
from dotenv import load_dotenv

load_dotenv()

def get_db_connection():
    connection = mysql.connector.connect(
        host=os.getenv('DB_HOST', 'localhost'),
        user=os.getenv('DB_USER', 'root'),
        password=os.getenv('DB_PASSWORD'),
        database=os.getenv('DB_NAME', 'kimfresh')
    )
    return connection

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
