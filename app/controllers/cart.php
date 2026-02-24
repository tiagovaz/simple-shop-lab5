<?php
// app/controllers/cart.php

function add_to_cart() {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) {
    $_SESSION['cart'] = $_SESSION['cart'] ?? [];
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
  }
  
  // Redirect to cart page to show updated cart
  header('Location: index.php?action=cart');
  exit;
}

function remove_from_cart() {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0 && isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
  }
  
  // Redirect back to cart page
  header('Location: index.php?action=cart');
  exit;
}

function show_cart() {
  $db = db();
  $items = [];
  $total = 0;

// Using positional placeholder this time, example:
// $stmt = $db->prepare('SELECT * FROM products WHERE id = ? AND price > ?');
// $stmt->execute([$product_id, $min_price]);

  foreach (($_SESSION['cart'] ?? []) as $id => $qty) {
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([(int)$id]);
    $p = $stmt->fetch();

    if ($p) {
      $line = (float)$p['price'] * (int)$qty;
      $items[] = ['product' => $p, 'qty' => (int)$qty, 'line' => $line];
      $total += $line;
    }
  }

  require __DIR__ . '/../views/cart.php';
}
