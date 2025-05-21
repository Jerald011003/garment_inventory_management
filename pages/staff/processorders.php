<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/auth/auth_check.php';
require_once '../../includes/config/database.php';
checkAuth('staff');

$pageTitle = "Process Orders";

// Handle individual order view if ID is provided
if (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    
    // Fetch order details
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.created_at,
            o.status,
            o.total_amount,
            c.name as customer_name,
            c.phone as customer_phone,
            c.address as customer_address,
            u.full_name as employee_name
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN users u ON o.created_by = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        // Order not found, redirect back to orders page
        header('Location: processorders.php');
        exit;
    }
    
    // Fetch order items with product details
    $stmt = $conn->prepare("
        SELECT 
            oi.id,
            oi.quantity,
            oi.unit_price,
            oi.total_price,
            p.name as product_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    include '../../layouts/header.php';
    ?>
    
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <h1 style="font-size: 1.8rem; font-weight: 600; margin: 0; color: #e2e8f0;"><?php echo $pageTitle; ?> - Order #<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></h1>
        <div style="display: flex; gap: 10px;">
            <button id="viewReceiptBtn" class="btn" style="padding: 8px 16px; background-color: #4F46E5; color: #e2e8f0; border: none; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-receipt"></i> View Receipt
            </button>
            <a href="processorders.php" class="btn" style="padding: 8px 16px; background-color: #374151; color: #e2e8f0; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>
    
    <div class="card" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); overflow: hidden; margin-bottom: 20px; padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div>
                <h3 style="font-size: 1.25rem; margin-bottom: 15px; color: #e2e8f0;">Order Information</h3>
                <div style="margin-bottom: 10px;">
                    <span style="color: #9ca3af; display: block; margin-bottom: 5px;">Order ID:</span>
                    <span style="color: #e2e8f0; font-weight: 500;">#<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div style="margin-bottom: 10px;">
                    <span style="color: #9ca3af; display: block; margin-bottom: 5px;">Date:</span>
                    <span style="color: #e2e8f0;"><?php echo date('F d, Y', strtotime($order['created_at'])); ?></span>
                </div>
                <div style="margin-bottom: 10px;">
                    <span style="color: #9ca3af; display: block; margin-bottom: 5px;">Status:</span>
                    <?php
                    $statusColor = '';
                    $statusBg = '';
                    switch ($order['status']) {
                        case 'pending':
                            $statusColor = '#F97316';
                            $statusBg = 'rgba(249, 115, 22, 0.1)';
                            break;
                        case 'shipped':
                            $statusColor = '#0EA5E9';
                            $statusBg = 'rgba(14, 165, 233, 0.1)';
                            break;
                        case 'delivered':
                            $statusColor = '#10B981';
                            $statusBg = 'rgba(16, 185, 129, 0.1)';
                            break;
                        case 'cancelled':
                            $statusColor = '#EF4444';
                            $statusBg = 'rgba(239, 68, 68, 0.1)';
                            break;
                    }
                    ?>
                    <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.875rem; background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; font-weight: 500; text-transform: capitalize;">
                        <?php echo $order['status']; ?>
                    </span>
                </div>
                <div style="margin-bottom: 10px;">
                    <span style="color: #9ca3af; display: block; margin-bottom: 5px;">Total Amount:</span>
                    <span style="color: #e2e8f0; font-weight: 600; font-size: 1.25rem;">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <div>
                <h3 style="font-size: 1.25rem; margin-bottom: 15px; color: #e2e8f0;">Customer Information</h3>
                <div style="margin-bottom: 10px;">
                    <span style="color: #9ca3af; display: block; margin-bottom: 5px;">Name:</span>
                    <span style="color: #e2e8f0;"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                </div>
                <div style="margin-bottom: 10px;">
                    <span style="color: #9ca3af; display: block; margin-bottom: 5px;">Phone:</span>
                    <span style="color: #e2e8f0;"><?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></span>
                </div>
                <div style="margin-bottom: 10px;">
                    <span style="color: #9ca3af; display: block; margin-bottom: 5px;">Address:</span>
                    <span style="color: #e2e8f0;"><?php echo htmlspecialchars($order['customer_address'] ?? 'N/A'); ?></span>
                </div>

                <h3 style="font-size: 1.25rem; margin-bottom: 15px; color: #e2e8f0;">Sales Representative</h3>

                <div style="margin-bottom: 10px;">
                    <span style="color: #9ca3af; display: block; margin-bottom: 5px;">Name:</span>
                    <span style="color: #e2e8f0;"><?php echo htmlspecialchars($order['employee_name'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); overflow: hidden; margin-bottom: 20px;">
        <h3 style="font-size: 1.25rem; padding: 15px 20px; margin: 0; border-bottom: 1px solid #374151; color: #e2e8f0;">Order Items</h3>
        <div class="table-container" style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #111827; text-align: left;">
                        <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Product</th>
                        <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Quantity</th>
                        <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Unit Price</th>
                        <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                    <tr style="border-bottom: 1px solid #374151;">
                        <td style="padding: 12px 16px; color: #e2e8f0;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td style="padding: 12px 16px; color: #e2e8f0;"><?php echo $item['quantity']; ?></td>
                        <td style="padding: 12px 16px; color: #e2e8f0;">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td style="padding: 12px 16px; color: #e2e8f0;">₱<?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background-color: rgba(17, 24, 39, 0.5);">
                        <td colspan="3" style="padding: 12px 16px; text-align: right; font-weight: 600; color: #e2e8f0;">Total</td>
                        <td style="padding: 12px 16px; color: #e2e8f0; font-weight: 600;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.5);">
        <div class="modal-content" style="background-color: #1E293B; margin: 5% auto; padding: 0; border-radius: 8px; width: 90%; max-width: 400px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #374151;">
                <h3 style="margin: 0; font-size: 1.25rem; color: #e2e8f0;">Order Receipt</h3>
                <div>
                    <button onclick="closeReceiptModal()" style="background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 1.25rem;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div id="receiptContent" style="padding: 20px; color: #e2e8f0; font-family: 'Courier New', monospace; background-color: white;">
                <div style="text-align: center; padding-bottom: 15px; border-bottom: 1px dashed #ccc;">
                    <h2 style="margin: 0; font-size: 1.2rem; font-weight: bold; color: #000;">Triple S Garments</h2>
                    <p style="margin: 5px 0; font-size: 0.9rem; color: #333;">123 Fashion Street, Style City</p>
                    <p style="margin: 5px 0; font-size: 0.9rem; color: #333;">Tel: (123) 456-7890</p>
                </div>
                
                <div style="margin: 15px 0; text-align: center;">
                    <p style="margin: 5px 0; font-size: 1rem; font-weight: bold; color: #000;">SALES RECEIPT</p>
                    <p style="margin: 5px 0; font-size: 0.9rem; color: #333;">Order #: <?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></p>
                    <p style="margin: 5px 0; font-size: 0.9rem; color: #333;">Date: <?php echo date('m/d/Y h:i A', strtotime($order['created_at'])); ?></p>
                </div>
                
                <div style="margin: 15px 0;">
                    <p style="margin: 5px 0; font-size: 0.9rem; font-weight: bold; color: #000;">Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <?php if(!empty($order['customer_phone'])): ?>
                    <p style="margin: 5px 0; font-size: 0.9rem; color: #333;">Phone: <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    <?php endif; ?>
                    <?php if(!empty($order['customer_address'])): ?>
                    <p style="margin: 5px 0; font-size: 0.9rem; color: #333;">Address: <?php echo htmlspecialchars($order['customer_address']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div style="border-top: 1px dashed #ccc; border-bottom: 1px dashed #ccc; padding: 10px 0;">
                    <table style="width: 100%; font-size: 0.9rem; color: #333;">
                        <thead>
                            <tr style="text-align: left;">
                                <th style="padding: 5px 0;">Item</th>
                                <th style="padding: 5px 0; text-align: center;">Qty</th>
                                <th style="padding: 5px 0; text-align: right;">Price</th>
                                <th style="padding: 5px 0; text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td style="padding: 5px 0;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td style="padding: 5px 0; text-align: center;"><?php echo $item['quantity']; ?></td>
                                <td style="padding: 5px 0; text-align: right;">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td style="padding: 5px 0; text-align: right;">₱<?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin: 15px 0;">
                    <table style="width: 100%; font-size: 0.9rem; color: #333;">
                        <tr>
                            <td style="text-align: right; padding: 5px 0; font-weight: bold;">Subtotal:</td>
                            <td style="text-align: right; padding: 5px 0;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                        <tr style="font-weight: bold; font-size: 1rem;">
                            <td style="text-align: right; padding: 5px 0; border-top: 1px dashed #ccc;">TOTAL:</td>
                            <td style="text-align: right; padding: 5px 0; border-top: 1px dashed #ccc;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </table>
                </div>
                
                <div style="margin: 20px 0; text-align: center; padding-top: 15px; border-top: 1px dashed #ccc;">
                    <p style="margin: 5px 0; font-size: 0.9rem; font-weight: bold; text-transform: uppercase; color: #000;"><?php echo $order['status']; ?></p>
                    <p style="margin: 15px 0; font-size: 0.9rem; color: #333;">Thank you for your purchase!</p>
                    <p style="margin: 5px 0; font-size: 0.8rem; color: #666;">Keep this receipt for your records</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Open the receipt modal when the button is clicked
    document.getElementById('viewReceiptBtn').addEventListener('click', function() {
        document.getElementById('receiptModal').style.display = 'block';
    });
    
    // Close the receipt modal
    function closeReceiptModal() {
        document.getElementById('receiptModal').style.display = 'none';
    }
    
    // Close modal when clicking outside the modal content
    window.onclick = function(event) {
        const modal = document.getElementById('receiptModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>
    
    <?php
    include '../../layouts/footer.php';
    exit; // End processing here for individual order view
}

// Get order statistics
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$shippedOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn();
$deliveredOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
$cancelledOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn();

// Process order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $newStatus = isset($_POST['new_status']) ? $_POST['new_status'] : '';
    
    // Validate the new status value
    if (in_array($newStatus, ['pending', 'shipped', 'delivered', 'cancelled'])) {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Update order status
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $orderId]);
            
            // Log the activity
            $userId = $_SESSION['user_id'] ?? 0;
            $description = "Updated order #" . str_pad($orderId, 3, '0', STR_PAD_LEFT) . " status to " . ucfirst($newStatus);
            
            $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description) VALUES (?, ?, ?)");
            $stmt->execute([$userId, "Order Status Update", $description]);
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['success_message'] = "Order status updated successfully.";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $_SESSION['error_message'] = "Error updating order status: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Invalid status value.";
    }
    
    // Redirect back to orders page
    header("Location: processorders.php");
    exit;
}

