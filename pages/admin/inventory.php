<?php
require_once '../../includes/auth/auth_check.php';
require_once '../../includes/config/database.php';
checkAuth('admin');

$page_title = "Inventory Management";

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                // Add new item
                $stmt = $conn->prepare("INSERT INTO products (name, category_id, price, stock) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['item_name'],
                    $_POST['category'],
                    $_POST['price'],
                    $_POST['stock']
                ]);
                
                // Log activity
                $stmt = $conn->prepare("INSERT INTO activity_log (user_id, description, action) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    "Added new product: " . $_POST['item_name'],
                    "add_product"
                ]);
                
                $_SESSION['success_message'] = "Product added successfully!";
            } elseif ($_POST['action'] === 'add_category') {
                // Add new category
                $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$_POST['category_name']]);
                
                // Log activity
                $stmt = $conn->prepare("INSERT INTO activity_log (user_id, description, action) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    "Added new category: " . $_POST['category_name'],
                    "add_category"
                ]);
                
                $_SESSION['success_message'] = "Category added successfully!";
            } elseif ($_POST['action'] === 'edit') {
                // Update existing item
                $stmt = $conn->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, stock = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['item_name'],
                    $_POST['category'],
                    $_POST['price'],
                    $_POST['stock'],
                    $_POST['item_id']
                ]);
                
                // Log activity
                $stmt = $conn->prepare("INSERT INTO activity_log (user_id, description, action) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    "Updated product ID: " . $_POST['item_id'],
                    "update_product"
                ]);
                
                $_SESSION['success_message'] = "Product updated successfully!";
            } elseif ($_POST['action'] === 'delete') {
                // Delete item
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$_POST['item_id']]);
                
                // Log activity
                $stmt = $conn->prepare("INSERT INTO activity_log (user_id, description, action) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    "Deleted product ID: " . $_POST['item_id'],
                    "delete_product"
                ]);
                
                $_SESSION['success_message'] = "Product deleted successfully!";
            } elseif ($_POST['action'] === 'edit_category') {
                // Update existing category
                $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['category_name'],
                    $_POST['category_id']
                ]);
                
                // Log activity
                $stmt = $conn->prepare("INSERT INTO activity_log (user_id, description, action) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    "Updated category ID: " . $_POST['category_id'],
                    "update_category"
                ]);
                
                $_SESSION['success_message'] = "Category updated successfully!";
            } elseif ($_POST['action'] === 'delete_category') {
                // Check if category is being used
                $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                $stmt->execute([$_POST['category_id']]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $_SESSION['error_message'] = "Cannot delete category. It is used by {$count} products.";
                } else {
                    // Delete category
                    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$_POST['category_id']]);
                    
                    // Log activity
                    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, description, action) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        "Deleted category ID: " . $_POST['category_id'],
                        "delete_category"
                    ]);
                    
                    $_SESSION['success_message'] = "Category deleted successfully!";
                }
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header("Location: inventory.php");
    exit();
}

// Search functionality
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Fetch categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY id")->fetchAll();

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

