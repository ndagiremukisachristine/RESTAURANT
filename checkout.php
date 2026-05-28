<?php
session_start();
$xml = simplexml_load_file('restaurants.xml');

if(empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}  

// Calculate total
$cart_total = 0;
$cart_items = array();
foreach($_SESSION['cart'] as $item_id => $quantity) {
    foreach($xml->menu->item as $item) {
        if($item->id == $item_id) {
            $item_total = $item->price * $quantity;
            $cart_total += $item_total;
            $cart_items[] = [
                'name' => (string)$item->name,
                'quantity' => $quantity,
                'price' => (float)$item->price,
                'total' => $item_total
            ];
        }
    }
}

// Process order submission
$order_placed = false;
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $_POST['customer_name'];
    $phone = $_POST['phone'];
    $special_instructions = $_POST['instructions'];
    
    // Create new order in XML
    $orders = $xml->orders;
    $new_id = 1003;
    foreach($orders->order as $order) {
        if((int)$order['id'] >= $new_id) {
            $new_id = (int)$order['id'] + 1;
        }
    }
    
    $items_string = implode(',', array_keys($_SESSION['cart']));
    
    $new_order = $orders->addChild('order');
    $new_order->addAttribute('id', $new_id);
    $new_order->addChild('customer_name', $customer_name);
    $new_order->addChild('phone', $phone);
    $new_order->addChild('items', $items_string);
    $new_order->addChild('total', $cart_total);
    $new_order->addChild('status', 'received');
    $new_order->addChild('order_time', date('Y-m-d H:i:s'));
    $new_order->addChild('instructions', $special_instructions);
    
    $xml->asXML('restaurants.xml');
    
    // Clear cart
    $_SESSION['cart'] = array();
    $order_placed = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Restaurant</title>
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
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .total {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .back-btn {
            background: #007bff;
            margin-top: 10px;
            text-align: center;
            display: inline-block;
            text-decoration: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if($order_placed): ?>
            <div class="success">
                <h2>✅ Order Placed Successfully!</h2>
                <p>Thank you for your order. We'll prepare it right away!</p>
                <p>Order #<?php echo $new_id; ?> - Total: $<?php echo number_format($cart_total, 2); ?></p>
                <a href="index.php" class="back-btn">Back to Menu</a>
            </div>
        <?php else: ?>
            <h1>🍽️ Checkout</h1>
            
            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php foreach($cart_items as $item): ?>
                    <div class="order-item">
                        <span><?php echo $item['name']; ?> x<?php echo $item['quantity']; ?></span>
                        <span>$<?php echo number_format($item['total'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="total">
                    Total: $<?php echo number_format($cart_total, 2); ?>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Your Name *</label>
                    <input type="text" name="customer_name" required>
                </div>
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Special Instructions (optional)</label>
                    <textarea name="instructions" rows="3" placeholder="Any allergies, preferences, or special requests?"></textarea>
                </div>
                <button type="submit">Place Order</button>
            </form>
            <br>
            <a href="index.php" class="back-btn" style="display: inline-block;">← Back to Menu</a>
        <?php endif; ?>
    </div>
</body>
</html>
