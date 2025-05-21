<?php
require_once '../../includes/auth/auth_check.php';
require_once '../../includes/config/database.php';
checkAuth('sales');

$page_title = "Products Inventory";

// Fetch customers for dropdown
$customers = $conn->query("SELECT * FROM customers ORDER BY name")->fetchAll();

// Process order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['submit_order'])) {
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Get product details
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        // Validate quantity
        $product_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $product_stmt->execute([$product_id]);
        $product = $product_stmt->fetch();
        
        if (!$product || $product['stock'] < $quantity) {
            throw new Exception("Invalid product or insufficient stock");
        }
        
        // Calculate order total
        $unit_price = $product['price'];
        $total_amount = $quantity * $unit_price;
        
        // Handle customer creation/selection
        $customer_id = null;
        if (isset($_POST['customer_id']) && $_POST['customer_id'] === 'new') {
            // Create new customer
            $customer_name = $_POST['new_customer_name'];
            $customer_phone = $_POST['new_customer_phone'] ?? null;
            $customer_address = $_POST['new_customer_address'] ?? null;
            
            if (empty($customer_name)) {
                throw new Exception("Customer name is required");
            }
            
            $customer_stmt = $conn->prepare("INSERT INTO customers (name, phone, address) VALUES (?, ?, ?)");
            $customer_stmt->execute([$customer_name, $customer_phone, $customer_address]);
            $customer_id = $conn->lastInsertId();
        } else {
            // Use existing customer
            $customer_id = $_POST['customer_id'] ?? 1; // Default to walk-in customer if none selected
        }
        
        // Create the order
        $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, created_by) VALUES (?, ?, ?)");
        $order_stmt->execute([$customer_id, $total_amount, $_SESSION['user_id']]);
        $order_id = $conn->lastInsertId();
        
        // Create order item
        $order_item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
        $order_item_stmt->execute([$order_id, $product_id, $quantity, $unit_price, $total_amount]);
        
        // Update product stock - reduce by the ordered quantity
        $old_stock = $product['stock'];
        $new_stock = $old_stock - $quantity;
        $update_stock_stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $update_stock_stmt->execute([$new_stock, $product_id]);
        
        // Log activity
        $activity_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description) VALUES (?, ?, ?)");
        $activity_stmt->execute([
            $_SESSION['user_id'],
            'create_order',
            "Created order #$order_id for $quantity units of product #$product_id (Stock reduced from $old_stock to $new_stock)"
        ]);
        
        // Commit transaction
        $conn->commit();
        
        // Store success message in session and redirect to GET
        $_SESSION['success_message'] = "Order #$order_id has been created successfully! Stock of {$product['name']} reduced from $old_stock to $new_stock units.";
        header('Location: inventory.php');
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header('Location: inventory.php');
        exit;
    }
}

// Get messages from session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Search functionality
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Fetch categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Fetch products with category names
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";

$params = [];

if (!empty($search_query)) {
    $query .= " AND p.name LIKE ?";
    $params[] = "%$search_query%";
}

if (!empty($category_filter)) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($status_filter)) {
    switch ($status_filter) {
        case 'in_stock':
            $query .= " AND p.stock > 10";
            break;
        case 'low_stock':
            $query .= " AND p.stock > 0 AND p.stock <= 10";
            break;
        case 'out_of_stock':
            $query .= " AND p.stock <= 0";
            break;
    }
}

$query .= " ORDER BY p.name";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

include '../../layouts/header.php';
?>

