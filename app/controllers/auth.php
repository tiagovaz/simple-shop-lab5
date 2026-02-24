<?php
// app/controllers/auth.php

require_once __DIR__ . '/../models/User.php';

/**
 * Saves the current session cart to the saved_carts table.
 * Merges with any previously saved cart for the user.
 * Does nothing if the user is not logged in or the cart is empty.
 */
function save_cart_to_db()
{
  if (!User::isLoggedIn() || empty($_SESSION['cart'])) {
    return;
  }

  $user_id = $_SESSION['user_id'];
  $db = db();

  // Get existing saved cart from database
  $stmt = $db->prepare('SELECT product_id, quantity FROM saved_carts WHERE user_id = ?');
  $stmt->execute([$user_id]);
  $savedItems = $stmt->fetchAll();

  // Start with the current session cart
  $mergedCart = $_SESSION['cart'];

  // Merge in any previously saved items
  foreach ($savedItems as $item) {
    $product_id = $item['product_id'];
    $quantity = $item['quantity'];
    if (isset($mergedCart[$product_id])) {
      $mergedCart[$product_id] += $quantity;
    } else {
      $mergedCart[$product_id] = $quantity;
    }
  }

  // Replace saved cart with merged result
  $stmt = $db->prepare('DELETE FROM saved_carts WHERE user_id = ?');
  $stmt->execute([$user_id]);

  $stmt = $db->prepare('INSERT INTO saved_carts (user_id, product_id, quantity) VALUES (?, ?, ?)');
  foreach ($mergedCart as $product_id => $quantity) {
    $stmt->execute([$user_id, $product_id, $quantity]);
  }
}

function login()
{
  $error = null;

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Find the user using User class
    $user = User::findByEmail($email);

    // Check password and login
    if ($user && $user->verifyPassword($password)) {
      $user->login();

      // Restore saved cart
      $db = db();
      $stmt = $db->prepare('SELECT product_id, quantity FROM saved_carts WHERE user_id = ?');
      $stmt->execute([$user->id]);
      $items = $stmt->fetchAll();
      if ($items) {
        // Initialize cart if it doesn't exist
        if (!isset($_SESSION['cart'])) {
          $_SESSION['cart'] = [];
        }

        // Merge: add quantities if product already in cart
        foreach ($items as $item) {
          $product_id = $item['product_id'];
          $quantity = $item['quantity'];

          if (isset($_SESSION['cart'][$product_id])) {
            // Product already in session cart - add quantities
            $_SESSION['cart'][$product_id] += $quantity;
          } else {
            // Product not in session cart - just add it
            $_SESSION['cart'][$product_id] = $quantity;
          }
        }

        // Delete saved cart from database
        $stmt = $db->prepare('DELETE FROM saved_carts WHERE user_id = ?');
        $stmt->execute([$user->id]);
      }

      // Redirect to home page
      header('Location: index.php');
      exit;
    }

    $error = 'Invalid email or password.';
  }

  require __DIR__ . '/../views/login.php';
}

function register()
{
  $error = null;

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Try to create user using User class
    $user = User::create($email, $password);

    if ($user) {
      // Redirect to login page
      header('Location: index.php?action=login');
      exit;
    }

    $error = 'Invalid email/password or email already exists. Password must be at least 8 characters with uppercase, lowercase, number, and special character.';
  }

  require __DIR__ . '/../views/register.php';
}

function logout()
{
  // Save cart to database before clearing the session
  save_cart_to_db();

  // Log out using User class
  User::logout();

  // Redirect to home page
  header('Location: index.php');
  exit;
}
