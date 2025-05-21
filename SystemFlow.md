# Garment Inventory Management System - Basic Flow

## Overview
The Garment Inventory Management System is a web-based application designed to help manage inventory, track orders, and handle sales for a clothing business. This document explains the basic flow of the system for beginners.

## System Architecture
The system is built using:
- PHP for server-side processing
- MySQL for database storage
- HTML, CSS, and JavaScript for the front-end
- Bootstrap for responsive design

## User Roles
The system has three types of users, each with different permissions:

1. **Admin** - Full access to all features
2. **Sales** - Manage inventory and customer orders
3. **Staff** - Process orders and manage warehouse inventory

## Database Structure
The system uses the following main tables:
- Users - Store user accounts and authentication information
- Products - Inventory items with details like price and stock count
- Categories - Product categories (T-shirts, Jeans, etc.)
- Customers - Customer information
- Orders - Order information
- Order_items - Line items for each order
- Activity_log - User actions for auditing

## Basic System Flow

### 1. Authentication
- Users log in via the main login page (index.php)
- The system validates credentials against the user database
- Users are redirected to their role-specific dashboard

### 2. Admin Workflow
- **Dashboard:** View sales summary, recent orders, and inventory status
- **Inventory:** Add, edit, and delete products and categories
- **Orders:** Manage customer orders and track order status

### 3. Sales Workflow
- **Dashboard:** View sales data and customer activity
- **Inventory:** Check product availability
- **Create Orders:** Add new customer orders and process sales

### 4. Staff Workflow
- **Process Orders:** Fulfill pending orders
- **Manage Inventory:** Update stock levels as products are shipped
- **Warehouse:** Track inventory movement in the warehouse
- **Deliveries:** Monitor order deliveries and update status

## Detailed Process Flows

### Product Management Flow
1. **Creating New Products** (Admin)
   - Navigate to Inventory page
   - Click "Add New Product" button
   - Fill in product details:
     * Product name
     * Select category from dropdown
     * Set price
     * Set initial stock quantity
   - Click "Save" to create the product
   - The new product appears in the inventory list

2. **Updating Products** (Admin)
   - Find product in inventory list
   - Click "Edit" button
   - Update details as needed
   - Click "Save" to apply changes

3. **Managing Categories** (Admin)
   - Navigate to Inventory page
   - Click "Manage Categories" button
   - Add, edit, or delete product categories

### Order Processing Flow
1. **Creating an Order** (Sales)
   - Navigate to Inventory page
   - Browse available products
   - Click "Create Order" button
   - Select or add a customer
   - Add products to the order:
     * Select products from the list
     * Set quantities for each item
     * System calculates prices automatically
   - Review order details
   - Click "Submit Order" to complete

2. **Order Assessment** (Staff)
   - Staff logs in and views pending orders
   - Click on an order to see details
   - Review ordered items and quantities
   - Check inventory availability
   - Click "Process Order" to begin fulfillment

3. **Order Status Updates** (Staff)
   - Navigate to Process Orders page
   - Select an order being processed
   - Update status as the order progresses:
     * "Pending" → "Processing" → "Packed" → "Shipped" → "Delivered"
   - Each status change is logged with timestamp
   - Inventory is automatically updated when order is processed

4. **Order Viewing** (All Roles)
   - **Admin:** Can view all orders from Orders page
   - **Sales:** Can view orders they created and check status
   - **Staff:** Focuses on orders that need processing

5. **Order Details** (All Roles)
   - Click on any order to view complete details:
     * Customer information
     * Line items with quantities and prices
     * Order status and history
     * Total amount
     * Processing notes

### Inventory Management Flow
1. **Stock Updates** (Admin & Staff)
   - Navigate to Inventory Management
   - Update stock quantities manually when receiving new shipments
   - System automatically reduces stock when orders are processed

2. **Low Stock Alerts**
   - Dashboard shows warnings for low stock items
   - Admin can set minimum stock thresholds for products

### Customer Management Flow
1. **Adding Customers** (Sales)
   - Add customer details during order creation
   - Enter name, contact information, and address

2. **Customer History**
   - View previous orders for any customer
   - Check customer purchase patterns

## Getting Started
1. Log in with your assigned username and password
2. Navigate to your dashboard based on your role
3. Use the navigation menu to access different functions
4. Follow on-screen instructions to complete tasks

## Demo Walkthrough Sequence
For client demonstrations, follow this sequence:
1. Log in as Admin to show system overview
2. Create new product categories
3. Add new products to inventory
4. Switch to Sales user
5. Create a new customer order
6. Switch to Staff user to show order processing
7. Update order status to show workflow
8. Return to Admin to view reports and overall system status

## Security Features
- Session-based authentication
- Role-based access control
- Activity logging for all important actions

This guide should help you understand and demonstrate the complete flow of the Garment Inventory Management System during client presentations. 