<div >
    <?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Search and Filter Section -->
    <h1>Inventory</h1>
    <form method="GET" action="inventory.php">
        <div class="filter-container">
            <input type="text" name="search" placeholder="Search products..." class="form-control" style="background-color: white; color: black;" value="<?php echo htmlspecialchars($search_query); ?>">
            <select name="category" class="select-control" style="background-color: white; color: black;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="select-control" style="background-color: white; color: black;">
                <option value="">All Status</option>
                <option value="in_stock" <?php echo $status_filter === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                <option value="low_stock" <?php echo $status_filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                <option value="out_of_stock" <?php echo $status_filter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
            </select>
            <button type="submit" class="btn btn-sm search-btn">
                <i class="fas fa-search"></i> Search
            </button>
        </div>
    </form>

    <style>
        .filter-container {
            margin-bottom: 20px;
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 200px 200px 150px;
            gap: 1rem;
            border-radius: 6px;
        }

        @media (max-width: 1024px) {
            .filter-container {
                grid-template-columns: 1fr 1fr;
            }
            .search-btn {
                grid-column: 2;
                justify-self: end;
            }
        }

        @media (max-width: 576px) {
            .filter-container {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            .search-btn {
                grid-column: 1;
                width: 100%;
            }
            .select-control {
                width: 100%;
            }
        }
    </style>

    <!-- Products Grid -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 gy-3 gx-3">
        <?php if (empty($products)): ?>
        <div class="col-12 text-center py-5">
            <h4 class="text-white text-center">No items available yet</h4>
        </div>
        <?php else: ?>
        <?php foreach ($products as $product): 
            $status_class = 'success';
            $status_text = 'In Stock';
            
            if ($product['stock'] <= 0) {
                $status_class = 'danger';
                $status_text = 'Out of Stock';
            } elseif ($product['stock'] <= 10) {
                $status_class = 'warning';
                $status_text = 'Low Stock';
            }
        ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </div>
                    <div>
                        <p class="card-text">
                            <strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?><br>
                            <strong>Stock:</strong> <?php echo $product['stock']; ?> units<br>
                            <strong>Price:</strong> ₱<?php echo number_format($product['price'], 2); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-5">
                            <button type="button" 
                                    class="btn btn-primary <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>"
                                    <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                                    onclick="createOrder({
                                        id: <?php echo $product['id']; ?>,
                                        name: '<?php echo addslashes(htmlspecialchars($product['name'])); ?>',
                                        stock: <?php echo $product['stock']; ?>,
                                        price: <?php echo floatval($product['price']); ?>
                                    })">
                                <i class="fas fa-shopping-cart"></i> Create Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Create Order Modal -->
<div id="createOrderModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: var(--background-light); margin: 10% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 500px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Create Order</h2>
            <button class="btn btn-sm" onclick="closeOrderModal()" style="background: none; color: var(--text-secondary);">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="createOrderForm" method="POST" action="javascript:void(0);" class="item-form" style="display: grid; gap: 1.5rem;" onsubmit="submitOrderForm(event)">
            <input type="hidden" id="product_id" name="product_id">
            <input type="hidden" name="submit_order" value="1">
            
            <div class="form-row">
                <label for="modalProductName">Product</label>
                <p class="fw-bold" id="modalProductName" style="margin-bottom: 0;"></p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-row">
                    <label for="modalAvailableStock">Available Stock</label>
                    <p id="modalAvailableStock" style="margin-bottom: 0;"></p>
                </div>
                
                <div class="form-row">
                    <label for="modalPrice">Price per unit</label>
                    <p style="margin-bottom: 0;">₱<span id="modalPrice"></span></p>
                </div>
            </div>
            
            <div class="form-row">
                <label for="orderQuantity">Quantity</label>
                <input type="number" id="orderQuantity" name="quantity" class="form-control" required min="1">
                <small class="form-text text-muted">Maximum order: <span id="maxOrder">0</span> units</small>
            </div>

            <div class="form-row">
                <label for="customerType">Customer</label>
                <select id="customerType" name="customer_id" class="form-control" required onchange="toggleNewCustomerFields()">
                    <option value="">Select Customer</option>
                    <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                    <?php endforeach; ?>
                    <option value="new">New Customer</option>
                </select>
            </div>

            <div id="newCustomerFields" style="display: none;">
                <div class="form-row">
                    <label for="newCustomerName">Customer Name</label>
                    <input type="text" id="newCustomerName" name="new_customer_name" class="form-control">
                </div>
                
                <div class="form-row">
                    <label for="newCustomerPhone">Phone Number (Optional)</label>
                    <input type="tel" id="newCustomerPhone" name="new_customer_phone" class="form-control">
                </div>
                
                <div class="form-row">
                    <label for="newCustomerAddress">Address (Optional)</label>
                    <textarea id="newCustomerAddress" name="new_customer_address" class="form-control" rows="2"></textarea>
                </div>
            </div>
            
            <div style="grid-column: 1 / -1; margin-top: 1rem; text-align: right;">
                <button type="button" class="btn btn-sm" onclick="closeOrderModal()" style="margin-right: 0.5rem;">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="confirmOrder" style="width: auto;">
                    <i class="fas fa-shopping-cart text-md"></i> Place Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Check for success message and show toast notification