<div style="display: flex; flex-direction: column; min-height: calc(100vh - 80px); margin-top: 10px;">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 1rem;">
        <h1 style="margin: 0; font-size: clamp(1.5rem, 4vw, 1.8rem);"><?php echo $page_title; ?></h1>
        <div class="header-buttons" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button class="btn btn-secondary" style="width: auto;" onclick="openAddCategoryModal()">
                <i class="fas fa-folder-plus"></i> <span class="btn-text">Add Category</span>
            </button>
            <button class="btn btn-primary" style="width: auto;" onclick="openAddModal()">
                <i class="fas fa-plus"></i> <span class="btn-text">Add New Item</span>
            </button>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="card categories-section" style="margin-bottom: 25px; padding: 20px; border-radius: 10px; background-color: var(--background-light);">
        <h2 style="margin-bottom: 20px; font-size: clamp(1.2rem, 3vw, 1.5rem);">Categories</h2>
        
        <div class="categories-grid" style="display: grid; gap: 15px;">
            <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <div class="category-name">
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                </div>
                <div class="actions">
                    <button class="btn btn-sm" onclick="openEditCategoryModal('<?php echo $category['id']; ?>', '<?php echo htmlspecialchars($category['name']); ?>')">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Inventory List -->
    <div class="card inventory-section" style="flex: 1; display: flex; flex-direction: column;">
        <h2 style="font-size: clamp(1.2rem, 3vw, 1.5rem);">Inventory Items</h2>
        
        <form method="GET" action="inventory.php" class="filter-form">
            <div class="filters-container">
                <input type="text" name="search" placeholder="Search inventory..." class="form-control search-input" value="<?php echo htmlspecialchars($search_query); ?>">
                <select name="category" class="select-control category-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="select-control status-select">
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
        
        <div class="table-responsive">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No inventory items found</td>
                    </tr>
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
                    <tr>
                        <td data-label="ID">#<?php echo str_pad($product['id'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td data-label="Name"><?php echo htmlspecialchars($product['name']); ?></td>
                        <td data-label="Category"><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td data-label="Price">₱<?php echo number_format($product['price'], 2); ?></td>
                        <td data-label="Stock"><?php echo $product['stock']; ?></td>
                        <td data-label="Status"><span class="badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                        <td data-label="Actions">
                            <button class="btn btn-sm" onclick="openEditModal('<?php echo $product['id']; ?>', '<?php echo htmlspecialchars($product['name']); ?>', '<?php echo $product['category_id']; ?>', '<?php echo $product['price']; ?>', '<?php echo $product['stock']; ?>')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: var(--background-light); margin: 10% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 700px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Add New Item</h2>
            <button class="btn btn-sm" onclick="closeModal('addItemModal')" style="background: none; color: var(--text-secondary);">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="addItemForm" method="POST" action="inventory.php" class="item-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <input type="hidden" name="action" value="add">
            
            <div class="form-row">
                <label for="add_item_name">Item Name</label>
                <input type="text" id="add_item_name" name="item_name" class="form-control" required>
            </div>
            
            <div class="form-row">
                <label for="add_category">Category</label>
                <select id="add_category" name="category" class="select-control" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <label for="add_price">Price (₱)</label>
                <input type="number" id="add_price" name="price" class="form-control" step="0.01" min="0" required>
            </div>
            
            <div class="form-row">
                <label for="add_stock">Stocks</label>
                <input type="number" id="add_stock" name="stock" class="form-control" min="0" required>
            </div>
            
            <div style="grid-column: 1 / -1; margin-top: 1rem; text-align: right;">
                <button type="button" class="btn btn-sm" onclick="closeModal('addItemModal')" style="margin-right: 0.5rem;">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" style="width: auto;">
                    <i class="fas fa-save"></i> Add Item
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editItemModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: var(--background-light); margin: 10% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 700px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Edit Item <span id="edit_item_id_display"></span></h2>
            <button class="btn btn-sm" onclick="closeModal('editItemModal')" style="background: none; color: var(--text-secondary);">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="editItemForm" method="POST" action="inventory.php" class="item-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_item_id" name="item_id">
            
            <div class="form-row">
                <label for="edit_item_name">Item Name</label>
                <input type="text" id="edit_item_name" name="item_name" class="form-control" required>
            </div>
            
            <div class="form-row">
                <label for="edit_category">Category</label>
                <select id="edit_category" name="category" class="select-control" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <label for="edit_price">Price (₱)</label>
                <input type="number" id="edit_price" name="price" class="form-control" step="0.01" min="0" required>
            </div>
            
            <div class="form-row">
                <label for="edit_stock">Stocks</label>
                <input type="number" id="edit_stock" name="stock" class="form-control" min="0" required>
            </div>
            
            <div style="grid-column: 1 / -1; margin-top: 1rem; display: flex; justify-content: space-between;">
                <button type="button" class="btn btn-sm" onclick="deleteItem()" style="background-color: rgba(255, 82, 82, 0.1); color: var(--danger-color);">
                    <i class="fas fa-trash"></i> Delete Item
                </button>
                
                <div>
                    <button type="button" class="btn btn-sm" onclick="closeModal('editItemModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" style="width: auto; margin-left: 0.5rem;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: var(--background-light); margin: 10% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 500px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Add New Category</h2>
            <button class="btn btn-sm" onclick="closeModal('addCategoryModal')" style="background: none; color: var(--text-secondary);">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="addCategoryForm" method="POST" action="inventory.php" class="item-form">
            <input type="hidden" name="action" value="add_category">
            
            <div class="form-row" style="margin-bottom: 20px;">
                <label for="category_name">Category Name</label>
                <input type="text" id="category_name" name="category_name" class="form-control" required>
            </div>
            
            <div style="margin-top: 1rem; text-align: right;">
                <button type="button" class="btn btn-sm" onclick="closeModal('addCategoryModal')" style="margin-right: 0.5rem;">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" style="width: auto;">
                    <i class="fas fa-save"></i> Add Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: var(--background-light); margin: 10% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 500px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Edit Category</h2>
            <button class="btn btn-sm" onclick="closeModal('editCategoryModal')" style="background: none; color: var(--text-secondary);">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="editCategoryForm" method="POST" action="inventory.php" class="item-form">
            <input type="hidden" name="action" value="edit_category">
            <input type="hidden" id="edit_category_id" name="category_id">
            
            <div class="form-row" style="margin-bottom: 20px;">
                <label for="edit_category_name">Category Name</label>
                <input type="text" id="edit_category_name" name="category_name" class="form-control" required>
            </div>
            
            <div style="margin-top: 1rem; display: flex; justify-content: space-between;">
                <button type="button" class="btn btn-sm" onclick="deleteCategory()" style="background-color: rgba(255, 82, 82, 0.1); color: var(--danger-color);">
                    <i class="fas fa-trash"></i> Delete Category
                </button>
                
                <div>
                    <button type="button" class="btn btn-sm" onclick="closeModal('editCategoryModal')" style="margin-right: 0.5rem;">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" style="width: auto;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('addItemModal').style.display = 'block';
    }
    
    function openAddCategoryModal() {
        document.getElementById('addCategoryModal').style.display = 'block';
    }
    
    function openEditCategoryModal(id, name) {
        document.getElementById('edit_category_id').value = id;
        document.getElementById('edit_category_name').value = name;
        document.getElementById('editCategoryModal').style.display = 'block';
    }
    
    function openEditModal(id, name, category, price, stock) {
        document.getElementById('edit_item_id').value = id;
        document.getElementById('edit_item_id_display').textContent = '#' + id;
        document.getElementById('edit_item_name').value = name;
        document.getElementById('edit_category').value = category;
        document.getElementById('edit_price').value = price;
        document.getElementById('edit_stock').value = stock;
        document.getElementById('editItemModal').style.display = 'block';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    function deleteItem() {
        if (confirm('Are you sure you want to delete this item?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'inventory.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'item_id';
            idInput.value = document.getElementById('edit_item_id').value;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function deleteCategory() {
        if (confirm('Are you sure you want to delete this category? If it contains products, you will not be able to delete it.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'inventory.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_category';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'category_id';
            idInput.value = document.getElementById('edit_category_id').value;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function confirmDeleteCategory(id, name) {
        if (confirm(`Are you sure you want to delete the category "${name}"? If it contains products, you will not be able to delete it.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'inventory.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_category';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'category_id';
            idInput.value = id;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Close modal when clicking outside the modal content
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>

<style>
    /* Responsive Styles */
    .categories-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }

    .category-card {
        background-color: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .category-name h3 {
        margin: 0;
        font-size: 16px;
        color: var(--text-primary);
    }

    .filters-container {
        display: grid;
        grid-template-columns: 1fr 200px 200px 150px;
        gap: 1rem;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .search-input,
    .category-select,
    .status-select {
        background-color: white;
        color: black;
        width: 100%;
    }

    .search-btn {
        width: auto;
    }

    .table-responsive {
        overflow-x: auto;
        margin: 0 -20px;
        padding: 0 20px;
    }

    .inventory-table {
        width: 100%;
        border-collapse: collapse;
    }

    .inventory-table th,
    .inventory-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        color: #E2E8F0;
    }

    .inventory-table th {
        background: rgba(0, 0, 0, 0.2);
        color: #94A3B8;
        font-weight: 500;
    }

    .badge {
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 600;
        border-radius: 0.25rem;
    }

    /* Responsive Breakpoints */
    @media (max-width: 1200px) {
        .filters-container {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 768px) {
        .filters-container {
            grid-template-columns: 1fr;
        }

        .header-buttons {
            width: 100%;
            justify-content: flex-start;
        }

        .inventory-table {
            border: 0;
        }

        .inventory-table thead {
            display: none;
        }

        .inventory-table tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
        }

        .inventory-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 0.5rem 0;
        }

        .inventory-table td:last-child {
            border-bottom: 0;
        }

        .inventory-table td::before {
            content: attr(data-label);
            font-weight: 500;
            color: #94A3B8;
            margin-right: 1rem;
        }

        .btn-text {
            display: none;
        }
    }

    @media (max-width: 576px) {
        .categories-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Modal Responsive Styles */
    .modal-content {
        width: 90%;
        max-width: 500px;
        margin: 20px auto;
    }

    @media (max-width: 576px) {
        .modal-content {
            width: 95%;
            margin: 10px;
            padding: 15px;
        }

        .item-form {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<?php include '../../layouts/footer.php'; ?> 