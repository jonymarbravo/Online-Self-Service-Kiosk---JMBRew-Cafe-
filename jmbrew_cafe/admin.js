// admin.js

let autoRefreshInterval = null;
let currentSection = "kitchen";
let isLoading = false;

// Toggle sidebar on mobile
function toggleSidebar() {
  document.getElementById("sidebar").classList.toggle("show");
}

// Show section
function showSection(sectionName) {
  if (isLoading) return;

  document.querySelectorAll(".section").forEach((section) => {
    section.classList.remove("active");
  });

  document.querySelectorAll(".sidebar-menu a").forEach((link) => {
    link.classList.remove("active");
  });

  document.getElementById(sectionName).classList.add("active");
  event.target.classList.add("active");

  const titles = {
    kitchen: "Kitchen Orders",
    cashier: "Cashier",
    products: "Product Management",
    inventory: "Inventory",
    vip: "VIP Management",
    statistics: "Statistics",
  };
  document.getElementById("sectionTitle").textContent = titles[sectionName];

  currentSection = sectionName;
  loadSectionData(sectionName);

  if (window.innerWidth <= 768) {
    document.getElementById("sidebar").classList.remove("show");
  }

  startAutoRefresh();
}

// Load section data
function loadSectionData(section) {
  switch (section) {
    case "kitchen":
      loadKitchenOrders();
      break;
    case "cashier":
      loadCashierOrders();
      break;
    case "products":
      loadProducts();
      break;
    case "inventory":
      loadInventory();
      break;
    case "vip":
      loadVIPCustomers();
      break;
    case "statistics":
      loadStatistics();
      break;
  }
}

// USEFUL REFRESH BUTTON
function refreshData() {
  if (isLoading) return;

  const refreshBtn = event?.target.closest("button");
  if (refreshBtn) {
    const originalHTML = refreshBtn.innerHTML;
    refreshBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    refreshBtn.disabled = true;
  }

  isLoading = true;
  loadSectionData(currentSection);

  setTimeout(() => {
    if (refreshBtn) {
      refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
      refreshBtn.disabled = false;
    }
    isLoading = false;
  }, 500);
}

// START AUTO-REFRESH
function startAutoRefresh() {
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval);
  }

  let refreshTime = 2000;

  if (currentSection === "kitchen" || currentSection === "cashier") {
    refreshTime = 2000;
  } else if (currentSection === "statistics") {
    refreshTime = 3000;
  } else {
    refreshTime = 5000;
  }

  autoRefreshInterval = setInterval(() => {
    if (!isLoading) {
      loadSectionData(currentSection);
    }
  }, refreshTime);
}

// STOP AUTO-REFRESH
function stopAutoRefresh() {
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval);
    autoRefreshInterval = null;
  }
}

// Load kitchen orders
async function loadKitchenOrders() {
  try {
    const response = await fetch("admin/get_kitchen_orders.php", {
      cache: "no-cache",
    });
    const data = await response.json();

    const table = document.getElementById("kitchenOrdersTable");

    if (data.success && data.orders.length > 0) {
      table.innerHTML = data.orders
        .map(
          (order) => `
                <tr class="fade-in">
                    <td><strong>#${String(order.priority_number).padStart(
                      2,
                      "0"
                    )}</strong></td>
                    <td>${order.items_summary}</td>
                    <td>${order.order_types}</td>
                    <td><span class="badge bg-${
                      order.status === "preparing" ? "warning" : "info"
                    }">${order.status}</span></td>
                    <td>${
                      order.is_vip
                        ? '<i class="fas fa-crown text-warning"></i> VIP'
                        : "-"
                    }</td>
                    <td>${formatTime(order.created_at)}</td>
                    <td>
                        ${
                          order.status !== "ready"
                            ? `<button class="btn btn-sm btn-success" onclick="markOrderReady(${order.id})">
                                <i class="fas fa-check"></i> Done
                            </button>`
                            : '<span class="badge bg-success">Ready</span>'
                        }
                    </td>
                </tr>
            `
        )
        .join("");
    } else {
      table.innerHTML =
        '<tr><td colspan="7" class="text-center text-muted">No active orders</td></tr>';
    }
  } catch (error) {
    console.error("Error loading kitchen orders:", error);
  }
}