<?php if (isset($success_message)): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Show success toast
    const toastEl = document.createElement('div');
    toastEl.className = 'position-fixed bottom-0 end-0 p-3';
    toastEl.style.zIndex = '9999';
    toastEl.innerHTML = `
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${<?php echo json_encode($success_message); ?>}
            </div>
        </div>
    `;
    document.body.appendChild(toastEl);
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        const toast = bootstrap.Toast.getOrCreateInstance(toastEl.querySelector('.toast'));
        toast.dispose();
        toastEl.remove();
    }, 5000);
});
<?php endif; ?>

function createOrder(product) {
    console.log('createOrder function called with product:', product);
    
    // Don't proceed if stock is 0
    if (product.stock <= 0) {
        alert("Cannot create order for products with zero stock.");
        return;
    }
    
    // Populate modal with product details
    document.getElementById('product_id').value = product.id;
    document.getElementById('modalProductName').textContent = product.name;
    document.getElementById('modalAvailableStock').textContent = product.stock + ' units';
    
    // Convert price to a number before using toFixed
    const price = parseFloat(product.price);
    document.getElementById('modalPrice').textContent = price.toFixed(2);
    document.getElementById('maxOrder').textContent = product.stock;
    
    // Set maximum order quantity
    const quantityInput = document.getElementById('orderQuantity');
    quantityInput.max = product.stock;
    quantityInput.value = 1;
    
    // Add quantity change listener
    quantityInput.addEventListener('input', function() {
        // Ensure quantity doesn't exceed stock
        if (parseInt(this.value) > product.stock) {
            this.value = product.stock;
            alert("Order quantity cannot exceed available stock of " + product.stock + " units.");
        }
        
        if (parseInt(this.value) < 1) {
            this.value = 1;
        }
        
        // Enable/disable the submit button based on quantity validation
        const submitButton = document.getElementById('confirmOrder');
        if (parseInt(this.value) > product.stock || parseInt(this.value) < 1) {
            submitButton.disabled = true;
        } else {
            submitButton.disabled = false;
        }
    });
    
    // Show modal
    document.getElementById('createOrderModal').style.display = 'block';
}

function closeOrderModal() {
    document.getElementById('createOrderModal').style.display = 'none';
}

function toggleNewCustomerFields() {
    const customerType = document.getElementById('customerType');
    const newCustomerFields = document.getElementById('newCustomerFields');
    const newCustomerName = document.getElementById('newCustomerName');
    
    if (customerType.value === 'new') {
        newCustomerFields.style.display = 'grid';
        newCustomerName.required = true;
    } else {
        newCustomerFields.style.display = 'none';
        newCustomerName.required = false;
    }
}

function submitOrderForm(event) {
    event.preventDefault();
    
    const form = document.getElementById('createOrderForm');
    const quantityInput = document.getElementById('orderQuantity');
    const maxStock = parseInt(document.getElementById('maxOrder').textContent);
    
    // Additional validation before submission
    if (parseInt(quantityInput.value) > maxStock) {
        alert("Order quantity cannot exceed available stock of " + maxStock + " units.");
        return false;
    }
    
    if (form.checkValidity()) {
        // Disable submit button to prevent double submission
        const submitBtn = document.getElementById('confirmOrder');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        
        // Create FormData object
        const formData = new FormData(form);
        
        // Submit form using fetch API
        fetch('inventory.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            // Reload the page without form resubmission prompt
            window.location.href = 'inventory.php?t=' + new Date().getTime();
        })
        .catch(error => {
            console.error('Error:', error);
            // Re-enable button if there's an error
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Place Order';
            alert('There was an error creating the order. Please try again.');
        });
    } else {
        form.reportValidity();
    }
}

// Handle order confirmation - now uses the form's own submit event
document.getElementById('confirmOrder').addEventListener('click', function() {
    document.getElementById('createOrderForm').submit();
});

// Close modal when clicking outside the modal content
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include '../../layouts/footer.php'; ?> 