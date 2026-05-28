<?php
$xml = simplexml_load_file('restaurants.xml') or die("Error: Cannot load menu");

// Handle cart actions
session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
} 

// Add to cart
if (isset($_GET['add_to_cart'])) {
    $item_id = $_GET['add_to_cart'];
    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]++;
    } else {
        $_SESSION['cart'][$item_id] = 1;
    }
    header("Location: index.php");
    exit();
}

// Remove from cart
if (isset($_GET['remove'])) {
    $item_id = $_GET['remove'];
    unset($_SESSION['cart'][$item_id]);
    header("Location: index.php");
    exit();
}

// Clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = array();
    header("Location: index.php");
    exit();
}

// Get category filter
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? strtolower($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍽️ Restaurant Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .header {
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            font-size: 2.5em;
        }
        .container {
            display: flex;
            max-width: 1400px;
            margin: 20px auto;
            gap: 20px;
            padding: 0 20px;
        }
        /* Menu Section */
        .menu-section {
            flex: 3;
        }
        /* Cart Section */
        .cart-section {
            flex: 1;
            background: white;
            border-radius: 15px;
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .filters {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .filters input, .filters select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .filters input {
            flex: 2;
        }
        .filters select {
            flex: 1;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .menu-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .category-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff6b6b;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .item-name {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            padding-right: 80px;
        }
        .item-price {
            font-size: 1.5em;
            color: #28a745;
            font-weight: bold;
            margin: 10px 0;
        }
        .item-description {
            color: #666;
            font-size: 0.9em;
            margin: 10px 0;
            line-height: 1.4;
        }
        .item-meta {
            display: flex;
            gap: 10px;
            margin: 10px 0;
            font-size: 0.85em;
        }
        .prep-time {
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 8px;
            border-radius: 5px;
        }
        .spice-level {
            background: #fff3e0;
            color: #f57c00;
            padding: 3px 8px;
            border-radius: 5px;
        }
        .availability {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.85em;
            margin: 10px 0;
        }
        .available {
            background: #d4edda;
            color: #155724;
        }
        .unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        .add-btn {
            width: 100%;
            padding: 10px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .add-btn:hover {
            background: #218838;
        }
        .add-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        /* Cart Styles */
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item-info {
            flex: 1;
        }
        .cart-item-name {
            font-weight: bold;
        }
        .cart-item-price {
            color: #28a745;
            font-size: 0.9em;
        }
        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .quantity-btn {
            background: #007bff;
            color: white;
            border: none;
            width: 25px;
            height: 25px;
            border-radius: 5px;
            cursor: pointer;
        }
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8em;
        }
        .cart-total {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #333;
            font-size: 1.2em;
            font-weight: bold;
        }
        .checkout-btn {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            margin-top: 15px;
            cursor: pointer;
            font-size: 1em;
        }
        .clear-btn {
            background: #dc3545;
            margin-top: 10px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-number {
            font-size: 1.8em;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .cart-section {
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🍽️ Gourmet Restaurant</h1>
        <p>Delicious food made with love ❤️</p>
    </div>
    
    <div class="container">
        <div class="menu-section">
            <!-- Stats -->
            <div class="stats">
                <?php
                $total_items = count($xml->menu->item);
                $available_items = 0;
                foreach($xml->menu->item as $item) {
                    if($item->available == 'yes') $available_items++;
                }
                ?>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $total_items; ?></div>
                    <div>Menu Items</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $available_items; ?></div>
                    <div>Available Now</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($_SESSION['cart']); ?></div>
                    <div>Cart Items</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <form method="GET" style="display: flex; gap: 15px; width: 100%;">
                    <input type="text" name="search" placeholder="Search menu items..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <select name="category" onchange="this.form.submit()">
                        <option value="all">All Categories</option>
                        <?php
                        $categories = array();
                        foreach($xml->menu->item as $item) {
                            $categories[(string)$item->category] = (string)$item->category;
                        }
                        $categories = array_unique($categories);
                        foreach($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Filter</button>
                    <a href="index.php"><button type="button">Clear</button></a>
                </form>
            </div>
            
            <!-- Menu Grid -->
            <div class="menu-grid">
                <?php
                foreach($xml->menu->item as $item) {
                    $item_name = strtolower($item->name);
                    $item_desc = strtolower($item->description);
                    $matches_search = ($search == '' || strpos($item_name, $search) !== false || 
                                      strpos($item_desc, $search) !== false);
                    $matches_category = ($category == 'all' || $item->category == $category);
                    
                    if($matches_search && $matches_category) {
                        $available_class = ($item->available == 'yes') ? 'available' : 'unavailable';
                        $available_text = ($item->available == 'yes') ? '✓ Available' : '✗ Currently Unavailable';
                        $spice_icon = '';
                        if($item->spice_level == 'mild') $spice_icon = '🌶️ Mild';
                        elseif($item->spice_level == 'medium') $spice_icon = '🌶️🌶️ Medium';
                        elseif($item->spice_level == 'hot') $spice_icon = '🌶️🌶️🌶️ Hot';
                ?>
                        <div class="menu-card">
                            <div class="category-badge"><?php echo $item->category; ?></div>
                            <div class="item-name"><?php echo $item->name; ?></div>
                            <div class="item-price">$<?php echo $item->price; ?></div>
                            <div class="item-description"><?php echo $item->description; ?></div>
                            <div class="item-meta">
                                <span class="prep-time">⏱️ <?php echo $item->prep_time; ?> min</span>
                                <span class="spice-level"><?php echo $spice_icon; ?></span>
                            </div>
                            <div class="availability <?php echo $available_class; ?>"><?php echo $available_text; ?></div>
                            <?php if($item->available == 'yes'): ?>
                                <a href="?add_to_cart=<?php echo $item->id; ?>">
                                    <button class="add-btn">🛒 Add to Cart - $<?php echo $item->price; ?></button>
                                </a>
                            <?php else: ?>
                                <button class="add-btn" disabled>Sold Out</button>
                            <?php endif; ?>
                        </div>
                <?php
                    }
                }
                ?>
            </div>
        </div>
        
        <!-- Shopping Cart -->
        <div class="cart-section">
            <h2>🛒 Your Order</h2>
            <?php if(empty($_SESSION['cart'])): ?>
                <p style="text-align: center; color: #999; padding: 20px;">Your cart is empty</p>
            <?php else: ?>
                <?php 
                $cart_total = 0;
                foreach($_SESSION['cart'] as $item_id => $quantity):
                    foreach($xml->menu->item as $item):
                        if($item->id == $item_id):
                            $item_total = $item->price * $quantity;
                            $cart_total += $item_total;
                ?>
                            <div class="cart-item">
                                <div class="cart-item-info">
                                    <div class="cart-item-name"><?php echo $item->name; ?></div>
                                    <div class="cart-item-price">$<?php echo $item->price; ?> each</div>
                                </div>
                                <div class="cart-item-quantity">
                                    <span>x<?php echo $quantity; ?></span>
                                    <a href="?remove=<?php echo $item_id; ?>" class="remove-btn">Remove</a>
                                </div>
                            </div>
                <?php 
                        endif;
                    endforeach;
                endforeach; 
                ?>
                <div class="cart-total">
                    Total: $<?php echo number_format($cart_total, 2); ?>
                </div>
                <a href="checkout.php">
                    <button class="checkout-btn">Proceed to Checkout</button>
                </a>
                <a href="?clear_cart=1">
                    <button class="checkout-btn clear-btn">Clear Cart</button>
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
