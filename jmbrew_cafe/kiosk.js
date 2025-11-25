// kiosk.js

let currentCategory = "Coffee";
let cart = [];
let priorityNumber = null;
let isVIP = false;
let vipID = null;
let products = [];
let currentOrderId = null;
let statusCheckInterval = null;

// Check if there's an active session
window.addEventListener("load", () => {
  const savedPriority = sessionStorage.getItem("priority_number");
  const savedOrderId = sessionStorage.getItem("order_id");
  const savedOrderStatus = sessionStorage.getItem("order_status");

  if (savedPriority && savedOrderStatus === "placed") {
    priorityNumber = parseInt(savedPriority);
    currentOrderId = savedOrderId ? parseInt(savedOrderId) : null;

    document.getElementById("welcomeScreen").style.display = "none";
    document.getElementById("kioskDashboard").style.display = "none";
    document.getElementById("statusScreen").style.display = "flex";

    const orderDate = sessionStorage.getItem("order_date");
    document.getElementById("statusDetails").innerHTML = `
            Priority Number: <strong>#${String(priorityNumber).padStart(
              2,
              "0"
            )}</strong><br>
            ${isVIP && vipID ? `VIP ID: <strong>${vipID}</strong><br>` : ""}
            Order Date: <strong>${orderDate || "Just now"}</strong>
        `;

    if (currentOrderId) {
      checkOrderStatus(currentOrderId);
    }
  } else if (savedPriority && !savedOrderStatus) {
    priorityNumber = parseInt(savedPriority);
    document.getElementById("priorityNumber").textContent = String(
      priorityNumber
    ).padStart(2, "0");

    document.getElementById("welcomeScreen").style.display = "none";
    document.getElementById("kioskDashboard").style.display = "block";

    loadProducts();
  }
});

