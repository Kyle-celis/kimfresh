"""Application configuration constants."""

# OTP settings
OTP_LENGTH = 6
OTP_EXPIRY_MINUTES = 5

# Stock defaults
DEFAULT_REORDER_LEVEL = 5
DEFAULT_STOCK_QUANTITY = 0

# Delivery status mapping
DELIVERY_STATUS_MAP = {
    'assigned': 'approved',
    'picked_up': 'waiting_for_pickup',
    'in_transit': 'in_transit',
    'delivered': 'delivered'
}

# Order statuses
ORDER_STATUS_PENDING = 'pending'
ORDER_STATUS_APPROVED = 'approved'
ORDER_STATUS_REJECTED = 'rejected'
ORDER_STATUS_DELIVERED = 'delivered'

# Product statuses
PRODUCT_STATUS_AVAILABLE = 'available'
PRODUCT_STATUS_INACTIVE = 'inactive'
