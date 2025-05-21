<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


require_once '../../includes/auth/auth_check.php';
require_once '../../includes/config/database.php';

checkAuth(); 

$pageTitle = "Sales Reports";

$timeRange = isset($_GET['time_range']) ? $_GET['time_range'] : 'all_time';
$startDate = null;
$endDate = date('Y-m-d 23:59:59');

switch ($timeRange) {
    case 'all_time':
        $startDate = '1970-01-01 00:00:00'; 
        $endDate = '2099-12-31 23:59:59';  
        break;
    case 'today':
        $startDate = date('Y-m-d 00:00:00');
        break;
    case 'yesterday':
        $startDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $endDate = date('Y-m-d 23:59:59', strtotime('-1 day'));
        break;
    case 'this_week':
        $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
        break;
    case 'last_week':
        $startDate = date('Y-m-d 00:00:00', strtotime('monday last week'));
        $endDate = date('Y-m-d 23:59:59', strtotime('sunday last week'));
        break;
    case 'this_month':
        $startDate = date('Y-m-01 00:00:00');
        break;
    case 'last_month':
        $startDate = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $endDate = date('Y-m-t 23:59:59', strtotime('last day of last month'));
        break;
    case 'custom':
        if (isset($_GET['start_date'])) {
            $startDate = $_GET['start_date'] . ' 00:00:00';
        }
        if (isset($_GET['end_date'])) {
            $endDate = $_GET['end_date'] . ' 23:59:59';
        }
        break;
}

// Fetch sales data
$query = "SELECT
    o.id,
    o.created_at,
    o.status,
    o.total_amount,
    c.name as customer_name,
    COALESCE(u.full_name, u.username, 'Unknown') as employee_name,
    SUM(COALESCE(oi.quantity, 0)) as total_items
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN users u ON o.created_by = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.created_at BETWEEN ? AND ?
    GROUP BY o.id, o.created_at, o.status, o.total_amount, c.name
    ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([$startDate, $endDate]);
$orders = $stmt->fetchAll();

// Calculate summary statistics
$totalSales = 0;
$totalItems = 0;
$totalOrders = count($orders);

foreach ($orders as $order) {
    $totalSales += $order['total_amount'];
    $totalItems += $order['total_items'];
}

include '../../layouts/header.php';
?>

<style>
.time-range-container {
    background-color: rgba(255, 255, 255, 0.05);
    position: relative;
    width: 100%;
    max-width: 100%;
    margin-bottom: 1.5rem;
}

.time-range-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background-color: #1e1e1e;
    border: 1px solid #333;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #fff;
}

.time-range-header:hover {
    border-color: #555;
    background-color: #2a2a2a;
}

.time-range-header-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.time-range-icon {
    color: #aaa;
}

.time-range-selected {
    font-weight: 500;
    color: #fff;
}

.time-range-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    z-index: 1000;
    background-color: #1e1e1e;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
    border: 1px solid #333;
    overflow: hidden;
}

.time-range-options {
    max-height: 320px;
    overflow-y: auto;
}

.time-range-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
    color: #fff;
}

.time-range-option:hover {
    background-color: #2a2a2a;
}

.time-range-option.active {
    background-color: rgba(79, 70, 229, 0.2);
    color: #8b5cf6;
}

.time-range-option-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.time-range-option-date {
    font-size: 0.75rem;
    color: #aaa;
}

.time-range-custom {
    padding: 1rem;
    border-top: 1px solid #333;
}

.time-range-custom-dates {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.time-range-custom-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: #ddd;
}

.time-range-custom-group input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #444;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: #2d2d2d;
    color: #fff;
}

.time-range-apply-btn {
    width: 100%;
    padding: 0.5rem;
    background-color: #4f46e5;
    color: white;
    border: none;
    border-radius: 0.375rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.time-range-apply-btn:hover {
    background-color: #4338ca;
}

/* Summary Cards Styles */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    width: 100%;
}

.summary-card {
    border-radius: 0.75rem;
    padding: 1.5rem;
    color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transition: transform 0.2s ease;
}

