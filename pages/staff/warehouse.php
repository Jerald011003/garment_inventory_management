<?php
require_once '../../includes/auth/auth_check.php';
checkAuth('staff');

$page_title = "Warehouse Management";

// Check if we're processing an order
$processing_order = isset($_GET['id']);
// Check if we're restocking an item
$restocking = isset($_GET['action']) && $_GET['action'] === 'restock';

include '../../layouts/header.php';
?>

<?php if ($processing_order): ?>
    <!-- Process Order View -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Process Order #<?php echo htmlspecialchars($_GET['id']); ?></h2>
            <a href="dashboard.php" class="btn btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="order-details">
            <div class="order-info" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div>
                    <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Customer</h3>
                    <p>John Smith</p>
                    <p>john.smith@example.com</p>
                    <p>+1 (555) 123-4567</p>
                </div>
                <div>
                    <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Shipping Address</h3>
                    <p>123 Main Street</p>
                    <p>Apt 4B</p>
                    <p>New York, NY 10001</p>
                </div>
                <div>
                    <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Order Summary</h3>
                    <p>Date: Mar 18, 2024</p>
                    <p>Status: <span class="badge badge-warning">Pending</span></p>
                    <p>Items: 3</p>
                </div>
            </div>

            <h3 style="margin-bottom: 1rem;">Items to Pack</h3>
            <form action="" method="POST">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Packed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>T-Shirt Basic</td>
                                <td>2</td>
                                <td>Aisle A, Shelf 3</td>
                                <td><span class="badge badge-success">In Stock</span></td>
                                <td>
                                    <input type="checkbox" name="packed[]" value="1" class="form-check">
                                </td>
                            </tr>
                            <tr>
                                <td>Denim Jeans</td>
                                <td>1</td>
                                <td>Aisle B, Shelf 2</td>
                                <td><span class="badge badge-warning">Low Stock</span></td>
                                <td>
                                    <input type="checkbox" name="packed[]" value="2" class="form-check">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="order-actions" style="margin-top: 2rem;">
                    <div class="form-row">
                        <label for="status_update">Update Order Status</label>
                        <select id="status_update" name="status" class="select-control" style="max-width: 300px;">
                            <option value="processing">Mark as Processing</option>
                            <option value="packed">Mark as Packed</option>
                            <option value="shipped">Mark as Shipped</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <label for="staff_notes">Staff Notes</label>
                        <textarea id="staff_notes" name="staff_notes" class="form-control" rows="3" placeholder="Add any notes about this order..."></textarea>
                    </div>
                    
                    <div style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="width: auto;">
                            <i class="fas fa-check"></i> Update Order
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php elseif ($restocking): ?>
    <!-- Restock Item View -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Restock Item</h2>
            <a href="dashboard.php" class="btn btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <?php
        $item_id = isset($_GET['item']) ? $_GET['item'] : '';
        $item_details = [
            '2' => ['name' => 'Denim Jeans', 'current_stock' => 15, 'category' => 'Pants', 'location' => 'Aisle B, Shelf 2'],
            '6' => ['name' => 'Winter Jacket', 'current_stock' => 0, 'category' => 'Outerwear', 'location' => 'Aisle C, Shelf 1']
        ];
        
        $item = isset($item_details[$item_id]) ? $item_details[$item_id] : null;
        ?>
        
        <?php if ($item): ?>
            <form action="" method="POST" class="restock-form">
                <div class="form-row">
                    <label for="item_name">Item Name</label>
                    <input type="text" id="item_name" name="item_name" class="form-control" value="<?php echo $item['name']; ?>" readonly>
                </div>
                
                <div class="form-row">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" class="form-control" value="<?php echo $item['category']; ?>" readonly>
                </div>
                
                <div class="form-row">
                    <label for="current_stock">Current Stock</label>
                    <input type="number" id="current_stock" name="current_stock" class="form-control" value="<?php echo $item['current_stock']; ?>" readonly>
                </div>
                
                <div class="form-row">
                    <label for="location">Storage Location</label>
                    <input type="text" id="location" name="location" class="form-control" value="<?php echo $item['location']; ?>">
                </div>
                
                <div class="form-row">
                    <label for="quantity_to_add">Quantity to Add</label>
                    <input type="number" id="quantity_to_add" name="quantity_to_add" class="form-control" min="1" value="50" required>
                </div>
                
                <div class="form-row">
                    <label for="supplier">Supplier</label>
                    <select id="supplier" name="supplier" class="select-control">
                        <option value="1">TextilePro Inc.</option>
                        <option value="2">FabricMaster Supply</option>
                        <option value="3">GarmentSource Ltd.</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                </div>
                
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="width: auto;">
                        <i class="fas fa-plus"></i> Add Stock
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-danger">
                Item not found.
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Warehouse Overview -->
    <div style="margin-bottom: 20px;">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <input type="text" placeholder="Search inventory..." class="form-control">
            </div>
            <a href="warehouse.php?action=scan" class="btn btn-primary" style="width: auto;">
                <i class="fas fa-barcode"></i> Scan Item
            </a>
        </div>
    </div>

    <div class="card">
        <h2>Warehouse Inventory</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#001</td>
                        <td>T-Shirt Basic</td>
                        <td>Shirts</td>
                        <td>Aisle A, Shelf 3</td>
                        <td>250</td>
                        <td><span class="badge badge-success">In Stock</span></td>
                        <td>
                            <a href="warehouse.php?action=update&item=1" class="btn btn-sm">Update</a>
                        </td>
                    </tr>
                    <tr>
                        <td>#002</td>
                        <td>Denim Jeans</td>
                        <td>Pants</td>
                        <td>Aisle B, Shelf 2</td>
                        <td>15</td>
                        <td><span class="badge badge-warning">Low Stock</span></td>
                        <td>
                            <a href="warehouse.php?action=restock&item=2" class="btn btn-sm">Restock</a>
                        </td>
                    </tr>
                    <tr>
                        <td>#003</td>
                        <td>Cotton Hoodie</td>
                        <td>Outerwear</td>
                        <td>Aisle C, Shelf 4</td>
                        <td>78</td>
                        <td><span class="badge badge-success">In Stock</span></td>
                        <td>
                            <a href="warehouse.php?action=update&item=3" class="btn btn-sm">Update</a>
                        </td>
                    </tr>
                    <tr>
                        <td>#004</td>
                        <td>Polo Shirt</td>
                        <td>Shirts</td>
                        <td>Aisle A, Shelf 2</td>
                        <td>120</td>
                        <td><span class="badge badge-success">In Stock</span></td>
                        <td>
                            <a href="warehouse.php?action=update&item=4" class="btn btn-sm">Update</a>
                        </td>
                    </tr>
                    <tr>
                        <td>#005</td>
                        <td>Casual Shorts</td>
                        <td>Pants</td>
                        <td>Aisle B, Shelf 1</td>
                        <td>85</td>
                        <td><span class="badge badge-success">In Stock</span></td>
                        <td>
                            <a href="warehouse.php?action=update&item=5" class="btn btn-sm">Update</a>
                        </td>
                    </tr>
                    <tr>
                        <td>#006</td>
                        <td>Winter Jacket</td>
                        <td>Outerwear</td>
                        <td>Aisle C, Shelf 1</td>
                        <td>0</td>
                        <td><span class="badge badge-danger">Out of Stock</span></td>
                        <td>
                            <a href="warehouse.php?action=restock&item=6" class="btn btn-sm">Restock</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Warehouse Layout</h2>
        <div class="warehouse-layout" style="margin-top: 1rem; background: var(--background-dark); padding: 1.5rem; border-radius: 10px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
            <div class="section" style="padding: 1rem; background: rgba(108, 99, 255, 0.1); border-radius: 8px; text-align: center; border: 1px dashed var(--primary-color);">
                <h3>Aisle A</h3>
                <p>Shirts & Tops</p>
                <p>Capacity: 500 items</p>
                <p>Current: 370 items</p>
            </div>
            <div class="section" style="padding: 1rem; background: rgba(108, 99, 255, 0.1); border-radius: 8px; text-align: center; border: 1px dashed var(--primary-color);">
                <h3>Aisle B</h3>
                <p>Pants & Bottoms</p>
                <p>Capacity: 350 items</p>
                <p>Current: 100 items</p>
            </div>
            <div class="section" style="padding: 1rem; background: rgba(108, 99, 255, 0.1); border-radius: 8px; text-align: center; border: 1px dashed var(--primary-color);">
                <h3>Aisle C</h3>
                <p>Outerwear & Accessories</p>
                <p>Capacity: 200 items</p>
                <p>Current: 78 items</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include '../../layouts/footer.php'; ?> 