<?php
require_once __DIR__ . '/../config.php';
$cartCount = array_sum($_SESSION['cart'] ?? []);
$currentUser = User::current(); // Get current logged-in user
$displayUser = $currentUser ? $currentUser->email : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_PATH) ?>/style.css">
</head>
<body>
<header class="top">
  <div class="container header-row">
    <h1><a href="index.php"><?= htmlspecialchars(APP_NAME) ?></a></h1>
    <nav class="nav">
      <a href="index.php?action=home">Home</a>
      <a href="index.php?action=cart">Cart (<?= (int)$cartCount ?>)</a>

      <?php if (!empty($displayUser)): ?>
        <a href="index.php?action=order_history">Orders</a>
        <span class="muted"><?= htmlspecialchars((string)$displayUser) ?></span>
        <a href="index.php?action=logout">Logout</a>
      <?php else: ?>
        <a href="index.php?action=login">Login</a>
        <a href="index.php?action=register">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container">