.summary-card:hover {
    transform: translateY(-5px);
}

.summary-card-title {
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    opacity: 0.9;
}

.summary-card-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.2;
}

.sales-card {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
}

.orders-card {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
}

.items-card {
    background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);
}

/* Table Styles */
.data-table-container {
    background-color: #1e1e1e;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    overflow: hidden;
    width: 100%;
            background-color: rgba(255, 255, 255, 0.05);

}

.data-table-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #333;
}

.data-table-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #fff;
    margin: 0;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background-color: #2d2d2d;
    color: #aaa;
    font-weight: 500;
    text-align: left;
    padding: 0.75rem 1.5rem;
    border-bottom: 1px solid #333;
}

.data-table td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #333;
    color: #fff;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.data-table tr:hover td {
    background-color: #2a2a2a;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-pending {
    background-color: rgba(234, 88, 12, 0.2);
    color: #fb923c;
}

.status-shipped {
    background-color: rgba(29, 78, 216, 0.2);
    color: #60a5fa;
}

.status-delivered {
    background-color: rgba(4, 120, 87, 0.2);
    color: #34d399;
}

.status-cancelled {
    background-color: rgba(185, 28, 28, 0.2);
    color: #f87171;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    background-color: #2d2d2d;
    color: #aaa;
    transition: all 0.2s ease;
}

.action-btn:hover {
    background-color: #3a3a3a;
    color: #fff;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.75rem;
}

.action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
}

.print-button {
    background-color: #4f46e5;
    color: white;
}

.print-button:hover {
    background-color: #4338ca;
}

.export-button {
    background-color: #059669;
    color: white;
}

.export-button:hover {
    background-color: #047857;
}

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}

/* Container */
.content-container {
    /* Remove this line or change it */
    max-width: 100%; /* Was 1400px */
    margin: 0;
    padding: 1rem 1.5rem;
    width: 100%;
    
}

