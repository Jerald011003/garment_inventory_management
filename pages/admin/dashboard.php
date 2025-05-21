<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../includes/auth/auth_check.php';
require_once '../../includes/config/database.php';
checkAuth('admin');

$pageTitle = "Overall Reports";

// Default to current month
$timeRange = isset($_GET['time_range']) ? $_GET['time_range'] : 'this_month';

// Calculate date ranges based on selected time range
$endDate = date('Y-m-d 23:59:59');
switch ($timeRange) {
    case 'this_month':
        $startDate = date('Y-m-01 00:00:00');
        break;
    case 'last_month':
        $startDate = date('Y-m-01 00:00:00', strtotime('-1 month'));
        $endDate = date('Y-m-t 23:59:59', strtotime('-1 month'));
        break;
    case 'last_3_months':
        $startDate = date('Y-m-01 00:00:00', strtotime('-3 months'));
        break;
    case 'this_year':
        $startDate = date('Y-01-01 00:00:00');
        break;
    default:
        $startDate = date('Y-m-01 00:00:00');
}

try {
    // Get total stock items and value
    $stmt = $conn->query("SELECT 
        COUNT(*) as total_items,
        SUM(stock) as total_stock,
        SUM(stock * price) as total_value
        FROM products");
    $stockStats = $stmt->fetch();

    // Get warehouse outflow (items shipped)
    $stmt = $conn->prepare("SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(oi.quantity), 0) as total_items_shipped
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'shipped'
        AND o.created_at BETWEEN ? AND ?");
    $stmt->execute([$startDate, $endDate]);
    $outflowStats = $stmt->fetch();

    // Get delivered items
    $stmt = $conn->prepare("SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(oi.quantity), 0) as total_items_delivered
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'delivered'
        AND o.created_at BETWEEN ? AND ?");
    $stmt->execute([$startDate, $endDate]);
    $deliveredStats = $stmt->fetch();

    // Get total revenue
    $stmt = $conn->prepare("SELECT 
        COALESCE(SUM(o.total_amount), 0) as total_revenue
        FROM orders o
        WHERE o.status IN ('shipped', 'delivered')
        AND o.created_at BETWEEN ? AND ?");
    $stmt->execute([$startDate, $endDate]);
    $revenueStats = $stmt->fetch();

    // Get recently added stock
    $stmt = $conn->query("SELECT 
        p.name as product_name,
        c.name as category_name,
        p.stock,
        p.created_at
        FROM products p
        JOIN categories c ON p.category_id = c.id
        ORDER BY p.created_at DESC
        LIMIT 3");
    $recentStock = $stmt->fetchAll();

    // Get low stock items
    $stmt = $conn->query("SELECT 
        p.name as product_name,
        c.name as category_name,
        p.stock
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.stock <= 10
        ORDER BY p.stock ASC
        LIMIT 3");
    $lowStock = $stmt->fetchAll();

    $dailySales = $conn->prepare("
    SELECT COALESCE(SUM(total_amount), 0) as daily_sales
    FROM orders
    WHERE DATE(created_at) = CURDATE()
    ");
    $dailySales->execute();
    $dailySalesAmount = $dailySales->fetchColumn();

    // Get weekly sales (last 7 days)
    $weeklySales = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as weekly_sales
        FROM orders
        WHERE created_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE() + INTERVAL 1 DAY
    ");
    $weeklySales->execute();
    $weeklySalesAmount = $weeklySales->fetchColumn();

    // Get daily and weekly order counts
    $dailyOrders = $conn->prepare("
        SELECT COUNT(*) as count
        FROM orders
        WHERE DATE(created_at) = CURDATE()
    ");
    $dailyOrders->execute();
    $dailyOrderCount = $dailyOrders->fetchColumn();

    $weeklyOrders = $conn->prepare("
        SELECT COUNT(*) as count
        FROM orders
        WHERE created_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE() + INTERVAL 1 DAY
    ");
    $weeklyOrders->execute();
    $weeklyOrderCount = $weeklyOrders->fetchColumn();



} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Error fetching dashboard data";
}

include '../../layouts/header.php';
?>

<div style="margin-bottom: 20px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <h1 style="font-size: 1.8rem; font-weight: 600; margin: 0;"><?php echo $pageTitle; ?></h1>
    
    <div style="display: flex; gap: 1rem; align-items: center;">
        <select id="time-range-selector" class="select-control" style="min-width: 150px; padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc;">
            <option value="this_month" <?php echo $timeRange == 'this_month' ? 'selected' : ''; ?>>This Month</option>
            <option value="last_month" <?php echo $timeRange == 'last_month' ? 'selected' : ''; ?>>Last Month</option>
            <option value="last_3_months" <?php echo $timeRange == 'last_3_months' ? 'selected' : ''; ?>>Last 3 Months</option>
            <option value="this_year" <?php echo $timeRange == 'this_year' ? 'selected' : ''; ?>>This Year</option>
        </select>
    </div>
</div>

<div class="stats-container">
    <!-- Total Stock Card -->
    <div class="dashboard-card">
        <div style="position: absolute; top: 0; right: 0; width: 100px; height: 100px; background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0) 70%); border-radius: 50%;"></div>
        
        <div class="card-header">
            <div class="card-icon purple">
                <i class="fas fa-box"></i>
            </div>
            <h3>Total Stock Items</h3>
        </div>
        
        <div class="card-value-container">
            <div class="card-value">
                <?php echo number_format($stockStats['total_stock']); ?>
            </div>
            <div class="card-subtext">
                (<?php echo number_format($stockStats['total_items']); ?> items)
            </div>
        </div>
        
        <div class="card-footer">
            Total Value: ₱<?php echo number_format($stockStats['total_value'], 2); ?>
        </div>
    </div>
    
    <!-- Warehouse Outflow Card -->
    <div class="dashboard-card">
        <div style="position: absolute; top: 0; right: 0; width: 100px; height: 100px; background: radial-gradient(circle, rgba(236, 72, 153, 0.15) 0%, rgba(236, 72, 153, 0) 70%); border-radius: 50%;"></div>
        
        <div class="card-header">
            <div class="card-icon pink">
                <i class="fas fa-truck"></i>
            </div>
            <h3>Warehouse Outflow</h3>
        </div>
        
        <div class="card-value-container">
            <div class="card-value">
                <?php echo number_format($outflowStats['total_items_shipped']); ?>
            </div>
        </div>
        
        <div class="card-footer">
            From <?php echo number_format($outflowStats['total_orders']); ?> orders
        </div>
    </div>
    
    <!-- Delivered Items Card -->
    <div class="dashboard-card">
        <div style="position: absolute; top: 0; right: 0; width: 100px; height: 100px; background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0) 70%); border-radius: 50%;"></div>
        
        <div class="card-header">
            <div class="card-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Delivered Items</h3>
        </div>
        
        <div class="card-value-container">
            <div class="card-value">
                <?php echo number_format($deliveredStats['total_items_delivered']); ?>
            </div>
        </div>
        
        <div class="card-footer">
            From <?php echo number_format($deliveredStats['total_orders']); ?> orders
        </div>
    </div>
    
    <!-- Revenue Card -->
    <div class="dashboard-card">
        <div style="position: absolute; top: 0; right: 0; width: 100px; height: 100px; background: radial-gradient(circle, rgba(14, 165, 233, 0.15) 0%, rgba(14, 165, 233, 0) 70%); border-radius: 50%;"></div>
        
        <div class="card-header">
            <div class="card-icon blue">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <h3>Revenue</h3>
        </div>
        
        <div class="card-value-container">
            <div class="card-value">
                ₱<?php echo number_format($revenueStats['total_revenue'], 2); ?>
            </div>
        </div>
        
        <div class="card-footer">
            From shipped & delivered orders
        </div>
    </div>
</div>

<div class="sales-summary-container">
    <div class="data-table-card">
        <h2>Sales Summary</h2>
        <div style="padding: 20px;">
            <div class="sales-summary-grid">
                <div class="sales-summary-item">
                    <div class="sales-title">Today's Sales</div>
                    <div class="sales-amount">₱<?php echo number_format($dailySalesAmount, 2); ?></div>
                    <div class="sales-subtitle"><?php echo $dailyOrderCount; ?> orders today</div>
                </div>
                
                <div class="sales-summary-item">
                    <div class="sales-title">Weekly Sales</div>
                    <div class="sales-amount">₱<?php echo number_format($weeklySalesAmount, 2); ?></div>
                    <div class="sales-subtitle"><?php echo $weeklyOrderCount; ?> orders this week</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="data-tables-container">
    <div class="data-table-card"">
        <h2>Recently Added Stock</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentStock)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No recently added stock items</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentStock as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td><?php echo number_format($item['stock']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="data-table-card">
        <h2>Low Stock Items</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStock)): ?>
                    <tr>
                        <td colspan="3" class="text-center">No low stock items</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($lowStock as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $item['stock'] <= 5 ? 'danger' : 'warning'; ?>">
                                <?php echo number_format($item['stock']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* Dashboard Cards Layout */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .dashboard-card {
        display: flex;
        flex-direction: column;
        background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }
    
    .card-header {
        display: flex;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .card-icon {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 48px;
        height: 48px;
        border-radius: 12px;
        margin-right: 16px;
    }
    
    .card-icon i {
        font-size: 20px;
        color: white;
    }
    
    .card-icon.purple {
        background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
    }
    
    .card-icon.pink {
        background: linear-gradient(135deg, #EC4899 0%, #F472B6 100%);
    }
    
    .card-icon.green {
        background: linear-gradient(135deg, #10B981 0%, #34D399 100%);
    }
    
    .card-icon.blue {
        background: linear-gradient(135deg, #0EA5E9 0%, #38BDF8 100%);
    }
    
    .card-header h3 {
        font-size: 1.2rem;
        font-weight: 500;
        color: #E2E8F0;
        margin: 0;
    }
    
    .card-value-container {
        display: flex;
        align-items: baseline;
        margin-bottom: 8px;
    }
    
    .card-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: white;
        margin-right: 12px;
    }
    
    .card-subtext {
        font-size: 1rem;
        color: #94A3B8;
    }
    
    .card-footer {
        font-size: 0.875rem;
        color: #94A3B8;
    }
    
    /* Data Tables Layout */
    .data-tables-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .data-table-card {
        background: #1E293B;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .data-table-card h2 {
        padding: 16px 20px;
        margin: 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 1.25rem;
        color: #E2E8F0;
    }
    
    .table-container {
        overflow-x: auto;
        padding: 0.5rem;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        background: #1E293B;
    }
    
    table th,
    table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        color: #E2E8F0;
    }
    
    table th {
        font-weight: 500;
        color: #94A3B8;
        background: rgba(0, 0, 0, 0.2);
    }

    table tbody tr:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .badge {
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        border-radius: 0.25rem;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
    }

    .badge-danger {
        background-color: rgba(239, 68, 68, 0.2);
        color: #EF4444;
    }

    .badge-warning {
        background-color: rgba(245, 158, 11, 0.2);
        color: #F59E0B;
    }

    .text-center {
        text-align: center;
    }
      .sales-summary-container {
        margin-bottom: 24px;
    }
    
    .sales-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .sales-summary-item {
        background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .sales-title {
        font-size: 1.1rem;
        color: #94A3B8;
        margin-bottom: 10px;
    }
    
    .sales-amount {
        font-size: 2rem;
        font-weight: 700;
        color: white;
        margin-bottom: 5px;
    }
    
    .sales-subtitle {
        font-size: 0.875rem;
        color: #94A3B8;
    }
    
    @media (max-width: 768px) {
        .sales-summary-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* Responsive Breakpoints */
    @media (max-width: 1200px) {
        .stats-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .data-tables-container {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 576px) {
        .stats-container {
            grid-template-columns: 1fr;
        }
        
        .card-value {
            font-size: 2rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeRangeSelector = document.getElementById('time-range-selector');
    
    // Handle time range change
    timeRangeSelector.addEventListener('change', function() {
        window.location.href = 'dashboard.php?time_range=' + this.value;
    });
});
</script>

<?php include '../../layouts/footer.php'; ?> 