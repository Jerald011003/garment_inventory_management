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
checkAuth('admin');

$pageTitle = "Order Management";

// Handle individual order view if ID is provided
if (isset($_GET['id'])) {
    $orderId = $_GET['id'];
    
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
        header('Location: orders.php');
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
        <h1 style="font-size: 1.8rem; font-weight: 600; margin: 0; color: #e2e8f0;">Order Management - Order #<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></h1>
        <div style="display: flex; gap: 10px;">
            <button id="viewReceiptBtn" class="btn" style="padding: 8px 16px; background-color: #4F46E5; color: #e2e8f0; border: none; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-receipt"></i> View Receipt
            </button>
            <a href="orders.php" class="btn" style="padding: 8px 16px; background-color: #374151; color: #e2e8f0; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
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
    
    // Print receipt functionality
    document.getElementById('printReceiptBtn').addEventListener('click', function() {
        const printContent = document.getElementById('receiptContent').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = `
            <style>
                body { font-family: 'Courier New', monospace; color: #000; background: #fff; }
                @media print {
                    @page { margin: 0.5cm; }
                    body { width: 80mm; margin: 0 auto; }
                }
            </style>
            <div style="width: 100%; max-width: 80mm; margin: 0 auto;">
                ${printContent}
            </div>
        `;
        
        window.print();
        
        // Restore the original content
        document.body.innerHTML = originalContent;
        
        // Reinitialize the event listeners
        document.getElementById('viewReceiptBtn').addEventListener('click', function() {
            document.getElementById('receiptModal').style.display = 'block';
        });
        
        document.getElementById('printReceiptBtn').addEventListener('click', function() {
            // This will be overwritten but needs to be defined to avoid errors
            console.log('Print button clicked');
        });
        
        // Use setTimeout to ensure the DOM is updated
        setTimeout(function() {
            if (document.getElementById('printReceiptBtn')) {
                document.getElementById('printReceiptBtn').addEventListener('click', arguments.callee.caller);
            }
        }, 1000);
    });
    </script>
    
    <?php
    include '../../layouts/footer.php';
    exit; // End processing here for individual order view
}