// Fetch all orders with customer details
$stmt = $conn->query("
    SELECT 
        o.id,
        o.created_at,
        o.status,
        o.total_amount,
        c.name as customer_name,
        c.phone as customer_phone,
        c.address as customer_address,
        (SELECT GROUP_CONCAT(p.name SEPARATOR ', ') 
         FROM order_items oi 
         JOIN products p ON oi.product_id = p.id 
         WHERE oi.order_id = o.id) as products,
        (SELECT SUM(oi.quantity) 
         FROM order_items oi 
         WHERE oi.order_id = o.id) as total_quantity
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    ORDER BY o.created_at DESC
");

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../layouts/header.php';
?>

<h1> Orders</h1>
<div class="stats-grid" style="margin-top: 40px;">
    <div class="stat-card">
        <h3>Total Orders</h3>
        <div class="value"><?php echo $totalOrders; ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Pending</h3>
        <div class="value"><?php echo $pendingOrders; ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Shipped</h3>
        <div class="value"><?php echo $shippedOrders; ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Delivered</h3>
        <div class="value"><?php echo $deliveredOrders; ?></div>
    </div>

    <div class="stat-card">
        <h3>Cancelled</h3>
        <div class="value"><?php echo $cancelledOrders; ?></div>
    </div>
</div>

<div class="card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Process Orders</h3>
        <div class="search-filter">
            <input type="text" class="form-control" style="background-color: white; color: black;" id="orderSearch" placeholder="Search orders...">
        </div>
    </div>
    <div class="table-container d-none d-lg-block">
        <table class="table">
            <thead>
                <tr>
                    <th class="text-white text-center">Order ID</th>
                    <th class="text-white text-center">Customer Name</th>
                    <th class="text-white text-center">Products</th>
                    <th class="text-white text-center">Quantity</th>
                    <th class="text-white text-center">Contact Number</th>
                    <th class="text-white text-center">Amount</th>
                    <th class="text-white text-center">Order Date</th>
                    <th class="text-white text-center">Status</th>
                    <th class="text-white text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($orders) > 0): ?>
                    <?php foreach ($orders as $order):
                        // Set status badge class
                        $statusClass = match($order['status']) {
                            'pending' => 'bg-warning',
                            'shipped' => 'bg-info',
                            'delivered' => 'bg-success',
                            'cancelled' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                        
                        // Format status text
                        $statusText = ucfirst($order['status']);
                    ?>
                    <tr>
                        <td class="text-white text-center">#<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td class="text-white text-center"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td class="text-white text-center"><?php echo htmlspecialchars($order['products'] ?? 'N/A'); ?></td>
                        <td class="text-white text-center"><?php echo $order['total_quantity'] ?? 0; ?></td>
                        <td class="text-white text-center"><?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></td>
                        <td class="text-white text-center">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td class="text-white text-center"><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                        <td class="text-center"><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                
                                <?php if ($order['status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-info" onclick="updateStatus('<?php echo $order['id']; ?>', 'shipped')">
                                    <i class="fas fa-truck"></i> Ship
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'shipped'): ?>
                                <button class="btn btn-sm btn-success" onclick="updateStatus('<?php echo $order['id']; ?>', 'delivered')">
                                    <i class="fas fa-check"></i> Delivered
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-white">No orders found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Card view for mobile screens -->
    <div class="d-lg-none">
        <?php if (count($orders) === 0): ?>
            <div class="text-center py-4 text-white">No orders found</div>
        <?php else: ?>
            <div class="order-cards">
                <?php foreach ($orders as $order):
                    $statusClass = match($order['status']) {
                        'pending' => 'bg-warning',
                        'shipped' => 'bg-info',
                        'delivered' => 'bg-success',
                        'cancelled' => 'bg-danger',
                        default => 'bg-secondary'
                    };
                    $statusText = ucfirst($order['status']);
                ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">#<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></h5>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                        </div>
                        <h6 class="customer-name mb-0"><?php echo htmlspecialchars($order['customer_name']); ?></h6>
                    </div>
                    <div class="order-card-body">
                        <div class="mb-2">
                            <strong>Date:</strong> <?php echo date('Y-m-d', strtotime($order['created_at'])); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Products:</strong> <?php echo htmlspecialchars($order['products'] ?? 'N/A'); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Quantity:</strong> <?php echo $order['total_quantity'] ?? 0; ?> items
                        </div>
                        <div class="mb-2">
                            <strong>Contact:</strong> <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <?php if ($order['status'] === 'pending'): ?>
                            <button class="btn btn-sm btn-info" onclick="updateStatus('<?php echo $order['id']; ?>', 'shipped')">
                                <i class="fas fa-truck"></i> Ship
                            </button>
                            <?php endif; ?>
                            <?php if ($order['status'] === 'shipped'): ?>
                            <button class="btn btn-sm btn-success" onclick="updateStatus('<?php echo $order['id']; ?>', 'delivered')">
                                <i class="fas fa-check"></i> Delivered
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm" method="post" action="processorders.php">
                    <input type="hidden" name="action" value="update_status">
                    <div class="mb-3">
                        <label class="form-label text-dark">Order ID: <span id="modalOrderId" class="text-dark"></span></label>
                    </div>
                    <div class="mb-4">
                        <p class="text-dark" id="confirmationMessage">Are you sure you want to update the status of this order?</p>
                    </div>
                    <input type="hidden" id="newStatus" name="new_status">
                    <input type="hidden" id="orderId" name="order_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="updateStatusForm" class="btn btn-primary">Update Status</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Add these styles to ensure proper text colors */
    .modal-content {
        background-color: white;
    }
    
    .modal input, 
    .modal select,
    .modal textarea {
        color: #333 !important;
    }
    
    .table tbody tr:hover td {
        color: white !important;
    }

    /* Button spacing */
    .btn-sm {
        margin: 0 2px;
    }

    /* Status badge styles */
    .badge {
        padding: 8px 12px;
        font-size: 0.85rem;
    }

    /* Add these styles for better table layout */
    .table {
        width: 100%;
        white-space: nowrap;
    }

    .table-container {
        overflow-x: auto;
        margin-bottom: 1rem;
    }

    /* Make product column wrap if needed */
    .table td:nth-child(3) {
        white-space: normal;
        max-width: 200px;
    }
    
    /* Center align all table content */
    .table th,
    .table td {
        vertical-align: middle;
    }

    /* Add hover effect */
    .table tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* Card view styles */
    .order-cards {
        display: grid;
        gap: 1rem;
        padding: 1rem 0;
    }

    .order-card {
        background: var(--background-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
    }

    .order-card-header {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        background-color: rgba(0, 0, 0, 0.2);
    }

    .order-card-body {
        padding: 1rem;
    }

    .customer-name {
        font-size: 1.1rem;
        color: var(--text-primary);
    }

    .order-card .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.875rem;
        white-space: nowrap;
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .order-card .d-flex.gap-2 {
        flex-wrap: wrap;
        gap: 0.5rem !important;
    }

    @media (min-width: 992px) {
        .d-lg-none {
            display: none !important;
        }
    }

    @media (max-width: 1100px) {
        .order-cards {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
    }

    @media (max-width: 991px) {
        .order-cards {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
    }

    @media (max-width: 745px) {
        .order-cards {
            grid-template-columns: 1fr;
        }
    }

    /* Improve card content layout */
    .order-card-body > div {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.5rem;
    }

    .order-card-body > div strong {
        color: #9ca3af;
        min-width: 80px;
    }

    .order-card-body > div:not(:last-child) {
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .order-card-body .d-flex.gap-2 {
        padding-top: 0.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin-top: 0.5rem;
        justify-content: stretch;
    }

    /* Make products text wrap properly */
    .order-card-body > div.mb-2:nth-child(2) {
        align-items: flex-start;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Display success/error messages if they exist
    <?php if (isset($_SESSION['success_message'])): ?>
        const successAlert = document.createElement('div');
        successAlert.className = 'alert alert-success alert-dismissible fade show';
        successAlert.innerHTML = `
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.querySelector('.stats-grid').before(successAlert);
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-danger alert-dismissible fade show';
        errorAlert.innerHTML = `
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.querySelector('.stats-grid').before(errorAlert);
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    // Order search functionality
    const orderSearch = document.getElementById('orderSearch');
    if (orderSearch) {
        orderSearch.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            const cards = document.querySelectorAll('.order-card');
            
            // Search in table rows
            rows.forEach(row => {
                const orderId = row.cells[0].textContent.toLowerCase();
                const customerName = row.cells[1].textContent.toLowerCase();
                const products = row.cells[2].textContent.toLowerCase();
                
                if (orderId.includes(searchTerm) || 
                    customerName.includes(searchTerm) || 
                    products.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Search in cards
            cards.forEach(card => {
                const orderId = card.querySelector('h5').textContent.toLowerCase();
                const customerName = card.querySelector('.customer-name').textContent.toLowerCase();
                const products = card.querySelector('.order-card-body').textContent.toLowerCase();
                
                if (orderId.includes(searchTerm) || 
                    customerName.includes(searchTerm) || 
                    products.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
    
    // Setup for status update functionality
    window.updateStatus = function(orderId, newStatus) {
        document.getElementById('modalOrderId').textContent = '#' + orderId.toString().padStart(3, '0');
        document.getElementById('orderId').value = orderId;
        document.getElementById('newStatus').value = newStatus;
        
        // Set confirmation message based on new status
        const statusText = newStatus === 'shipped' ? 'ship' : 'mark as delivered';
        document.getElementById('confirmationMessage').textContent = 
            `Are you sure you want to ${statusText} this order?`;
        
        // Show modal
        let modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
        modal.show();
    };
});
</script>

<?php include '../../layouts/footer.php'; ?>