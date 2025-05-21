<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/config/database.php';

$pageTitle = "Sales Dashboard";

// Fetch statistics
$stats = [
    'total_orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'delivered_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn(),
    'available_items' => $conn->query("SELECT COUNT(*) FROM products WHERE stock > 0")->fetchColumn(), // Added comma here
    'cancelled_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn(),
];

// Fetch recent orders with customer details and item count
$recent_orders = $conn->query("
    SELECT 
        o.id,
        o.created_at,
        o.status,
        o.total_amount,
        c.name as customer_name,
        SUM(oi.quantity) as total_quantity,
        GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetchAll();

include '../../layouts/header.php';
?>
<h1>Dashboard</h1>
<div class="stats-grid" style="margin-top: 40px;">
    <div class="stat-card">
        <h3>Total Orders</h3>
        <div class="value"><?php echo $stats['total_orders']; ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Pending Orders</h3>
        <div class="value"><?php echo $stats['pending_orders']; ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Delivered Orders</h3>
        <div class="value"><?php echo $stats['delivered_orders']; ?></div>
    </div>

    <div class="stat-card">
        <h3>Cancelled Orders</h3>
        <div class="value"><?php echo $stats['cancelled_orders']; ?></div>
    </div>

    <div class="stat-card">
        <h3>Available Items</h3>
        <div class="value"><?php echo $stats['available_items']; ?></div>
    </div>
</div>

<div class="card">
    <h3>Recent Orders</h3>
    <!-- Table view for larger screens -->
    <div class="table-container d-none d-md-block">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Products</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_orders)): ?>
                <tr>
                    <td colspan="8" class="text-center">No orders available</td>
                </tr>
                <?php else: ?>
                <?php foreach ($recent_orders as $order): 
                    $status_class = match($order['status']) {
                        'pending' => 'warning',
                        'shipped' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'secondary'
                    };
                ?>
                <tr>
                    <td>#<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo $order['total_quantity'] ?? 0; ?></td>
                    <td><?php echo htmlspecialchars($order['product_names']); ?></td>
                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td><span class="badge badge-<?php echo $status_class; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                    <td><a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">View</a></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Card view for mobile screens -->
    <div class="d-md-none">
        <?php if (empty($recent_orders)): ?>
            <div class="text-center py-4">No orders available</div>
        <?php else: ?>
            <div class="order-cards">
                <?php foreach ($recent_orders as $order): 
                    $status_class = match($order['status']) {
                        'pending' => 'warning',
                        'shipped' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'secondary'
                    };
                ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">#<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></h5>
                            <span class="badge badge-<?php echo $status_class; ?>"><?php echo ucfirst($order['status']); ?></span>
                        </div>
                        <div class="text-muted small"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="order-card-body">
                        <div class="mb-2">
                            <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Items:</strong> <?php echo $order['total_quantity'] ?? 0; ?>
                        </div>
                        <div class="mb-2">
                            <strong>Products:</strong> <?php echo htmlspecialchars($order['product_names']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                        <div class="text-end">
                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .order-cards {
        display: grid;
        gap: 1rem;
        padding: 1rem 0;
    }

    .order-card {
        background: var(--background-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1rem;
    }

    .order-card-header {
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .order-card-body {
        padding-top: 1rem;
    }

    @media (max-width: 824px) {
        .table-container {
            display: none;
        }
        
        .order-cards {
            display: grid;
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include '../../layouts/footer.php'; ?> 