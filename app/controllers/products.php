<?php
// app/controllers/products.php

function show_products() {
  $db = db();
  $products = $db->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
  require __DIR__ . '/../views/home.php';
}