// Load cashier orders
async function loadCashierOrders() {
  try {
    const response = await fetch("admin/get_cashier_orders.php", {
      cache: "no-cache",
    });
    const data = await response.json();

    const table = document.getElementById("cashierOrdersTable");

    if (data.success && data.orders.length > 0) {
      table.innerHTML = data.orders
        .map(
          (order) => `
                <tr class="fade-in">
                    <td><strong>#${String(order.priority_number).padStart(
                      2,
                      "0"
                    )}</strong></td>
                    <td>
                        <small>${order.items_summary}</small>
                    </td>
                    <td>₱${parseFloat(order.total_price).toFixed(2)}</td>
                    <td class="text-success">₱${parseFloat(
                      order.discount_amount
                    ).toFixed(2)}</td>
                    <td><strong>₱${parseFloat(order.final_price).toFixed(
                      2
                    )}</strong></td>
                    <td><span class="badge bg-primary">${
                      order.payment_method
                    }</span></td>
                    <td>${order.is_vip ? order.vip_id : "-"}</td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="completeOrder(${
                          order.id
                        })">
                            <i class="fas fa-check-circle"></i> Complete
                        </button>
                    </td>
                </tr>
            `
        )
        .join("");
    } else {
      table.innerHTML =
        '<tr><td colspan="8" class="text-center text-muted">No ready orders</td></tr>';
    }
  } catch (error) {
    console.error("Error loading cashier orders:", error);
  }
}

// Load products
async function loadProducts() {
  try {
    const response = await fetch("api/get_products.php");
    const data = await response.json();

    const table = document.getElementById("productsTable");

    if (data.success && data.products.length > 0) {
      table.innerHTML = data.products
        .map(
          (product) => `
                <tr class="fade-in">
                    <td><img src="${
                      product.image_path
                    }" class="product-img-preview" alt="${product.name}"></td>
                    <td>${product.name}</td>
                    <td><span class="badge bg-secondary">${
                      product.category
                    }</span></td>
                    <td>₱${parseFloat(product.price).toFixed(2)}</td>
                    <td>${product.stock_quantity}</td>
                    <td>
                        ${
                          product.is_available
                            ? '<span class="badge bg-success">Available</span>'
                            : '<span class="badge bg-danger">Out of Stock</span>'
                        }
                    </td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editProduct(${
                          product.id
                        }); event.stopPropagation();">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(${
                          product.id
                        }); event.stopPropagation();">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `
        )
        .join("");
    } else {
      table.innerHTML =
        '<tr><td colspan="7" class="text-center text-muted">No products yet. Add your first product!</td></tr>';
    }
  } catch (error) {
    console.error("Error loading products:", error);
  }
}

// Load inventory
async function loadInventory() {
  try {
    const response = await fetch("api/get_products.php");
    const data = await response.json();

    if (data.success) {
      const counts = {
        Coffee: 0,
        Juices: 0,
        Sandwiches: 0,
      };

      data.products.forEach((p) => {
        if (counts[p.category] !== undefined) counts[p.category]++;
      });

      document.getElementById("coffeeCount").textContent = counts.Coffee;
      document.getElementById("juiceCount").textContent = counts.Juices;
      document.getElementById("sandwichCount").textContent = counts.Sandwiches;

      const table = document.getElementById("inventoryTable");
      if (data.products.length > 0) {
        table.innerHTML = data.products
          .map(
            (product) => `
                    <tr class="fade-in">
                        <td>${product.name}</td>
                        <td><span class="badge bg-secondary">${
                          product.category
                        }</span></td>
                        <td><strong>${product.stock_quantity}</strong></td>
                        <td>
                            ${
                              product.stock_quantity === 0
                                ? '<span class="badge bg-danger">Out of Stock</span>'
                                : product.stock_quantity < 5
                                ? '<span class="badge bg-warning">Low Stock</span>'
                                : '<span class="badge bg-success">In Stock</span>'
                            }
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="showUpdateStockModal(${
                              product.id
                            }, '${product.name}', ${product.stock_quantity})">
                                <i class="fas fa-plus"></i> Update Stock
                            </button>
                        </td>
                    </tr>
                `
          )
          .join("");
      } else {
        table.innerHTML =
          '<tr><td colspan="5" class="text-center text-muted">No products in inventory</td></tr>';
      }
    }
  } catch (error) {
    console.error("Error loading inventory:", error);
  }
}

// Load VIP customers
async function loadVIPCustomers() {
  try {
    const response = await fetch("admin/get_vip_customers.php");
    const data = await response.json();

    const table = document.getElementById("vipTable");

    if (data.success && data.vips.length > 0) {
      table.innerHTML = data.vips
        .map(
          (vip) => `
                <tr class="fade-in">
                    <td><strong>${vip.vip_id}</strong></td>
                    <td><span class="badge bg-warning">${
                      vip.discount_percentage
                    }%</span></td>
                    <td>${formatDate(vip.created_at)}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteVIP('${
                          vip.vip_id
                        }')">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </td>
                </tr>
            `
        )
        .join("");
    } else {
      table.innerHTML =
        '<tr><td colspan="4" class="text-center text-muted">No VIP customers yet</td></tr>';
    }
  } catch (error) {
    console.error("Error loading VIP customers:", error);
  }
}