// Filter settings
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$searchTerm = isset($_GET['search_term']) ? $_GET['search_term'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Fetch orders with customer details and item count
$baseQuery = "
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
    WHERE 1=1
";

$params = [];

// Add search condition if search term exists
if (!empty($searchTerm)) {
    $baseQuery .= " AND (
        o.id LIKE ? OR 
        c.name LIKE ? OR 
        p.name LIKE ? OR
        o.total_amount LIKE ?
    )";
    $searchParam = "%$searchTerm%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}

// Add date range filter if dates are provided
if (!empty($startDate)) {
    $baseQuery .= " AND DATE(o.created_at) >= ?";
    $params[] = $startDate;
}
if (!empty($endDate)) {
    $baseQuery .= " AND DATE(o.created_at) <= ?";
    $params[] = $endDate;
}

// Add status filter if not 'all'
if ($statusFilter !== 'all') {
    $baseQuery .= " AND o.status = ?";
    $params[] = $statusFilter;
}

// Add group by and order by
$baseQuery .= " GROUP BY o.id ORDER BY o.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($baseQuery);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for each status
$counts = [
    'all' => $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending' => $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'shipped' => $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn(),
    'delivered' => $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn(),  // Add comma here
    'cancelled' => $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn()
];

include '../../layouts/header.php';
?>

<div style="margin-bottom: 20px; display: flex; margin-top: 10px; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <h1 style="font-size: 1.8rem; font-weight: 600; margin: 0; color: #e2e8f0;">Order Management</h1>
    
    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <!-- Date Filter Form -->
        <form method="GET" action="" class="date-filter-form" style="display: flex; gap: 0.5rem; align-items: center;">
            <!-- Preserve other filters -->
            <?php if ($statusFilter !== 'all'): ?>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
            <?php endif; ?>
            <?php if (!empty($searchTerm)): ?>
            <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <?php endif; ?>
            
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input 
                    type="date" 
                    name="start_date" 
                    value="<?php echo htmlspecialchars($startDate); ?>"
                    style="padding: 8px; border-radius: 8px; border: 1px solid #4b5563; background-color: #1f2937; color: #e2e8f0;"
                >
                <span style="color: #9ca3af;">to</span>
                <input 
                    type="date" 
                    name="end_date" 
                    value="<?php echo htmlspecialchars($endDate); ?>"
                    style="padding: 8px; border-radius: 8px; border: 1px solid #4b5563; background-color: #1f2937; color: #e2e8f0;"
                >
                <button type="submit" style="padding: 8px 16px; background-color: #4F46E5; color: #e2e8f0; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <?php if (!empty($startDate) || !empty($endDate)): ?>
                <a href="?<?php 
                    $params = [];
                    if ($statusFilter !== 'all') $params[] = 'status=' . urlencode($statusFilter);
                    if (!empty($searchTerm)) $params[] = 'search_term=' . urlencode($searchTerm);
                    echo implode('&', $params);
                ?>" style="padding: 8px 16px; background-color: #374151; color: #e2e8f0; border-radius: 6px; text-decoration: none;">
                    <i class="fas fa-times"></i> Clear Dates
                </a>
                <?php endif; ?>
            </div>
        </form>

        <form method="GET" action="" class="search-form">
            <!-- Preserve current filters when searching -->
            <?php if ($statusFilter !== 'all'): ?>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
            <?php endif; ?>
            <?php if (!empty($startDate)): ?>
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            <?php endif; ?>
            <?php if (!empty($endDate)): ?>
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            <?php endif; ?>
            
            <div class="search-container" style="position: relative;">
                <input type="text" name="search_term" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search orders..." style="padding: 8px 12px 8px 36px; border-radius: 8px; border: 1px solid #4b5563; background-color: #1f2937; color: #e2e8f0; min-width: 250px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                <?php if (!empty($searchTerm)): ?>
                <button type="submit" style="position: absolute; right: 0; top: 0; height: 100%; background: none; border: none; padding: 0 10px; color: #6366F1; font-size: 0.875rem;">
                    <i class="fas fa-times"></i>
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Main Content Container with Full Height -->
<div style="display: flex; flex-direction: column; min-height: calc(100vh - 100px);">
    <!-- Filters and Tabs -->
    <div style="margin-bottom: 20px;">
        <div class="status-tabs" style="display: flex; gap: 1rem; border-bottom: 1px solid #374151; padding-bottom: 0.5rem;">
            <a href="?status=all<?php echo !empty($searchTerm) ? '&search_term=' . urlencode($searchTerm) : ''; ?>" class="status-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>" style="padding: 0.5rem 1rem; text-decoration: none; color: <?php echo $statusFilter === 'all' ? '#6366F1' : '#9ca3af'; ?>; font-weight: <?php echo $statusFilter === 'all' ? '600' : '400'; ?>; border-bottom: 2px solid <?php echo $statusFilter === 'all' ? '#6366F1' : 'transparent'; ?>;">
                All Orders <span class="count" style="background-color: #374151; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; margin-left: 5px;"><?php echo $counts['all']; ?></span>
            </a>
            <a href="?status=pending<?php echo !empty($searchTerm) ? '&search_term=' . urlencode($searchTerm) : ''; ?>" class="status-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" style="padding: 0.5rem 1rem; text-decoration: none; color: <?php echo $statusFilter === 'pending' ? '#F97316' : '#9ca3af'; ?>; font-weight: <?php echo $statusFilter === 'pending' ? '600' : '400'; ?>; border-bottom: 2px solid <?php echo $statusFilter === 'pending' ? '#F97316' : 'transparent'; ?>;">
                Pending <span class="count" style="background-color: #374151; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; margin-left: 5px;"><?php echo $counts['pending']; ?></span>
            </a>
            <a href="?status=shipped<?php echo !empty($searchTerm) ? '&search_term=' . urlencode($searchTerm) : ''; ?>" class="status-tab <?php echo $statusFilter === 'shipped' ? 'active' : ''; ?>" style="padding: 0.5rem 1rem; text-decoration: none; color: <?php echo $statusFilter === 'shipped' ? '#0EA5E9' : '#9ca3af'; ?>; font-weight: <?php echo $statusFilter === 'shipped' ? '600' : '400'; ?>; border-bottom: 2px solid <?php echo $statusFilter === 'shipped' ? '#0EA5E9' : 'transparent'; ?>;">
                Shipped <span class="count" style="background-color: #374151; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; margin-left: 5px;"><?php echo $counts['shipped']; ?></span>
            </a>
            <a href="?status=delivered<?php echo !empty($searchTerm) ? '&search_term=' . urlencode($searchTerm) : ''; ?>" class="status-tab <?php echo $statusFilter === 'delivered' ? 'active' : ''; ?>" style="padding: 0.5rem 1rem; text-decoration: none; color: <?php echo $statusFilter === 'delivered' ? '#10B981' : '#9ca3af'; ?>; font-weight: <?php echo $statusFilter === 'delivered' ? '600' : '400'; ?>; border-bottom: 2px solid <?php echo $statusFilter === 'delivered' ? '#10B981' : 'transparent'; ?>;">
                Delivered <span class="count" style="background-color: #374151; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; margin-left: 5px;"><?php echo $counts['delivered']; ?></span>
            </a>
            <a href="?status=cancelled<?php echo !empty($searchTerm) ? '&search_term=' . urlencode($searchTerm) : ''; ?>" class="status-tab <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>" style="padding: 0.5rem 1rem; text-decoration: none; color: <?php echo $statusFilter === 'cancelled' ? '#EF4444' : '#9ca3af'; ?>; font-weight: <?php echo $statusFilter === 'cancelled' ? '600' : '400'; ?>; border-bottom: 2px solid <?php echo $statusFilter === 'cancelled' ? '#EF4444' : 'transparent'; ?>;">
                Cancelled <span class="count" style="background-color: #374151; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; margin-left: 5px;"><?php echo $counts['cancelled']; ?></span>
            </a>
            </div>
    </div>

    <!-- Orders Table -->
    <div class="card" style="flex: 1; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); overflow: hidden; margin-bottom: 20px;">
        <!-- Table view for larger screens -->
        <div class="table-view d-none d-lg-block">
            <div class="table-container" style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #111827; text-align: left;">
                            <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Order ID</th>
                            <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Customer</th>
                            <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Date</th>
                            <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Total</th>
                            <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Items</th>
                            <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Products</th>
                            <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Status</th>
                            <th style="padding: 12px 16px; color: #9ca3af; font-weight: 500; border-bottom: 1px solid #374151;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): 
                                $status_class = match($order['status']) {
                                    'pending' => 'warning',
                                    'shipped' => 'info',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                            ?>
                        <tr style="border-bottom: 1px solid #374151;">
                            <td style="padding: 12px 16px; color: #e2e8f0;">
                                    <span style="font-weight: 500;">#<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></span>
                            </td>
                                <td style="padding: 12px 16px; color: #e2e8f0;"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td style="padding: 12px 16px; color: #e2e8f0;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td style="padding: 12px 16px; color: #e2e8f0;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td style="padding: 12px 16px; color: #e2e8f0;"><?php echo $order['total_quantity'] ?? 0; ?></td>
                                <td style="padding: 12px 16px; color: #e2e8f0;"><?php echo htmlspecialchars($order['product_names'] ?? 'N/A'); ?></td>
                            <td style="padding: 12px 16px;">
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
                                <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; font-weight: 500; text-transform: capitalize;">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 12px 16px;">
                                    <a href="?id=<?php echo $order['id']; ?>" style="background: none; border: none; color: #6366F1; cursor: pointer; padding: 8px 12px; border-radius: 6px; background-color: rgba(99, 102, 241, 0.1); font-size: 0.875rem; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="padding: 20px; text-align: center; color: #9ca3af;">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Card view for mobile screens -->
        <div class="card-view d-lg-none">
            <div class="orders-grid" style="display: grid; gap: 1rem; padding: 1rem;">
                <?php if (count($orders) > 0): ?>
                    <?php foreach ($orders as $order): 
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
                    <div class="order-card" style="background: #1E293B; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; overflow: hidden;">
                        <div class="order-card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: rgba(0, 0, 0, 0.2); border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                            <span style="font-weight: 600; color: #e2e8f0; font-size: 1.1rem;">#<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></span>
                            <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.875rem; background-color: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; font-weight: 500; text-transform: capitalize;">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>
                        <div class="order-card-body" style="padding: 1rem;">
                            <div style="margin-bottom: 0.5rem;">
                                <span style="color: #9ca3af;">Date:</span>
                                <span style="color: #e2e8f0; margin-left: 0.5rem;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div style="margin-bottom: 0.5rem;">
                                <span style="color: #9ca3af;">Customer:</span>
                                <span style="color: #e2e8f0; margin-left: 0.5rem;"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                            </div>
                            <div style="margin-bottom: 0.5rem;">
                                <span style="color: #9ca3af;">Items:</span>
                                <span style="color: #e2e8f0; margin-left: 0.5rem;"><?php echo $order['total_quantity'] ?? 0; ?></span>
                            </div>
                            <div style="margin-bottom: 0.5rem;">
                                <span style="color: #9ca3af;">Products:</span>
                                <span style="color: #e2e8f0; margin-left: 0.5rem;"><?php echo htmlspecialchars($order['product_names'] ?? 'N/A'); ?></span>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <span style="color: #9ca3af;">Total:</span>
                                <span style="color: #e2e8f0; margin-left: 0.5rem; font-weight: 600;">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div style="text-align: right;">
                                <a href="?id=<?php echo $order['id']; ?>" style="background: none; border: none; color: #6366F1; cursor: pointer; padding: 8px 12px; border-radius: 6px; background-color: rgba(99, 102, 241, 0.1); font-size: 0.875rem; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #9ca3af;">No orders found</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Display search, filter, and date range information if any filters are active -->
<?php if (!empty($searchTerm) || !empty($startDate) || !empty($endDate)): ?>
<div style="margin-top: 15px; color: #9ca3af; font-size: 0.875rem;">
    <?php 
    $filterParts = [];
    if (!empty($searchTerm)) {
        $filterParts[] = 'search term "<span style="color: #e2e8f0;">' . htmlspecialchars($searchTerm) . '</span>"';
    }
    if (!empty($startDate) || !empty($endDate)) {
        $datePart = 'date range ';
        if (!empty($startDate) && !empty($endDate)) {
            $datePart .= '<span style="color: #e2e8f0;">' . htmlspecialchars($startDate) . '</span> to <span style="color: #e2e8f0;">' . htmlspecialchars($endDate) . '</span>';
        } else if (!empty($startDate)) {
            $datePart .= 'from <span style="color: #e2e8f0;">' . htmlspecialchars($startDate) . '</span>';
        } else {
            $datePart .= 'until <span style="color: #e2e8f0;">' . htmlspecialchars($endDate) . '</span>';
        }
        $filterParts[] = $datePart;
    }
    
    $resultText = count($orders) > 0 ? 'Found ' . count($orders) . ' result' . (count($orders) > 1 ? 's' : '') : 'No results found';
    echo $resultText . ' for ' . implode(' and ', $filterParts);
    ?>
    <a href="?<?php echo $statusFilter !== 'all' ? 'status=' . htmlspecialchars($statusFilter) : ''; ?>" style="color: #6366F1; margin-left: 10px; text-decoration: none;">
        <i class="fas fa-times"></i> Clear all filters
    </a>
</div>
<?php endif; ?>

<style>
    /* Responsive styles */
    @media (max-width: 991px) {
        .status-tabs {
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 5px;
        }
        
        .status-tab {
            display: inline-block;
        }
        
        .orders-grid {
            grid-template-columns: 1fr;
        }
        
        .search-container input {
            min-width: 200px;
        }
    }

    @media (max-width: 576px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .search-container {
            width: 100%;
        }
        
        .search-container input {
            width: 100%;
        }
        
        .orders-grid {
            grid-template-columns: 1fr;
        }
        
        .order-card {
            margin-bottom: 1rem;
        }
    }

    /* General improvements */
    .order-card {
        transition: transform 0.2s ease;
    }
    
    .order-card:hover {
        transform: translateY(-2px);
    }
    
    .status-tabs::-webkit-scrollbar {
        height: 4px;
    }
    
    .status-tabs::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .status-tabs::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 2px;
    }

    /* Modal Animation */
    @keyframes modalFadeIn {
        from {opacity: 0; transform: translateY(-50px);}
        to {opacity: 1; transform: translateY(0);}
    }

    /* Add these styles to your existing CSS */
    @media (max-width: 768px) {
        .date-filter-form {
            flex-direction: column;
            align-items: stretch;
            width: 100%;
        }
        
        .date-filter-form input[type="date"] {
            width: 100%;
        }
        
        .date-filter-form button {
            width: 100%;
            margin-top: 0.5rem;
        }
    }
    
    /* Style the date inputs */
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
    }
</style>

<?php include '../../layouts/footer.php'; ?>