async function startOrder() {
  try {
    const response = await fetch("api/get_priority_number.php");
    const data = await response.json();

    if (data.success) {
      priorityNumber = data.priority_number;
      sessionStorage.setItem("priority_number", priorityNumber);
      sessionStorage.removeItem("order_status");
      sessionStorage.removeItem("order_id");

      document.getElementById("priorityNumber").textContent = String(
        priorityNumber
      ).padStart(2, "0");

      document.getElementById("welcomeScreen").style.display = "none";
      document.getElementById("kioskDashboard").style.display = "block";

      loadProducts();
    } else {
      alert("Error starting order. Please try again.");
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Connection error. Please try again.");
  }
}

async function loadProducts() {
  try {
    const response = await fetch("api/get_products.php");
    const data = await response.json();

    if (data.success) {
      products = data.products;
      displayProducts();
    }
  } catch (error) {
    console.error("Error loading products:", error);
  }
}

function displayProducts() {
  const grid = document.getElementById("productsGrid");
  const filtered = products.filter((p) => p.category === currentCategory);

  if (filtered.length === 0) {
    grid.innerHTML =
      '<div class="col-12 text-center text-white"><h4>No products available in this category yet.</h4></div>';
    return;
  }

  grid.innerHTML = filtered
    .map((product) => {
      const soldOut =
        product.stock_quantity <= 0 ||
        product.is_available === false ||
        product.is_available === 0;

      return `
            <div class="product-card ${soldOut ? "sold-out" : ""}" onclick="${
        !soldOut ? `addToCart(${product.id})` : ""
      }">
                <img src="${product.image_path}" alt="${
        product.name
      }" class="product-image">
                <div class="product-info">
                    <div class="product-name">${product.name}</div>
                    <div class="product-price">₱${parseFloat(
                      product.price
                    ).toFixed(2)}</div>
                    ${
                      soldOut
                        ? '<div class="text-danger mt-1"><small><strong>OUT OF STOCK</strong></small></div>'
                        : ""
                    }
                </div>
            </div>
        `;
    })
    .join("");
}

function filterCategory(category) {
  currentCategory = category;

  document.querySelectorAll(".category-btn").forEach((btn) => {
    btn.classList.remove("active");
  });
  event.target.classList.add("active");

  displayProducts();
}

function addToCart(productId) {
  const product = products.find((p) => p.id === productId);
  if (!product) return;

  if (product.stock_quantity <= 0 || !product.is_available) {
    alert("Sorry, this product is currently out of stock!");
    return;
  }

  const existingItem = cart.find((item) => item.productId === productId);

  if (existingItem) {
    if (existingItem.quantity >= product.stock_quantity) {
      alert(
        `Only ${product.stock_quantity} items available for ${product.name}!`
      );
      return;
    }
    if (existingItem.quantity >= 10) {
      alert("Maximum 10 items per product!");
      return;
    }
    existingItem.quantity++;
  } else {
    cart.push({
      productId: product.id,
      name: product.name,
      price: parseFloat(product.price),
      quantity: 1,
      orderType: "Dine In",
      maxStock: product.stock_quantity,
    });
  }

  updateCart();
}

function removeFromCart(productId) {
  cart = cart.filter((item) => item.productId !== productId);
  updateCart();
}

function updateQuantity(productId, change) {
  const item = cart.find((item) => item.productId === productId);
  if (!item) return;

  const newQuantity = item.quantity + change;

  if (newQuantity > item.maxStock) {
    alert(`Only ${item.maxStock} items available for ${item.name}!`);
    return;
  }

  item.quantity = newQuantity;

  if (item.quantity <= 0) {
    removeFromCart(productId);
  } else if (item.quantity > 10) {
    alert("Maximum 10 items per product!");
    item.quantity = 10;
  }

  updateCart();
}

function updateOrderType(productId, type) {
  const item = cart.find((item) => item.productId === productId);
  if (item) {
    item.orderType = type;
    updateCart();
  }
}

function updateCart() {
  const cartCount = document.getElementById("cartCount");
  const orderItems = document.getElementById("orderItems");

  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
  cartCount.textContent = totalItems;

  if (cart.length === 0) {
    orderItems.innerHTML = '<p class="text-muted">Your cart is empty</p>';
    updateTotal();
    return;
  }

  orderItems.innerHTML = cart
    .map(
      (item) => `
        <div class="order-item">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <strong>${item.name}</strong>
                <button class="btn btn-sm btn-danger" onclick="removeFromCart(${
                  item.productId
                })">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="text-muted mb-2">₱${item.price.toFixed(2)} each</div>
            
            <div class="quantity-controls">
                <button class="quantity-btn btn-minus" onclick="updateQuantity(${
                  item.productId
                }, -1)">-</button>
                <span class="mx-3"><strong>${item.quantity}</strong></span>
                <button class="quantity-btn btn-plus" onclick="updateQuantity(${
                  item.productId
                }, 1)">+</button>
            </div>
            
            <div class="mt-2">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="orderType${
                      item.productId
                    }" 
                           id="dineIn${item.productId}" value="Dine In" 
                           ${item.orderType === "Dine In" ? "checked" : ""}
                           onchange="updateOrderType(${
                             item.productId
                           }, 'Dine In')">
                    <label class="form-check-label" for="dineIn${
                      item.productId
                    }">Dine In</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="orderType${
                      item.productId
                    }" 
                           id="takeOut${item.productId}" value="Take Out"
                           ${item.orderType === "Take Out" ? "checked" : ""}
                           onchange="updateOrderType(${
                             item.productId
                           }, 'Take Out')">
                    <label class="form-check-label" for="takeOut${
                      item.productId
                    }">Take Out</label>
                </div>
            </div>
            
            <div class="mt-2 text-end">
                <strong>Subtotal: ₱${(item.price * item.quantity).toFixed(
                  2
                )}</strong>
            </div>
        </div>
    `
    )
    .join("");

  updateTotal();
}

function toggleVIPInput() {
  const checkbox = document.getElementById("vipCheckbox");
  const input = document.getElementById("vipInput");
  const btn = document.getElementById("verifyVipBtn");

  input.disabled = !checkbox.checked;
  btn.disabled = !checkbox.checked;

  if (!checkbox.checked) {
    input.value = "";
    isVIP = false;
    vipID = null;
    document.getElementById("vipMessage").innerHTML = "";
    updateTotal();
  }
}

async function verifyVIP() {
  const vipInput = document.getElementById("vipInput").value.trim();
  const messageDiv = document.getElementById("vipMessage");

  if (vipInput.length !== 10) {
    messageDiv.innerHTML =
      '<small class="text-danger">Please enter a 10-digit VIP ID</small>';
    return;
  }

  try {
    const response = await fetch("api/verify_vip.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ vip_id: vipInput }),
    });

    const data = await response.json();

    if (data.success) {
      isVIP = true;
      vipID = vipInput;
      messageDiv.innerHTML =
        '<small class="text-success"><i class="fas fa-check-circle"></i> VIP verified! 20% discount applied</small>';
      updateTotal();
    } else {
      isVIP = false;
      vipID = null;
      messageDiv.innerHTML =
        '<small class="text-danger"><i class="fas fa-times-circle"></i> Invalid VIP ID</small>';
      updateTotal();
    }
  } catch (error) {
    console.error("Error:", error);
    messageDiv.innerHTML =
      '<small class="text-danger">Verification error</small>';
  }
}

