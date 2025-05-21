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

$pageTitle = "Manage Inventory";

// Process stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['new_stock'])) {
    $productId = (int)$_POST['product_id'];
    $newStock = (int)$_POST['new_stock'];
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get current stock
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $currentStock = $stmt->fetchColumn();
        
        if ($currentStock === false) {
            throw new Exception("Product not found");
        }
        
        // Ensure stock doesn't go below zero
        if ($newStock < 0) {
            $newStock = 0;
        }
        
        // Update product stock
        $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt->execute([$newStock, $productId]);
        
        // Log the activity
        $stockDifference = $newStock - $currentStock;
        $action = $stockDifference > 0 ? "Stock increased by $stockDifference" : "Stock decreased by " . abs($stockDifference);
        $description = "Updated stock for product ID #$productId. $action. New stock: $newStock";
        
        $userId = $_SESSION['user_id'] ?? 0;
        $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description) VALUES (?, ?, ?)");
        $stmt->execute([$userId, "Stock Update", $description]);
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['success_message'] = "Stock updated successfully.";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error_message'] = "Error updating stock: " . $e->getMessage();
    }
    
    // Redirect back to inventory page
    header("Location: manageinventory.php");
    exit;
}

// Calculate inventory statistics
$totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStockItems = $conn->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 10")->fetchColumn();
$outOfStockItems = $conn->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();
$totalCategories = $conn->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Fetch all products with their categories
$stmt = $conn->query("
    SELECT 
        p.id,
        p.name AS product_name,
        p.stock,
        p.price,
        c.name AS category_name
    FROM 
        products p
    LEFT JOIN 
        categories c ON p.category_id = c.id
    ORDER BY 
        p.id ASC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../layouts/header.php';
?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <?php 
        echo $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        ?>
    </div>
<?php endif; ?>

<h1> Inventory</h1>
<div class="stats-grid" style="margin-top: 40px;">
    <div class="stat-card">
        <h3>Total Products</h3>
        <div class="value"><?php echo $totalProducts; ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Low Stock Items</h3>
        <div class="value"><?php echo $lowStockItems; ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Out of Stock</h3>
        <div class="value"><?php echo $outOfStockItems; ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Categories</h3>
        <div class="value"><?php echo $totalCategories; ?></div>
    </div>
</div>

<div class="card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Inventory Items</h3>
        <div class="search-filter">
            <input type="text" class="form-control" id="productSearch" placeholder="Search items...">
        </div>
    </div>

    <!-- Table view for larger screens -->
    <div class="table-container d-none d-md-block">
        <table class="table">
            <thead>
                <tr>
                    <th class="text-white text-center">Product ID</th>
                    <th class="text-white text-center">Product Name</th>
                    <th class="text-white text-center">Category</th>
                    <th class="text-white text-center">Price</th>
                    <th class="text-white text-center">Current Stock</th>
                    <th class="text-white text-center">Status</th>
                    <th class="text-white text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): 
                        // Determine product status
                        if ($product['stock'] <= 0) {
                            $status = 'Out of Stock';
                            $statusClass = 'bg-danger';
                        } elseif ($product['stock'] <= 10) {
                            $status = 'Low Stock';
                            $statusClass = 'bg-warning';
                        } else {
                            $status = 'In Stock';
                            $statusClass = 'bg-success';
                        }
                    ?>
                    <tr>
                        <td class="text-white text-center">#<?php echo str_pad($product['id'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td class="text-white text-center"><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td class="text-white text-center"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                        <td class="text-white text-center">₱<?php echo number_format($product['price'], 2); ?></td>
                        <td class="text-white text-center"><?php echo $product['stock']; ?></td>
                        <td class="text-center"><span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-primary update-stock-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#updateStockModal"
                                    data-product-id="<?php echo $product['id']; ?>"
                                    data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                    data-current-stock="<?php echo $product['stock']; ?>">
                                <i class="fas fa-edit"></i> Update Stock
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-white">No products found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Card view for mobile screens -->
    <div class="d-md-none">
        <?php if (count($products) === 0): ?>
            <div class="text-center py-4">No products found</div>
        <?php else: ?>
            <div class="inventory-cards">
                <?php foreach ($products as $product): 
                    if ($product['stock'] <= 0) {
                        $status = 'Out of Stock';
                        $statusClass = 'bg-danger';
                    } elseif ($product['stock'] <= 10) {
                        $status = 'Low Stock';
                        $statusClass = 'bg-warning';
                    } else {
                        $status = 'In Stock';
                        $statusClass = 'bg-success';
                    }
                ?>
                <div class="inventory-card">
                    <div class="inventory-card-header">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">#<?php echo str_pad($product['id'], 3, '0', STR_PAD_LEFT); ?></h5>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                        </div>
                        <h6 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                    </div>
                    <div class="inventory-card-body">
                        <div class="mb-2">
                            <strong>Category:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Price:</strong> ₱<?php echo number_format($product['price'], 2); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Current Stock:</strong> <?php echo $product['stock']; ?> units
                        </div>
                        <div class="text-end">
                            <button class="btn btn-sm btn-primary update-stock-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#updateStockModal"
                                    data-product-id="<?php echo $product['id']; ?>"
                                    data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                    data-current-stock="<?php echo $product['stock']; ?>">
                                <i class="fas fa-edit"></i> Update Stock
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Stock Modal -->
<div id="updateStockModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: var(--background-light); border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color); padding: 15px 20px;">
                <h5 class="modal-title" style="color: var(--text-primary); font-weight: 600;">Update Stock: <span id="productNameModal"></span></h5>
                <button type="button" style="color: white;" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <form id="updateStockForm" method="post" action="manageinventory.php" style="display: grid; gap: 1.5rem;">
                    <input type="hidden" id="productId" name="product_id">
                    
                    <div class="form-row">
                        <label for="currentStock" style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: 500;">Current Stock</label>
                        <input type="number" class="form-control" id="currentStock" readonly style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid var(--border-color); background-color: var(--input-bg); color: var(--text-primary);">
                    </div>
                    
                    <div class="form-row">
                        <label for="newStock" style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: 500;">New Stock</label>
                        <input type="number" class="form-control" id="newStock" name="new_stock" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid var(--border-color); background-color: var(--input-bg); color: var(--text-primary);">
                        <small style="display: block; margin-top: 5px; color: var(--text-secondary);">Enter the new total stock quantity.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--border-color); padding: 15px 20px;">
                <button type="submit" form="updateStockForm" class="btn btn-primary" style="padding: 8px 16px; border-radius: 4px; background-color: #6366F1; color: white; border: none; cursor: pointer; font-weight: 500;">Update Stock</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Add these styles for better table layout */
    .table {
        width: 100%;
        white-space: nowrap;
    }

    .table-container {
        overflow-x: auto;
        margin-bottom: 1rem;
    }

    /* Make product name cell wrap if needed */
    .table td:nth-child(2) {
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
    
    /* Ensure table hover state maintains white text */
    .table tbody tr:hover td {
        color: white !important;
    }
    
    /* Modal styles */
    .modal-content {
        color: var(--text-primary);
    }
    
    .form-control {
        background-color: white;
        color: #333;
    }
    
    /* Alert styles */
    .alert {
        padding: 12px 20px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .alert-success {
        background-color: rgba(16, 185, 129, 0.1);
        color: #10B981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .alert-danger {
        background-color: rgba(239, 68, 68, 0.1);
        color: #EF4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    /* New card view styles */
    .inventory-cards {
        display: grid;
        gap: 1rem;
        padding: 1rem 0;
    }

    .inventory-card {
        background: var(--background-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1rem;
    }

    .inventory-card-header {
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .inventory-card-body {
        padding-top: 1rem;
    }

    .product-name {
        font-size: 1.1rem;
        margin: 0;
        color: var(--text-primary);
    }

    @media (max-width: 910px) {
        .table-container {
            display: none;
        }
        
        .inventory-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update product info in the modal
    const updateStockButtons = document.querySelectorAll('.update-stock-btn');
    updateStockButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            const currentStock = this.getAttribute('data-current-stock');
            
            document.getElementById('productId').value = productId;
            document.getElementById('productNameModal').textContent = productName;
            document.getElementById('currentStock').value = currentStock;
            document.getElementById('newStock').value = currentStock;
        });
    });
    
    // Product search functionality
    const productSearch = document.getElementById('productSearch');
    if (productSearch) {
        productSearch.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            const cards = document.querySelectorAll('.inventory-card');
            
            // Search in table rows
            rows.forEach(row => {
                const productName = row.cells[1].textContent.toLowerCase();
                const category = row.cells[2].textContent.toLowerCase();
                const productId = row.cells[0].textContent.toLowerCase();
                
                if (productName.includes(searchTerm) || 
                    category.includes(searchTerm) || 
                    productId.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Search in cards
            cards.forEach(card => {
                const productName = card.querySelector('.product-name').textContent.toLowerCase();
                const category = card.querySelector('.inventory-card-body').textContent.toLowerCase();
                const productId = card.querySelector('h5').textContent.toLowerCase();
                
                if (productName.includes(searchTerm) || 
                    category.includes(searchTerm) || 
                    productId.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php include '../../layouts/footer.php'; ?>