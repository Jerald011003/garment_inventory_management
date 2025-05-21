<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role'])) {
    header("Location: ../../index.php");
    exit();
}

function getInventoryNotifications($conn) {
    try {
        // Get low stock items (threshold of 10 units)
        $lowStockQuery = $conn->query("
            SELECT COUNT(*) as count FROM products 
            WHERE stock > 0 AND stock <= 10
        ");
        $lowStock = $lowStockQuery->fetch();
        
        // Get out of stock items
        $outOfStockQuery = $conn->query("
            SELECT COUNT(*) as count FROM products 
            WHERE stock = 0
        ");
        $outOfStock = $outOfStockQuery->fetch();
        
        // Get high stock items (threshold of 200 units)
        $highStockQuery = $conn->query("
            SELECT COUNT(*) as count FROM products 
            WHERE stock >= 200
        ");
        $highStock = $highStockQuery->fetch();
        
        return [
            'low_stock' => $lowStock['count'],
            'out_of_stock' => $outOfStock['count'],
            'high_stock' => $highStock['count'],
            'total' => $lowStock['count'] + $outOfStock['count'] + $highStock['count']
        ];
    } catch (Exception $e) {
        return [
            'low_stock' => 0,
            'out_of_stock' => 0,
            'high_stock' => 0,
            'total' => 0
        ];
    }
}

// Get notification counts if database connection exists
$notificationCounts = isset($conn) ? getInventoryNotifications($conn) : ['total' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'high_stock' => 0];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Garment Inventory Management'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg custom-navbar" style="padding-top: 20px; padding-bottom: 20px; background-color: #141414;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="fas text-white fa-tshirt brand-icon"></i>
                <span class="ms-2 text-white" style>Triple S Garments</span>
            </a>
            <button class="navbar-toggler custom-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas text-white fa-bars"></i>
            </button>

            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto me-4">
                    <?php if ($_SESSION['role'] == 'admin') { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../../pages/admin/dashboard.php">
                                <i class="fas text-white fa-tachometer-alt"></i>
                                <span class="text-white">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../../pages/admin/inventory.php">
                                <i class="fas text-white fa-boxes"></i>
                                <span class="text-white">Inventory</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../../pages/admin/orders.php">
                                <i class="fas text-white fa-shopping-cart"></i>
                                <span class="text-white">Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                        <a class="nav-link" href="../../pages/admin/sales_report.php">
                            <i class="fas text-white fa-chart-bar"></i>
                            <span class="text-white">Sales Report</span>
                        </a>
                    </li>
                    <?php } elseif ($_SESSION['role'] == 'sales') { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../../pages/sales/dashboard.php">
                                <i class="fas text-white fa-tachometer-alt"></i>
                                <span class="text-white">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/garment-inventory-management/pages/sales/inventory.php">
                                <i class="fas text-white fa-boxes"></i>
                                <span class="text-white">Inventory</span>
                            </a>
                        </li>
                        <li class="nav-item">
                        <a class="nav-link" href="../../pages/sales/sales_report.php">
                            <i class="fas text-white fa-chart-bar"></i>
                            <span class="text-white">Sales Report</span>
                        </a>
                    </li>
                    <?php } elseif ($_SESSION['role'] == 'staff') { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/garment-inventory-management/pages/staff/processorders.php">
                                <i class="fas text-white fa-tasks"></i>
                                <span class="text-white">Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/garment-inventory-management/pages/staff/manageinventory.php">
                                <i class="fas text-white fa-boxes"></i>
                                <span class="text-white">Inventory</span>
                            </a>
                        </li>
                        <li class="nav-item">
                        <a class="nav-link" href="../../pages/staff/sales_report.php">
                            <i class="fas text-white fa-chart-bar"></i>
                            <span class="text-white">Sales Report</span>
                        </a>
                    </li>
                    <?php } ?>
                </ul>


                <div class="nav-user-section d-flex align-items-center">
                        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'sales' || $_SESSION['role'] == 'staff'): ?>
                        <div class="nav-item me-3">
                            <div class="dropdown">
<a href="#" class="nav-link position-relative" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">                                    <i class="fas fa-bell text-white"></i>
                                    <?php if ($notificationCounts['total'] > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $notificationCounts['total']; ?>
                                        <span class="visually-hidden">notifications</span>
                                    </span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                                    <li><h6 class="dropdown-header">Inventory Alerts</h6></li>
                                    <?php if ($notificationCounts['total'] == 0): ?>
                                        <li><span class="dropdown-item-text">No notifications</span></li>
                                    <?php else: ?>
                                        <?php if ($notificationCounts['low_stock'] > 0): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo $_SESSION['role'] == 'admin' ? '../../pages/admin/inventory.php?status=low_stock' : '../../pages/sales/inventory.php?status=low_stock'; ?>">
                                                <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Low Stock Items</span>
                                                <span class="badge bg-warning text-dark ms-2"><?php echo $notificationCounts['low_stock']; ?></span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if ($notificationCounts['out_of_stock'] > 0): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo $_SESSION['role'] == 'admin' ? '../../pages/admin/inventory.php?status=out_of_stock' : '../../pages/sales/inventory.php?status=out_of_stock'; ?>">
                                                <span class="text-danger"><i class="fas fa-times-circle"></i> Out of Stock Items</span>
                                                <span class="badge bg-danger ms-2"><?php echo $notificationCounts['out_of_stock']; ?></span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if ($notificationCounts['high_stock'] > 0): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo $_SESSION['role'] == 'admin' ? '../../pages/admin/inventory.php?status=high_stock' : '../../pages/sales/inventory.php?status=high_stock'; ?>">
                                                <span class="text-info"><i class="fas fa-info-circle"></i> High Stock Items</span>
                                                <span class="badge bg-info ms-2"><?php echo $notificationCounts['high_stock']; ?></span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle user-menu" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i>
                            <span class="ms-2"><?php echo $_SESSION['full_name']; ?></span>
                        </a>
                        
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                            <li>
                                <a class="dropdown-item" href="register_user.php">
                                    <i class="fas fa-user-plus text-success"></i> Register User
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="../../includes/auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                        
                    </div>
                </div>

            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Content goes below this line --> 