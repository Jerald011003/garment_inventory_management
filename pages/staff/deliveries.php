<?php
require_once '../../includes/auth/auth_check.php';
checkAuth('staff');

$page_title = "Delivery Tracking";

// Check if we're updating a specific delivery
$updating_delivery = isset($_GET['id']);

include '../../layouts/header.php';
?>

<?php if ($updating_delivery): ?>
    <!-- Update Specific Delivery -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Update Delivery #<?php echo htmlspecialchars($_GET['id']); ?></h2>
            <a href="deliveries.php" class="btn btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Deliveries
            </a>
        </div>
        
        <div class="delivery-tracker" style="margin-bottom: 2rem;">
            <div class="tracker-steps" style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; position: relative;">
                <div class="step-line" style="position: absolute; top: 25px; left: 50px; right: 50px; height: 2px; background-color: var(--border-color); z-index: 1;"></div>
                
                <div class="tracker-step" style="display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2;">
                    <div class="step-icon" style="width: 50px; height: 50px; border-radius: 50%; background-color: var(--success-color); display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
                        <i class="fas fa-box" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div class="step-label" style="text-align: center;">
                        <div style="font-weight: 600;">Processed</div>
                        <div style="color: var(--text-secondary); font-size: 0.75rem;">Mar 16, 2024</div>
                    </div>
                </div>
                
                <div class="tracker-step" style="display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2;">
                    <div class="step-icon" style="width: 50px; height: 50px; border-radius: 50%; background-color: var(--success-color); display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
                        <i class="fas fa-warehouse" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div class="step-label" style="text-align: center;">
                        <div style="font-weight: 600;">Packed</div>
                        <div style="color: var(--text-secondary); font-size: 0.75rem;">Mar 16, 2024</div>
                    </div>
                </div>
                
                <div class="tracker-step" style="display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2;">
                    <div class="step-icon" style="width: 50px; height: 50px; border-radius: 50%; background-color: var(--success-color); display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
                        <i class="fas fa-shipping-fast" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div class="step-label" style="text-align: center;">
                        <div style="font-weight: 600;">Shipped</div>
                        <div style="color: var(--text-secondary); font-size: 0.75rem;">Mar 17, 2024</div>
                    </div>
                </div>
                
                <div class="tracker-step" style="display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2;">
                    <div class="step-icon" style="width: 50px; height: 50px; border-radius: 50%; background-color: rgba(108, 99, 255, 0.2); display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; border: 2px dashed var(--primary-color);">
                        <i class="fas fa-truck" style="color: var(--primary-color); font-size: 20px;"></i>
                    </div>
                    <div class="step-label" style="text-align: center;">
                        <div style="font-weight: 600;">In Transit</div>
                        <div style="color: var(--text-secondary); font-size: 0.75rem;">Current</div>
                    </div>
                </div>
                
                <div class="tracker-step" style="display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2;">
                    <div class="step-icon" style="width: 50px; height: 50px; border-radius: 50%; background-color: var(--background-dark); display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; border: 2px dashed var(--border-color);">
                        <i class="fas fa-flag-checkered" style="color: var(--text-secondary); font-size: 20px;"></i>
                    </div>
                    <div class="step-label" style="text-align: center;">
                        <div style="font-weight: 600;">Delivered</div>
                        <div style="color: var(--text-secondary); font-size: 0.75rem;">Pending</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="order-details">
            <div class="order-info" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div>
                    <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Customer</h3>
                    <p>Michael Brown</p>
                    <p>michael.brown@example.com</p>
                    <p>+1 (555) 987-6543</p>
                </div>
                <div>
                    <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Shipping Address</h3>
                    <p>456 Park Avenue</p>
                    <p>Unit 7C</p>
                    <p>Chicago, IL 60601</p>
                </div>
                <div>
                    <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Delivery Info</h3>
                    <p>Carrier: FastShip Express</p>
                    <p>Tracking: FSEX12345678</p>
                    <p>Est. Delivery: Mar 19, 2024</p>
                </div>
            </div>
            
            <h3 style="margin-bottom: 1rem;">Items in Order</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>T-Shirt Basic</td>
                            <td>1</td>
                            <td><span class="badge badge-info">In Transit</span></td>
                        </tr>
                        <tr>
                            <td>Cotton Hoodie</td>
                            <td>1</td>
                            <td><span class="badge badge-info">In Transit</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <form action="" method="POST" style="margin-top: 2rem;">
                <div class="form-row">
                    <label for="status_update">Update Delivery Status</label>
                    <select id="status_update" name="status" class="select-control" style="max-width: 300px;">
                        <option value="in_transit">In Transit</option>
                        <option value="out_for_delivery">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                        <option value="failed_delivery">Failed Delivery</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="delivery_notes">Delivery Notes</label>
                    <textarea id="delivery_notes" name="delivery_notes" class="form-control" rows="3" placeholder="Add any notes about this delivery..."></textarea>
                </div>
                
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="width: auto;">
                        <i class="fas fa-check"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- Deliveries List -->
    <div class="action-buttons" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" placeholder="Search deliveries..." class="form-control">
            </div>
            <div style="min-width: 150px;">
                <select class="select-control">
                    <option value="">All Statuses</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="in_transit">In Transit</option>
                    <option value="delivered">Delivered</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Active Deliveries</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Ship Date</th>
                        <th>Estimated Delivery</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#ORD-003</td>
                        <td>Michael Brown</td>
                        <td>Mar 16, 2024</td>
                        <td>Mar 19, 2024</td>
                        <td><span class="badge badge-info">In Transit</span></td>
                        <td>
                            <a href="deliveries.php?id=ORD-003" class="btn btn-sm">Update</a>
                        </td>
                    </tr>
                    <tr>
                        <td>#ORD-004</td>
                        <td>Jennifer Davis</td>
                        <td>Mar 15, 2024</td>
                        <td>Mar 18, 2024</td>
                        <td><span class="badge badge-info">In Transit</span></td>
                        <td>
                            <a href="deliveries.php?id=ORD-004" class="btn btn-sm">Update</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <h2>Delivery History</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Ship Date</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#ORD-002</td>
                        <td>Emma Johnson</td>
                        <td>Mar 15, 2024</td>
                        <td>Mar 17, 2024</td>
                        <td><span class="badge badge-success">Delivered</span></td>
                        <td>
                            <a href="deliveries.php?id=ORD-002" class="btn btn-sm">View</a>
                        </td>
                    </tr>
                    <tr>
                        <td>#ORD-005</td>
                        <td>Robert Wilson</td>
                        <td>Mar 12, 2024</td>
                        <td>Mar 14, 2024</td>
                        <td><span class="badge badge-success">Delivered</span></td>
                        <td>
                            <a href="deliveries.php?id=ORD-005" class="btn btn-sm">View</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 1rem; display: flex; justify-content: center;">
            <div class="pagination" style="display: flex; gap: 0.5rem;">
                <a href="#" class="btn btn-sm">Previous</a>
                <a href="#" class="btn btn-sm" style="background-color: rgba(108, 99, 255, 0.2);">1</a>
                <a href="#" class="btn btn-sm">2</a>
                <a href="#" class="btn btn-sm">3</a>
                <a href="#" class="btn btn-sm">Next</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include '../../layouts/footer.php'; ?> 