/* Print Styles */
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
    
    .content-container {
        width: 100%;
        max-width: 100%;
        padding: 0;
    }
    
    .action-buttons, .time-range-container, .action-btn, .navbar, footer, header {
        display: none !important;
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .page-title {
        color: black !important;
        font-size: 24px;
    }
    
    .summary-cards {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
    }
    
    .summary-card {
        background: white !important;
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        color: black !important;
        width: 30%;
        padding: 15px;
        transform: none !important;
    }
    
    .summary-card-title {
        color: #555 !important;
        font-size: 14px;
    }
    
    .summary-card-value {
        color: black !important;
        font-size: 20px;
    }
    
    .data-table-container {
        background-color: white !important;
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
    
    .data-table-header {
        border-bottom: 1px solid #ddd !important;
        padding: 10px 15px;
    }
    
    .data-table-title {
        color: black !important;
        font-size: 18px;
    }
    
    .data-table th {
        background-color: #f5f5f5 !important;
        color: black !important;
        border-bottom: 1px solid #ddd !important;
    }
    
    .data-table td {
        color: black !important;
        border-bottom: 1px solid #ddd !important;
    }
    
    .data-table tr:hover td {
        background-color: white !important;
    }
    
    .status-badge {
        border: 1px solid #ddd;
        background-color: white !important;
        color: black !important;
    }
    
    .status-pending, .status-shipped, .status-delivered, .status-cancelled {
        background-color: white !important;
        color: black !important;
    }
    
    /* Add page info */
    @page {
        margin: 0.5cm;
    }
    
    /* Add print footer */
    .print-footer {
        display: block !important;
        text-align: center;
        font-size: 10px;
        color: #777;
        margin-top: 30px;
    }
}

/* Hide print footer by default */
.print-footer {
    display: none;
}

@media (max-width: 768px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .time-range-custom-dates {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>

<div class="content-container">
    <div class="page-header">
    <h1 class="page-title">Sales Reports</h1>
    <div class="action-buttons">
        <button id="printAllReceiptsBtn" class="action-button print-button" style="margin-right: 10px;">
            <i class="fas fa-receipt"></i> Print All Receipts
        </button>
        <button id="printReportBtn" class="action-button print-button">
            <i class="fas fa-print"></i> Print Report
        </button>
        <button id="exportCSVBtn" class="action-button export-button">
            <i class="fas fa-file-csv"></i> Export to CSV
        </button>
    </div>
    </div>

    <!-- Time Range Filter -->
    <div class="time-range-container">
        <div class="time-range-header" id="timeRangeHeader">
            <div class="time-range-header-content">
                <i class="fas fa-calendar-alt time-range-icon"></i>
                <span class="time-range-selected" id="selectedRangeText">
                    <?php
                    switch ($timeRange) {
                        case 'all_time':
                            echo 'All Time';
                            break;
                        case 'today':
                            echo 'Today';
                            break;
                        case 'this_week':
                            echo 'This Week';
                            break;
                        case 'last_week':
                            echo 'Last Week';
                            break;
                        case 'this_month':
                            echo 'This Month';
                            break;
                        case 'last_month':
                            echo 'Last Month';
                            break;
                        case 'custom':
                            if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
                                echo date('M d, Y', strtotime($_GET['start_date'])) . ' - ' . date('M d, Y', strtotime($_GET['end_date']));
                            } else {
                                echo 'Custom Range';
                            }
                            break;
                        default:
                            echo 'Select Time Range';
                    }
                    ?>
                </span>
            </div>
            <i class="fas fa-chevron-down time-range-icon"></i>
        </div>
        
        <div class="time-range-dropdown" id="timeRangeDropdown" style="display: none;">
            <div class="time-range-options">
                <form method="GET" action="" id="timeRangeForm">
                    <input type="hidden" name="time_range" id="timeRangeInput" value="<?php echo $timeRange; ?>">
                    <input type="hidden" name="start_date" id="startDateInput" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                    <input type="hidden" name="end_date" id="endDateInput" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
                    
                    <div class="time-range-option <?php echo $timeRange == 'all_time' ? 'active' : ''; ?>" data-value="all_time">
                        <div class="time-range-option-label">
                            <i class="fas fa-history"></i>
                            <span>All Time</span>
                        </div>
                        <span class="time-range-option-date">All orders</span>
                    </div>

                    <div class="time-range-option <?php echo $timeRange == 'today' ? 'active' : ''; ?>" data-value="today">
                        <div class="time-range-option-label">
                            <i class="fas fa-calendar-day"></i>
                            <span>Today</span>
                        </div>
                        <span class="time-range-option-date"><?php echo date('M d, Y'); ?></span>
                    </div>
                    
                    <div class="time-range-option <?php echo $timeRange == 'yesterday' ? 'active' : ''; ?>" data-value="yesterday">
                        <div class="time-range-option-label">
                            <i class="fas fa-calendar-day"></i>
                            <span>Yesterday</span>
                        </div>
                        <span class="time-range-option-date"><?php echo date('M d, Y', strtotime('-1 day')); ?></span>
                    </div>
                    
                    <div class="time-range-option <?php echo $timeRange == 'this_week' ? 'active' : ''; ?>" data-value="this_week">
                        <div class="time-range-option-label">
                            <i class="fas fa-calendar-week"></i>
                            <span>This Week</span>
                        </div>
                        <span class="time-range-option-date">
                            <?php 
                            echo date('M d', strtotime('monday this week')) . ' - ' . date('M d, Y', strtotime('sunday this week')); 
                            ?>
                        </span>
                    </div>
                    
                    <div class="time-range-option <?php echo $timeRange == 'last_week' ? 'active' : ''; ?>" data-value="last_week">
                        <div class="time-range-option-label">
                            <i class="fas fa-calendar-week"></i>
                            <span>Last Week</span>
                        </div>
                        <span class="time-range-option-date">
                            <?php 
                            echo date('M d', strtotime('monday last week')) . ' - ' . date('M d, Y', strtotime('sunday last week')); 
                            ?>
                        </span>
                    </div>
                    
                    <div class="time-range-option <?php echo $timeRange == 'this_month' ? 'active' : ''; ?>" data-value="this_month">
                        <div class="time-range-option-label">
                            <i class="fas fa-calendar-alt"></i>
                            <span>This Month</span>
                        </div>
                        <span class="time-range-option-date">
                            <?php 
                            echo date('M Y'); 
                            ?>
                        </span>
                    </div>
                    
                    <div class="time-range-option <?php echo $timeRange == 'last_month' ? 'active' : ''; ?>" data-value="last_month">
                        <div class="time-range-option-label">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Last Month</span>
                        </div>
                        <span class="time-range-option-date">
                            <?php 
                            echo date('M Y', strtotime('first day of last month')); 
                            ?>
                        </span>
                    </div>
                    
                    <div class="time-range-option <?php echo $timeRange == 'custom' ? 'active' : ''; ?>" data-value="custom">
                        <div class="time-range-option-label">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Custom Range</span>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="time-range-custom" id="customDateContainer" style="display: <?php echo $timeRange == 'custom' ? 'block' : 'none'; ?>;">
                <div class="time-range-custom-dates">
                    <div class="time-range-custom-group">
                        <label for="customStartDate">Start Date</label>
                        <input type="date" id="customStartDate" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d'); ?>">
                    </div>
                    <div class="time-range-custom-group">
                        <label for="customEndDate">End Date</label>
                        <input type="date" id="customEndDate" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); ?>">
                    </div>
                </div>
                <button type="button" class="time-range-apply-btn" id="applyCustomDates">Apply Range</button>
            </div>
        </div>
    </div>

    <!-- Sales Summary -->
    <div class="summary-cards">
        <div class="summary-card sales-card">
            <div class="summary-card-title">Total Sales</div>
            <div class="summary-card-value">₱<?php echo number_format($totalSales, 2); ?></div>
        </div>
        <div class="summary-card orders-card">
            <div class="summary-card-title">Total Orders</div>
            <div class="summary-card-value"><?php echo $totalOrders; ?></div>
        </div>
        <div class="summary-card items-card">
            <div class="summary-card-title">Items Sold</div>
            <div class="summary-card-value"><?php echo $totalItems; ?></div>
        </div>
    </div>

    <!-- Sales Data -->
    <div class="data-table-container">
        <div class="data-table-header">
            <h2 class="data-table-title">Sales Details</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="salesTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Employee</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['employee_name']); ?></td>
                        <td><?php echo $order['total_items']; ?></td>
                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="action-btn view-receipt" data-order-id="<?php echo $order['id']; ?>" title="View Receipt">
                                <i class="fas fa-receipt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Print footer - only visible when printing -->
    <div class="print-footer">
        <p>Triple S Garments - Sales Report - <?php echo date('F d, Y'); ?></p>
        <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; overflow: auto;">
    <div style="background-color: white; margin: 10% auto; padding: 20px; width: 80%; max-width: 500px; position: relative; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <span id="closeReceiptModal" style="position: absolute; right: 15px; top: 10px; font-size: 24px; font-weight: bold; cursor: pointer; color: #777;">&times;</span>
        <div id="receiptContent" style="max-height: 70vh; overflow-y: auto;"></div>
        <div style="margin-top: 20px; text-align: right;">
            <button id="printSingleReceipt" style="background-color: #4f46e5; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                <i class="fas fa-print"></i> Print Receipt
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeRangeHeader = document.getElementById('timeRangeHeader');
    const timeRangeDropdown = document.getElementById('timeRangeDropdown');
    const timeRangeOptions = document.querySelectorAll('.time-range-option');
    const timeRangeForm = document.getElementById('timeRangeForm');
    const timeRangeInput = document.getElementById('timeRangeInput');
    const startDateInput = document.getElementById('startDateInput');
    const endDateInput = document.getElementById('endDateInput');
    const selectedRangeText = document.getElementById('selectedRangeText');
    const customDateContainer = document.getElementById('customDateContainer');
    const customStartDate = document.getElementById('customStartDate');
    const customEndDate = document.getElementById('customEndDate');
    const applyCustomDates = document.getElementById('applyCustomDates');
    
    timeRangeHeader.addEventListener('click', function() {
        timeRangeDropdown.style.display = timeRangeDropdown.style.display === 'none' ? 'block' : 'none';
    });
    
    document.addEventListener('click', function(event) {
        if (!timeRangeHeader.contains(event.target) && !timeRangeDropdown.contains(event.target)) {
            timeRangeDropdown.style.display = 'none';
        }
    });
    
    timeRangeOptions.forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            timeRangeInput.value = value;
            
            if (value === 'custom') {
                customDateContainer.style.display = 'block';
            } else {
                customDateContainer.style.display = 'none';
                timeRangeForm.submit();
            }
            
            timeRangeOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    applyCustomDates.addEventListener('click', function() {
        const startDate = customStartDate.value;
        const endDate = customEndDate.value;
        
        if (startDate && endDate) {
            startDateInput.value = startDate;
            endDateInput.value = endDate;
            timeRangeForm.submit();
        } else {
            alert('Please select both start and end dates');
        }
    });
    
    document.getElementById('printReportBtn').addEventListener('click', function() {
        const style = document.createElement('style');
        style.id = 'print-style';
        style.innerHTML = `
            @media print {
                body * {
                    visibility: hidden;
                }
                .content-container, .content-container * {
                    visibility: visible;
                }
                .content-container {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                }
                .action-buttons, .time-range-container, .action-btn {
                    display: none !important;
                }
            }
        `;
        document.head.appendChild(style);
        
        window.print();
        
        document.head.removeChild(style);
    });
    
        document.getElementById('exportCSVBtn').addEventListener('click', function() {
        const table = document.getElementById('salesTable');
        
        let csvContent = '\uFEFF';
        
        let rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length - 1; j++) { 
                let text = '';
                
                if (j === 0) { 
                    text = cols[j].textContent.trim();
                } 
                else if (j === 1 && i > 0) { 
                    const dateText = cols[j].textContent.trim();
                    text = dateText; 
                }
                else if (j === 5 && i > 0) { 
                    const amountText = cols[j].textContent.trim();
                    text = amountText.replace('₱', 'P');
                }
                else {
                    text = cols[j].textContent.trim();
                }
                
                text = text.replace(/"/g, '""');
                row.push('"' + text + '"');
            }
            
            csvContent += row.join(',') + '\n';
        }
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', 'sales_report_<?php echo date('Y-m-d'); ?>.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    
const receiptButtons = document.querySelectorAll('.view-receipt');
const receiptModal = document.getElementById('receiptModal');
const closeReceiptModal = document.getElementById('closeReceiptModal');
const receiptContent = document.getElementById('receiptContent');
const printSingleReceipt = document.getElementById('printSingleReceipt');

closeReceiptModal.addEventListener('click', function() {
    receiptModal.style.display = 'none';
});

window.addEventListener('click', function(event) {
    if (event.target == receiptModal) {
        receiptModal.style.display = 'none';
    }
});

receiptButtons.forEach(button => {
    button.addEventListener('click', function() {
        const orderId = this.getAttribute('data-order-id');
        const orders = <?php echo json_encode($orders); ?>;
        
        const order = orders.find(o => o.id == orderId);
        
        if (order) {
            const receiptHtml = `
                <div class="receipt" style="font-family: 'Arial', sans-serif; margin: 0 auto; color: #000;">
                    <!-- Header -->
                    <div style="text-align: center; padding-bottom: 15px; margin-bottom: 15px; border-bottom: 2px solid #333;">
                        <h2 style="margin: 0; font-size: 18px; font-weight: bold; color: #000; text-transform: uppercase; letter-spacing: 1px;">Triple S Garments</h2>
                        <p style="margin: 5px 0; font-size: 12px; color: #333;">123 Fashion Street, Style City</p>
                        <p style="margin: 5px 0; font-size: 12px; color: #333;">Tel: (123) 456-7890</p>
                    </div>
                    
                    <!-- Receipt Title -->
                    <div style="margin: 15px 0; text-align: center; background-color: #f8f8f8; padding: 8px 0; border-radius: 4px;">
                        <p style="margin: 0; font-size: 16px; font-weight: bold; color: #000;">SALES RECEIPT</p>
                    </div>
                    
                    <!-- Order Info -->
                    <div style="margin: 15px 0; font-size: 12px; line-height: 1.5;">
                        <table style="width: 100%; color: #000;">
                            <tr>
                                <td><strong>Order #:</strong></td>
                                <td>${order.id.toString().padStart(3, '0')}</td>
                            </tr>
                            <tr>
                                <td><strong>Date:</strong></td>
                                <td>${new Date(order.created_at).toLocaleString()}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Customer Info -->
                    <div style="margin: 15px 0; padding: 8px; background-color: #f8f8f8; border-radius: 4px; font-size: 12px; line-height: 1.5;">
                        <p style="margin: 0 0 5px 0; font-weight: bold; color: #000;">Customer Information:</p>
                        <p style="margin: 0; color: #000;">Name: ${order.customer_name || 'N/A'}</p>
                    </div>
                    
                    <!-- Items -->
                    <div style="margin: 15px 0; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 10px 0;">
                        <table style="width: 100%; font-size: 12px; border-collapse: collapse; color: #000;">
                            <tr style="">
                                <th style="">Items</th>
                                <th style="text-align: right; padding: 5px 0; ">Total</th>
                            </tr>
                    
                             <tr>
                                <td style="padding: 8px 0; color: #000;">${order.total_items} items</td>
                                <td style="text-align: right; padding: 8px 0; color: #000;">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Total -->
                    <div style="margin: 15px 0;">
                        <table style="width: 100%; font-size: 14px; color: #000;">
                            <tr style="font-weight: bold;">
                                <td style="text-align: right; padding: 5px 0; color: #000;">TOTAL:</td>
                                <td style="text-align: right; padding: 5px 0; width: 30%; color: #000;">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Status -->
                    ${order.status === 'cancelled' ? `
                    <div style="position: relative; margin: 20px 0; text-align: center;">
                        <div style="opacity: 0.8;">
                            <p style="margin: 0; font-size: 22px; font-weight: bold; color: #FF0000; border: 3px solid #FF0000; display: inline-block; padding: 5px 20px; border-radius: 10px; text-transform: uppercase; letter-spacing: 2px;">CANCELLED</p>
                        </div>
                    </div>
                    ` : ``}
                    
                    <!-- Footer -->
                    <div style="margin: 20px 0;">
                        <div style="text-align: center; padding-top: 15px; border-top: 1px solid #ddd;">
                            <p style="margin: 5px 0; font-size: 12px; color: #333;">Thank you for your purchase!</p>
                            <p style="margin: 5px 0; font-size: 10px; color: #555;">Keep this receipt for your records</p>
                        </div>
                    </div>
                </div>
            `;
            
            receiptContent.innerHTML = receiptHtml;
            receiptModal.style.display = 'block';
        } else {
            alert('Receipt not found');
        }
    });
});

printSingleReceipt.addEventListener('click', function() {
    const receiptHtml = receiptContent.innerHTML;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Receipt</title>
            <style>
                @page { size: 80mm 200mm; margin: 0; }
                body { 
                    font-family: Arial, sans-serif; 
                    background: white; 
                    color: black; 
                    margin: 0; 
                    padding: 10px;
                }
                .receipt { width: 80mm; margin: 0 auto; }
            </style>
        </head>
        <body>
            <div class="receipt">
                ${receiptHtml}
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    setTimeout(() => {
        printWindow.print();
    }, 500);
});
    
    document.getElementById('printAllReceiptsBtn').addEventListener('click', function() {
        const orders = <?php echo json_encode($orders); ?>;
        
        if (orders.length === 0) {
            alert('No orders available to print.');
            return;
        }
        
        let printContent = '<div class="all-receipts">';
        
        orders.forEach((order, index) => {
            printContent += `
                <div class="receipt" style="page-break-after: always; font-family: 'Arial', sans-serif; max-width: 80mm; margin: 0 auto; border: 1px solid #ddd; padding: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <div style="text-align: center; padding-bottom: 15px; margin-bottom: 15px; border-bottom: 2px solid #333;">
                        <h2 style="margin: 0; font-size: 18px; font-weight: bold; color: #000; text-transform: uppercase; letter-spacing: 1px;">Triple S Garments</h2>
                        <p style="margin: 5px 0; font-size: 12px; color: #555;">123 Fashion Street, Style City</p>
                        <p style="margin: 5px 0; font-size: 12px; color: #555;">Tel: (123) 456-7890</p>
                    </div>
                    
                    <!-- Receipt Title -->
                    <div style="margin: 15px 0; text-align: center; background-color: #f8f8f8; padding: 8px 0; border-radius: 4px;">
                        <p style="margin: 0; font-size: 16px; font-weight: bold; color: #000;">SALES RECEIPT</p>
                    </div>
                    
                    <!-- Order Info -->
                    <div style="margin: 15px 0; font-size: 12px; line-height: 1.5;">
                        <table style="width: 100%;">
                            <tr>
                                <td><strong>Order #:</strong></td>
                                <td>${order.id.toString().padStart(3, '0')}</td>
                            </tr>
                            <tr>
                                <td><strong>Date:</strong></td>
                                <td>${new Date(order.created_at).toLocaleString()}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Customer Info -->
                    <div style="margin: 15px 0; padding: 8px; background-color: #f8f8f8; border-radius: 4px; font-size: 12px; line-height: 1.5;">
                        <p style="margin: 0 0 5px 0; font-weight: bold;">Customer Information:</p>
                        <p style="margin: 0;">Name: ${order.customer_name || 'N/A'}</p>
                    </div>
                    
                    <!-- Items -->
                    <div style="margin: 15px 0; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 10px 0;">
                        <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                            <tr style="border-bottom: 1px solid #eee; font-weight: bold; ">
                                <th style="text-align: left; padding: 5px 0;">Items</th>
                                <th style="text-align: right; padding: 5px 0;">Total</th>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0;">${order.total_items} items</td>
                                <td style="text-align: right; padding: 8px 0;">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Total -->
                    <div style="margin: 15px 0;">
                        <table style="width: 100%; font-size: 14px;">
                            <tr style="font-weight: bold;">
                                <td style="text-align: right; padding: 5px 0;">TOTAL:</td>
                                <td style="text-align: right; padding: 5px 0; width: 30%;">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Status -->
                    ${order.status === 'cancelled' ? `
                    <div style="position: relative; margin: 20px 0; text-align: center;">
                        <div style="position: absolute; top: -10px; left: 0; right: 0; transform: rotate(-15deg); opacity: 0.8;">
                            <p style="margin: 0; font-size: 22px; font-weight: bold; color: #FF0000; border: 3px solid #FF0000; display: inline-block; padding: 5px 20px; border-radius: 10px; text-transform: uppercase; letter-spacing: 2px;">CANCELLED</p>
                        </div>
                    </div>
                    <div style="margin: 60px 0 20px 0;">
                    ` : `
                    <div style="margin: 20px 0;">
                    `}
                        <!-- Footer -->
                        <div style="text-align: center; padding-top: 15px; border-top: 1px solid #ddd;">
                            <p style="margin: 5px 0; font-size: 12px; color: #555;">Thank you for your purchase!</p>
                            <p style="margin: 5px 0; font-size: 10px; color: #888;">Keep this receipt for your records</p>
                        </div>
                    </div>
                </div>
            `;
        });
        
        printContent += '</div>';
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>Daily Receipts - ${new Date().toLocaleDateString()}</title>
                <style>
                    @page { size: 80mm 200mm; margin: 0; }
                    body { 
                        font-family: Arial, sans-serif; 
                        background: white; 
                        color: black; 
                        margin: 0; 
                        padding: 0;
                    }
                    .all-receipts { width: 80mm; margin: 0 auto; }
                    @media print {
                        body { width: 80mm; }
                        .receipt { box-shadow: none; border: none; }
                    }
                </style>
            </head>
            <body>
                ${printContent}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        setTimeout(() => {
            printWindow.print();
        }, 500);
    });

});
</script>

<?php include '../../layouts/footer.php'; ?>
