<?php
// public/index.php
// Front controller

// Start output buffering - allows headers to be sent even after content is generated
ob_start();

session_start();

// When loading core application files we can use require instead
// Soon we'll learn about autoload and avoid all this requires
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/models/User.php';
require __DIR__ . '/../app/controllers/products.php';
require __DIR__ . '/../app/controllers/cart.php';
require __DIR__ . '/../app/controllers/auth.php';
require __DIR__ . '/../app/controllers/checkout.php';

// TODO: add here a comment explaining what this code does. Be precise.
if (User::isLoggedIn() && isset($_SESSION['last_activity'])) {
  $inactive = time() - $_SESSION['last_activity'];
  if ($inactive > SESSION_TIMEOUT) {
    // Save cart to database before the session is destroyed
    save_cart_to_db();
    User::logout();
    $_SESSION['timeout_message'] = 'Your session expired. Please login again.';
    header('Location: index.php?action=login');
    exit;
  }
}

// TODO: add here a comment explaining what this code does.
if (User::isLoggedIn()) {
  $_SESSION['last_activity'] = time();
}

$action = $_GET['action'] ?? 'home';

require __DIR__ . '/../app/views/header.php';

switch ($action) {
  case 'home':
    show_products();
    break;

  case 'add_to_cart':
    add_to_cart();
    break;

  case 'remove_from_cart':
    remove_from_cart();
    break;

  case 'cart':
    show_cart();
    break;

  case 'checkout':
    process_checkout();
    break;

  case 'checkout_success':
    show_checkout_success();
    break;

  case 'order_history':
    show_order_history();
    break;

  case 'login':
    login();
    break;

  case 'register':
    register();
    break;

  case 'logout':
    logout();
    break;

  default:
    echo '<p>Page not found</p>';
}

require __DIR__ . '/../app/views/footer.php';

// Send the buffered output
ob_end_flush();