// Load statistics
async function loadStatistics() {
  try {
    const response = await fetch("admin/get_statistics.php");
    const data = await response.json();

    if (data.success) {
      document.getElementById("todaySales").textContent = `₱${parseFloat(
        data.today_sales
      ).toFixed(2)}`;
      document.getElementById("totalOrders").textContent = data.total_orders;
      document.getElementById("pendingOrders").textContent =
        data.pending_orders;
      document.getElementById("vipCustomers").textContent = data.vip_count;

      const table = document.getElementById("recentOrdersTable");
      if (data.recent_orders.length > 0) {
        table.innerHTML = data.recent_orders
          .map(
            (order) => `
                    <tr class="fade-in">
                        <td><strong>#${String(order.priority_number).padStart(
                          2,
                          "0"
                        )}</strong></td>
                        <td>${formatDateTime(order.created_at)}</td>
                        <td>${order.items_summary}</td>
                        <td>₱${parseFloat(order.final_price).toFixed(2)}</td>
                        <td><span class="badge bg-${getStatusColor(
                          order.order_status
                        )}">${order.order_status}</span></td>
                    </tr>
                `
          )
          .join("");
      } else {
        table.innerHTML =
          '<tr><td colspan="5" class="text-center text-muted">No orders yet</td></tr>';
      }
    }
  } catch (error) {
    console.error("Error loading statistics:", error);
  }
}

// Mark order as ready
async function markOrderReady(orderId) {
  if (!confirm("Mark this order as ready?")) return;

  const btn = event.target.closest("button");
  const originalHTML = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  btn.disabled = true;

  try {
    const response = await fetch("admin/update_order_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ order_id: orderId, status: "ready" }),
    });

    const data = await response.json();
    if (data.success) {
      btn.innerHTML = '<i class="fas fa-check"></i> Done!';
      btn.classList.remove("btn-success");
      btn.classList.add("btn-primary");

      setTimeout(() => {
        loadKitchenOrders();
        if (currentSection === "cashier") {
          loadCashierOrders();
        }
      }, 500);
    } else {
      alert("Error: " + data.message);
      btn.innerHTML = originalHTML;
      btn.disabled = false;
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Error updating order status");
    btn.innerHTML = originalHTML;
    btn.disabled = false;
  }
}

// Complete order
async function completeOrder(orderId) {
  if (!confirm("Complete this order?")) return;

  const btn = event.target.closest("button");
  const originalHTML = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  btn.disabled = true;

  try {
    const response = await fetch("admin/update_order_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ order_id: orderId, status: "completed" }),
    });

    const data = await response.json();
    if (data.success) {
      btn.innerHTML = '<i class="fas fa-check"></i> Completed!';

      setTimeout(() => {
        loadCashierOrders();
        if (currentSection === "statistics") {
          loadStatistics();
        }
      }, 500);
    } else {
      alert("Error: " + data.message);
      btn.innerHTML = originalHTML;
      btn.disabled = false;
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Error completing order");
    btn.innerHTML = originalHTML;
    btn.disabled = false;
  }
}

// Show add product modal
function showAddProductModal() {
  document.getElementById("addProductForm").reset();
  document.getElementById("modalTitle").textContent = "Add New Product";
  document.getElementById("productIdField").value = "";
  document.getElementById("currentImagePreview").style.display = "none";
  document.getElementById("imageInput").required = true;
  document.getElementById("submitProductBtn").textContent = "Add Product";

  const modal = new bootstrap.Modal(document.getElementById("addProductModal"));
  modal.show();
}

