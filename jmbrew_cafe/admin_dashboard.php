<?php
// admin_dashboard.php
require_once 'config.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - JMBrew Café</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        /* SMOOTH FADE-IN ANIMATION */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-logo {
            margin-bottom: 10px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar-menu i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        /* Header with USEFUL REFRESH BUTTON */
        .content-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Section */
        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        /* Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        /* Tables */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table {
            margin-bottom: 0;
        }

        /* Product Image Preview */
        .product-img-preview {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* LOADING INDICATOR */
        .btn-primary:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }

        .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .content-header {
                flex-direction: column;
                gap: 15px;
            }

            #inventory .row,
            #statistics .row {
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo"><img class="img-fluid d-block" src="uploads/logo.svg" alt="JMBrew Cafe Logo"></div>
            <small>Admin Dashboard</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="#" class="active" onclick="showSection('kitchen')">
                    <i class="fas fa-utensils"></i> Kitchen Orders
                </a></li>
            <li><a href="#" onclick="showSection('cashier')">
                    <i class="fas fa-cash-register"></i> Cashier
                </a></li>
            <li><a href="#" onclick="showSection('products')">
                    <i class="fas fa-box"></i> Product Management
                </a></li>
            <li><a href="#" onclick="showSection('inventory')">
                    <i class="fas fa-warehouse"></i> Inventory
                </a></li>
            <li><a href="#" onclick="showSection('vip')">
                    <i class="fas fa-crown"></i> VIP Management
                </a></li>
            <li><a href="#" onclick="showSection('statistics'); return false;">
                    <i class="fas fa-chart-bar"></i> Statistics
                </a></li>
            <li><a href="?logout=1" style="background: rgba(39, 22, 22, 0.67);">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <div>
                <h2 id="sectionTitle">Kitchen Orders</h2>
                <small class="text-muted">Auto-refreshing every 2-3 seconds • Priority resets every 12 hours</small>
            </div>
            <div>
                <button class="btn btn-primary" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Kitchen Orders Section -->
        <div id="kitchen" class="section active">
            <div class="table-container">
                <h4 class="mb-4">Active Orders</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Priority #</th>
                                <th>Items</th>
                                <th>Order Type</th>
                                <th>Status</th>
                                <th>VIP</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="kitchenOrdersTable">
                            <tr>
                                <td colspan="7" class="text-center">Loading orders...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cashier Section -->
        <div id="cashier" class="section">
            <div class="table-container">
                <h4 class="mb-4">Ready Orders</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Priority #</th>
                                <th>Items</th>
                                <th>Subtotal</th>
                                <th>Discount</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>VIP ID</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="cashierOrdersTable">
                            <tr>
                                <td colspan="8" class="text-center">Loading orders...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div id="products" class="section">
            <div class="mb-4">
                <button class="btn btn-primary" onclick="showAddProductModal()">
                    <i class="fas fa-plus"></i> Add New Product
                </button>
            </div>

            <div class="table-container">
                <h4 class="mb-4">Product List</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productsTable">
                            <tr>
                                <td colspan="7" class="text-center">Loading products...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Inventory Section -->
        <div id="inventory" class="section">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <div class="stat-icon text-primary">
                            <i class="fas fa-coffee"></i>
                        </div>
                        <h5>Coffee Items</h5>
                        <h2 id="coffeeCount">0</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <div class="stat-icon text-warning">
                            <i class="fas fa-glass-whiskey"></i>
                        </div>
                        <h5>Juice Items</h5>
                        <h2 id="juiceCount">0</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <div class="stat-icon text-success">
                            <i class="fas fa-hamburger"></i>
                        </div>
                        <h5>Sandwich Items</h5>
                        <h2 id="sandwichCount">0</h2>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <h4 class="mb-4">Stock Status</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="inventoryTable">
                            <tr>
                                <td colspan="5" class="text-center">Loading inventory...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- VIP Management Section -->
        <div id="vip" class="section">
            <div class="mb-4">
                <button class="btn btn-primary" onclick="showAddVIPModal()">
                    <i class="fas fa-plus"></i> Add VIP Customer
                </button>
            </div>

            <div class="table-container">
                <h4 class="mb-4">VIP Customers</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>VIP ID</th>
                                <th>Discount</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="vipTable">
                            <tr>
                                <td colspan="4" class="text-center">Loading VIP customers...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div id="statistics" class="section">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-icon text-success">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h6>Today's Sales</h6>
                        <h3 id="todaySales">₱0.00</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-icon text-primary">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h6>Total Orders</h6>
                        <h3 id="totalOrders">0</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-icon text-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h6>Pending Orders</h6>
                        <h3 id="pendingOrders">0</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-icon text-info">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h6>VIP Customers</h6>
                        <h3 id="vipCustomers">0</h3>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <h4 class="mb-4">Recent Orders</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Priority #</th>
                                <th>Date/Time</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="recentOrdersTable">
                            <tr>
                                <td colspan="5" class="text-center">Loading orders...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm" enctype="multipart/form-data">
                        <input type="hidden" id="productIdField" name="product_id">

                        <div class="mb-3 text-center" id="currentImagePreview" style="display:none;">
                            <label class="form-label">Current Image</label><br>
                            <img id="currentImage" src="" style="max-width: 200px; border-radius: 8px;" alt="Current product">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="imageInput" name="image" accept="image/*" required>
                            <small class="text-muted">Leave empty to keep current image (when editing)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="productName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-control" id="productCategory" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Coffee">Coffee</option>
                                <option value="Juices">Juices</option>
                                <option value="Sandwiches">Sandwiches</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (₱)</label>
                            <input type="number" class="form-control" id="productPrice" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" id="productStock" name="stock" min="0" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitProductBtn" onclick="addProduct()">Add Product</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add VIP Modal -->
    <div class="modal fade" id="addVIPModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add VIP Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addVIPForm">
                        <div class="mb-3">
                            <label class="form-label">VIP ID (10 digits)</label>
                            <input type="text" class="form-control" id="vipIdInput" maxlength="10" pattern="[0-9]{10}" required>
                            <small class="text-muted">Must be exactly 10 digits</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addVIP()">Add VIP</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div class="modal fade" id="updateStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStockForm">
                        <input type="hidden" id="stockProductId">
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="stockProductName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Stock</label>
                            <input type="number" class="form-control" id="stockCurrentQty" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Stock Quantity</label>
                            <input type="number" class="form-control" id="stockNewQty" min="0" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateStock()">Update Stock</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment" id="paymentCashier" value="Cashier" checked />
                        <label class="form-check-label" for="paymentCashier">
                            <i class="fas fa-cash-register"></i> Pay at Cashier
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment" id="paymentGcash" value="GCash" disabled />
                        <label class="form-check-label text-muted" for="paymentGcash">
                            <i class="fab fa-google-wallet"></i> GCash (Coming Soon)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmPayment()">Confirm Payment</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin.js"></script>
</body>

</html>