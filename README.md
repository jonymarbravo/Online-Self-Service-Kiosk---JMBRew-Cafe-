# ☕ JMBrew Café - Self-Service Kiosk System

**"Refined Coffee. Real Comfort."**

A modern self-service kiosk system for café management with real-time order tracking, VIP customer support, and comprehensive admin dashboard.

---

## 📋 Overview

JMBrew Café is a complete point-of-sale and order management solution designed for cafés and food service establishments. Built with PHP, MySQL, and JavaScript, it provides seamless ordering experience for customers and powerful management tools for staff.

---

## ✨ Key Features

### Customer Kiosk
- Priority queue system with automatic number assignment
- Browse products by category (Coffee, Juices, Sandwiches)
- Smart cart management with quantity controls
- Choose Dine In or Take Out per item
- VIP customer integration with 20% automatic discount
- Real-time stock availability display
- Live order status tracking

### Kitchen Management
- Real-time order display with auto-refresh
- Order status workflow: Pending → Preparing → Ready → Completed
- Priority-based queue ordering
- VIP order indicators
- Complete order details with item quantities

### Cashier Module
- Process ready orders for payment
- Multiple payment methods (Cash, GCash)
- Automatic discount calculations for VIP
- Complete order summaries and receipts

### Admin Dashboard
- **Product Management**: Add, edit, delete products with image uploads
- **Inventory Control**: Real-time stock tracking and updates
- **VIP Management**: Register customers with 10-digit VIP IDs
- **Business Analytics**: Today's sales, order counts, pending orders
- **Kitchen Orders**: Monitor and update order status
- **Auto-refresh**: Dashboard updates every 2-3 seconds
- **Priority Reset**: Automatic counter reset every 12 hours

---

## 🛠️ Tech Stack

**Frontend:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, Font Awesome 6.5  
**Backend:** PHP 8.2, MySQL/MariaDB, MySQLi  
**Tools:** XAMPP, phpMyAdmin, Git

---

## 📦 Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.4+
- Apache web server
- XAMPP (recommended for local development)

### Setup Steps

1. Clone the repository and move to XAMPP htdocs folder
2. Start Apache and MySQL in XAMPP
3. Create database named `jmbrew_cafe` in phpMyAdmin
4. Import the provided SQL file to create tables
5. Configure database credentials in `config.php`
6. Access kiosk at `http://localhost/jmbrew-cafe/`
7. Access admin at `http://localhost/jmbrew-cafe/admin_login.php`
8. Default admin password: `admin123`

---

## 📖 Usage Guide

### For Customers
Click "Order Now" to receive priority number, browse products by category, add items to cart, select Dine In or Take Out, apply VIP discount if applicable, checkout with preferred payment method, and track order status until ready for pickup.

### For Kitchen Staff
Login to admin dashboard, view incoming orders in Kitchen Orders section, mark orders as "Preparing" when starting work, mark as "Ready" when complete. Orders are sorted by priority number with VIP orders clearly indicated.

### For Cashier
Navigate to Cashier section to view all ready orders, verify payment amounts including VIP discounts, process payments, and mark orders as "Completed" to finalize transactions.

### For Administrators
Login to admin dashboard to manage products (add/edit/delete with images), monitor inventory levels, update stock quantities, register VIP customers with 10-digit IDs, view real-time business statistics including today's sales and order counts, and manually reset priority counter if needed.

---

## 🗄️ Database Structure

The system uses six main tables: **admin** for authentication, **orders** for customer orders, **order_items** for individual items in each order, **products** for the catalog, **vip_customers** for VIP data, and **priority_counter** for tracking priority numbers. Orders link to order_items and products, while VIP customers link to orders for discount application.

---

## 🔌 API Endpoints

### Customer APIs
Get priority number, fetch all products, verify VIP status, submit new orders, and check order status.

### Admin APIs  
Fetch kitchen orders, fetch ready orders, update order status, manage products (add/edit/delete), update inventory stock, add VIP customers, retrieve business statistics, and reset priority counter.

---

## 🚀 Deployment

For production deployment, choose a hosting provider (shared hosting like Hostinger or VPS like DigitalOcean), upload all files via FTP or control panel, create and import database on server, update `config.php` with production credentials, set proper file permissions (755 for folders, 644 for files), change default admin password, enable HTTPS, and disable error display for security.

**Recommended Hosting:** Hostinger ($1.99/month), Namecheap ($1.58/month), DigitalOcean ($4/month), or Vultr ($2.50/month).

---

## ⚠️ Important Notes

**Security:** Change the default admin password immediately after installation. Update database credentials in config.php for production. Set proper file permissions and enable HTTPS.

**Compatibility:** Works best on modern browsers (Chrome, Firefox, Safari, Edge). Optimized for desktop and tablet viewing. Some admin features may have limited functionality on mobile devices.

**Limitations:** May experience caching issues on free hosting platforms. Real-time updates work best on dedicated hosting. Currently supports English language only.

---

## 📝 Future Enhancements

Planned features include email/SMS notifications, online payment gateway integration (GCash, PayMaya), customer order history, loyalty points system, multi-language support, detailed sales reports, employee management, table reservation system, mobile app version, and receipt printer integration.

---

## 🤝 Contributing

Contributions are welcome! Fork the repository, create a feature branch, make your changes, test thoroughly, and submit a pull request. Please follow coding standards and update documentation for new features.


## 👨‍💻 Author

*Barrete, Jony Mar S.  
GitHub: @jonymarbravo 
Email: jonymarbarrete88@gmail.com

---

## 📞 Support

For issues or questions, check the documentation, search existing GitHub issues, or create a new issue with your environment details, steps to reproduce, and expected behavior.

---

**Made with ☕ and ❤️**
