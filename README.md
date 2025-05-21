# Triple S Garments Inventory Management System

A web-based inventory management system for Triple S Garment Business. This application helps manage products, customers, orders, and inventory levels.

## Features

- User authentication with role-based access (Admin, Sales, Staff)
- Product management with categories
- Customer management
- Order processing with status tracking
- Inventory management
- Responsive design for all devices

## Requirements

- XAMPP Control Panel
- Git

## Installation Guide

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/garment-inventory-management.git
cd garment-inventory-management
```

### 2. Set Up XAMPP

1. Download and install XAMPP from [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)
2. Start the XAMPP Control Panel
3. Start the Apache and MySQL services

### 3. Project Setup

1. Move or copy the cloned repository to the XAMPP htdocs directory:
   - `C:\xampp\htdocs\garment-inventory-management`

### 4. Database Setup

1. Open your web browser and navigate to `http://localhost/phpmyadmin`
2. Click on "Import" in the top menu
3. Import the `garment_inventory.sql` file under `databases` folder
4. Click "Go" to execute the import

### 5. Run the Application

1. Open your web browser and navigate to:
   ```
   http://localhost/garment-inventory-management
   ```

2. Login with the default credentials:
   - Admin: 
     - Username: admin
     - Password: admin
   - Sales:
     - Username: sales
     - Password: sales
   - Staff:
     - Username: staff
     - Password: staff