function updateTotal() {
  const subtotal = cart.reduce(
    (sum, item) => sum + item.price * item.quantity,
    0
  );
  const discount = isVIP ? subtotal * 0.2 : 0;
  const total = subtotal - discount;

  document.getElementById("subtotal").textContent = `₱${subtotal.toFixed(2)}`;
  document.getElementById("discount").textContent = `₱${discount.toFixed(2)}`;
  document.getElementById("totalPrice").textContent = `₱${total.toFixed(2)}`;

  if (isVIP && discount > 0) {
    document.getElementById("discountRow").style.display = "block";
  } else {
    document.getElementById("discountRow").style.display = "none";
  }
}

function toggleOrderSummary() {
  document.getElementById("orderSummary").classList.toggle("show");
}

function checkout() {
  if (cart.length === 0) {
    alert("Your cart is empty!");
    return;
  }

  const modal = new bootstrap.Modal(document.getElementById("paymentModal"));
  modal.show();
}

async function confirmPayment() {
  const paymentMethod = document.querySelector(
    'input[name="payment"]:checked'
  ).value;

  const orderData = {
    priority_number: priorityNumber,
    items: cart,
    payment_method: paymentMethod,
    is_vip: isVIP,
    vip_id: vipID,
  };

  try {
    const response = await fetch("api/place_order.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(orderData),
    });

    const data = await response.json();

    if (data.success) {
      currentOrderId = data.data.order_id;

      sessionStorage.setItem("order_status", "placed");
      sessionStorage.setItem("order_id", currentOrderId);

      const now = new Date();
      const orderDate = now.toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
      });
      sessionStorage.setItem("order_date", orderDate);

      bootstrap.Modal.getInstance(
        document.getElementById("paymentModal")
      ).hide();

      document.getElementById("kioskDashboard").style.display = "none";
      document.getElementById("statusScreen").style.display = "flex";

      document.getElementById("statusIcon").textContent = "⏳";
      document.getElementById("statusMessage").textContent =
        "Your order is being prepared...";
      document.getElementById("statusDetails").innerHTML = `
                Priority Number: <strong>#${String(priorityNumber).padStart(
                  2,
                  "0"
                )}</strong><br>
                ${isVIP && vipID ? `VIP ID: <strong>${vipID}</strong><br>` : ""}
                Order Date: <strong>${orderDate}</strong><br>
                Payment: <strong>${paymentMethod}</strong>
            `;

      cart = [];

      checkOrderStatus(currentOrderId);
    } else {
      alert("Error placing order: " + data.message);
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Connection error. Please try again.");
  }
}

async function checkOrderStatus(orderId) {
  if (statusCheckInterval) {
    clearInterval(statusCheckInterval);
  }

  const checkStatus = async () => {
    try {
      const response = await fetch(
        `api/get_order_status.php?order_id=${orderId}`
      );
      const data = await response.json();

      if (data.success) {
        if (data.data.status === "ready") {
          clearInterval(statusCheckInterval);

          sessionStorage.setItem("order_ready", "true");

          document.getElementById("statusIcon").textContent = "✅";
          document.getElementById("statusMessage").textContent =
            "Your order is ready!";

          const orderDate = sessionStorage.getItem("order_date");
          const detailsDiv = document.getElementById("statusDetails");
          detailsDiv.innerHTML = `
                        Priority Number: <strong>#${String(
                          data.data.priority_number
                        ).padStart(2, "0")}</strong><br>
                        ${
                          data.data.is_vip && data.data.vip_id
                            ? `VIP ID: <strong>${data.data.vip_id}</strong><br>`
                            : ""
                        }
                        Order Date: <strong>${
                          orderDate || "Just now"
                        }</strong><br><br>
                        <p class="text-success" style="font-size: 1.3rem; font-weight: 700;">Please proceed to the cashier to collect your order.</p>
                        <button class="btn btn-lg btn-primary mt-4" onclick="placeNewOrder()" style="padding: 15px 40px; font-size: 1.2rem; border-radius: 10px;">
                            <i class="fas fa-shopping-cart"></i> Place New Order
                        </button>
                    `;
        }
      }
    } catch (error) {
      console.error("Error checking status:", error);
    }
  };

  checkStatus();
  statusCheckInterval = setInterval(checkStatus, 3000);
}

function placeNewOrder() {
  sessionStorage.clear();

  if (statusCheckInterval) {
    clearInterval(statusCheckInterval);
  }

  priorityNumber = null;
  currentOrderId = null;
  cart = [];
  isVIP = false;
  vipID = null;

  document.getElementById("statusScreen").style.display = "none";
  document.getElementById("welcomeScreen").style.display = "flex";
  document.getElementById("kioskDashboard").style.display = "none";
}

document.addEventListener("DOMContentLoaded", () => {
  // Session check is done in window.load event
});