// Edit product
async function editProduct(productId) {
  try {
    const response = await fetch(`admin/get_product.php?id=${productId}`);
    const data = await response.json();

    if (data.success) {
      const product = data.data;

      document.getElementById("modalTitle").textContent = "Edit Product";
      document.getElementById("productIdField").value = product.id;
      document.getElementById("productName").value = product.name;
      document.getElementById("productCategory").value = product.category;
      document.getElementById("productPrice").value = product.price;
      document.getElementById("productStock").value = product.stock_quantity;

      document.getElementById("currentImage").src = product.image_path;
      document.getElementById("currentImagePreview").style.display = "block";
      document.getElementById("imageInput").required = false;
      document.getElementById("submitProductBtn").textContent =
        "Update Product";

      const modal = new bootstrap.Modal(
        document.getElementById("addProductModal")
      );
      modal.show();
    } else {
      alert("Error loading product: " + data.message);
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Error loading product");
  }
}

// Add or edit product
async function addProduct() {
  const form = document.getElementById("addProductForm");
  const formData = new FormData(form);
  const productId = document.getElementById("productIdField").value;

  const submitBtn = document.getElementById("submitProductBtn");
  submitBtn.disabled = true;
  const originalText = submitBtn.textContent;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

  const url = productId ? "admin/edit_product.php" : "admin/add_product.php";

  if (productId) {
    formData.append("product_id", productId);
  }

  try {
    const response = await fetch(url, {
      method: "POST",
      body: formData,
    });

    const data = await response.json();
    if (data.success) {
      alert(
        productId
          ? "Product updated successfully!"
          : "Product added successfully!"
      );
      bootstrap.Modal.getInstance(
        document.getElementById("addProductModal")
      ).hide();
      form.reset();
      loadProducts();
    } else {
      alert("Error: " + data.message);
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Error saving product");
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalText;
  }
}

// Delete product
async function deleteProduct(productId) {
  if (!confirm("Are you sure you want to delete this product?")) return;

  try {
    const response = await fetch("admin/delete_product.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ product_id: productId }),
    });

    const data = await response.json();
    if (data.success) {
      alert("Product deleted successfully!");
      loadProducts();
    } else {
      alert("Error: " + data.message);
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Error deleting product");
  }
}

// Show update stock modal
function showUpdateStockModal(productId, productName, currentStock) {
  document.getElementById("stockProductId").value = productId;
  document.getElementById("stockProductName").value = productName;
  document.getElementById("stockCurrentQty").value = currentStock;
  document.getElementById("stockNewQty").value = currentStock;

  const modal = new bootstrap.Modal(
    document.getElementById("updateStockModal")
  );
  modal.show();
}

// Update stock
async function updateStock() {
  const productId = document.getElementById("stockProductId").value;
  const newQty = document.getElementById("stockNewQty").value;

  try {
    const response = await fetch("admin/update_stock.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ product_id: productId, quantity: newQty }),
    });

    const data = await response.json();
    if (data.success) {
      alert("Stock updated successfully!");
      bootstrap.Modal.getInstance(
        document.getElementById("updateStockModal")
      ).hide();
      loadInventory();
    } else {
      alert("Error: " + data.message);
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Error updating stock");
  }
}

// Show add VIP modal
function showAddVIPModal() {
  document.getElementById("vipIdInput").value = "";
  const modal = new bootstrap.Modal(document.getElementById("addVIPModal"));
  modal.show();
}

// Add VIP
async function addVIP() {
  const vipId = document.getElementById("vipIdInput").value.trim();

  if (vipId.length !== 10) {
    alert("VIP ID must be exactly 10 digits");
    return;
  }

  try {
    const response = await fetch("admin/add_vip.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ vip_id: vipId }),
    });

    const data = await response.json();
    if (data.success) {
      alert("VIP customer added successfully!");
      bootstrap.Modal.getInstance(
        document.getElementById("addVIPModal")
      ).hide();
      document.getElementById("vipIdInput").value = "";
      loadVIPCustomers();
    } else {
      alert("Error: " + data.message);
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Error adding VIP");
  }
}

// Delete VIP
async function deleteVIP(vipId) {
  if (!confirm("Remove this VIP customer?")) return;

  try {
    const response = await fetch("admin/delete_vip.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ vip_id: vipId }),
    });

    const data = await response.json();
    if (data.success) {
      alert("VIP customer removed!");
      loadVIPCustomers();
    } else {
      alert("Error: " + data.message);
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Error removing VIP");
  }
}

// Helper functions
function formatTime(datetime) {
  const date = new Date(datetime);
  return date.toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatDate(datetime) {
  const date = new Date(datetime);
  return date.toLocaleDateString("en-US");
}

function formatDateTime(datetime) {
  const date = new Date(datetime);
  return date.toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function getStatusColor(status) {
  const colors = {
    pending: "secondary",
    preparing: "warning",
    ready: "success",
    completed: "primary",
  };
  return colors[status] || "secondary";
}

// Page visibility handling
document.addEventListener("visibilitychange", () => {
  if (document.hidden) {
    stopAutoRefresh();
  } else {
    startAutoRefresh();
    loadSectionData(currentSection);
  }
});

// Initial load
document.addEventListener("DOMContentLoaded", () => {
  loadKitchenOrders();
  startAutoRefresh();
});

// Clean up on page unload
window.addEventListener("beforeunload", () => {
  stopAutoRefresh();
});

