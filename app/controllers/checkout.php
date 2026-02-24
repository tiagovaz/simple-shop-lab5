<?php
// app/controllers/checkout.php
// Functions for processing checkout and displaying order history

/**
 * Process the checkout - creates an order from the current cart
 */
function process_checkout()
{
  // Check if user is logged in
  if (!User::isLoggedIn()) {
    header('Location: index.php?action=login');
    exit;
  }

  // Check if cart has items
  if (empty($_SESSION['cart'])) {
    $_SESSION['error_message'] = 'Your cart is empty. Add some items before checking out.';
    header('Location: index.php?action=cart');
    exit;
  }

  $db = db();
  $user_id = $_SESSION['user_id'];

  // Calculate total and prepare order items
  $total = 0;
  $order_items = [];

  foreach ($_SESSION['cart'] as $product_id => $quantity) {
    // Get current product price from database
    $stmt = $db->prepare('SELECT id, name, price FROM products WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
      $line_total = (float)$product['price'] * (int)$quantity;
      $total += $line_total;

      $order_items[] = [
        'product_id' => (int)$product['id'],
        'quantity' => (int)$quantity,
        'price' => (float)$product['price']
      ];
    }
  }

  // Start a transaction for data integrity
  try {
    $db->beginTransaction();

    // Insert the order
    $stmt = $db->prepare('INSERT INTO orders (user_id, total, order_date, status) VALUES (?, ?, NOW(), ?)');
    $stmt->execute([$user_id, $total, 'completed']);

    // Get the order ID that was just created
    $order_id = $db->lastInsertId();

    // Insert each order item
    $stmt = $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
    foreach ($order_items as $item) {
      $stmt->execute([
        $order_id,
        $item['product_id'],
        $item['quantity'],
        $item['price']
      ]);
    }

    // Commit the transaction
    $db->commit();

    // Clear the cart
    $_SESSION['cart'] = [];

    // Store order info for success page
    $_SESSION['last_order_id'] = $order_id;
    $_SESSION['last_order_total'] = $total;

    // Redirect to success page
    header('Location: index.php?action=checkout_success');
    exit;

  } catch (Exception $e) {
    // Rollback on error
    $db->rollBack();
    $_SESSION['error_message'] = 'There was an error processing your order. Please try again.';
    header('Location: index.php?action=cart');
    exit;
  }
}

/**
 * Display the checkout success page
 */
function show_checkout_success()
{
  // Check if user is logged in
  if (!User::isLoggedIn()) {
    header('Location: index.php?action=login');
    exit;
  }

  // Get order info from session
  $order_id = $_SESSION['last_order_id'] ?? null;
  $total = $_SESSION['last_order_total'] ?? 0;

  // Clear the order info from session
  unset($_SESSION['last_order_id']);
  unset($_SESSION['last_order_total']);

  require __DIR__ . '/../views/checkout_success.php';
}

/**
 * Display the order history for the logged-in user
 */
function show_order_history()
{
  // Check if user is logged in
  if (!User::isLoggedIn()) {
    header('Location: index.php?action=login');
    exit;
  }

  $db = db();
  $user_id = $_SESSION['user_id'];

  // Get all orders for this user (newest first)
  $stmt = $db->prepare('
    SELECT id, total, order_date, status
    FROM orders
    WHERE user_id = ?
    ORDER BY order_date DESC
  ');
  $stmt->execute([$user_id]);
  $orders = $stmt->fetchAll();

  // For each order, get the order items with product details
  foreach ($orders as &$order) {
    $stmt = $db->prepare('
      SELECT
        oi.quantity,
        oi.price,
        p.name as product_name
      FROM order_items oi
      JOIN products p ON oi.product_id = p.id
      WHERE oi.order_id = ?
    ');
    $stmt->execute([$order['id']]);
    $order['items'] = $stmt->fetchAll();
  }

  require __DIR__ . '/../views/order_history.php';
}
