<?php
// scripts/seed.php
// Creates the database/tables (if missing) and inserts demo products.

require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';

function println($msg)
{
  echo $msg . PHP_EOL;
}

try {
  // Connect without DB selected
  $pdo = db(false);

  // Create database
  $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET ' . DB_CHARSET . ' COLLATE utf8mb4_unicode_ci');
  println('Database ensured: ' . DB_NAME);

  // Connect with DB selected
  $pdo = db(true);

  // Create tables
  $pdo->exec('CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL
  )');

  $pdo->exec('CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255) NULL
  )');

  // Add image_path column to existing products table if it doesn't exist yet
  try {
    $pdo->exec('ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL');
    println('Added image_path column to products table.');
  } catch (Exception $e) {
    // Column already exists — nothing to do
  }

  $pdo->exec('CREATE TABLE IF NOT EXISTS saved_carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
)');

  $pdo->exec('CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(20) NOT NULL DEFAULT \'completed\',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  println('Tables ensured: users, products, saved_carts, orders, order_items');

  // Real instrument photos from Wikimedia Commons (public domain / CC-licensed).
  // Thumbnail URLs are the canonical 400px thumb format produced by the Commons API.
  $products = [
    ['Fender Stratocaster (1998)',  850.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Fender_Stratocaster_004.JPG/400px-Fender_Stratocaster_004.JPG'],
    ['Yamaha Acoustic Guitar',      245.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Yamaha_FG110_Acoustic_Guitar.JPG/400px-Yamaha_FG110_Acoustic_Guitar.JPG'],
    ['Roland Digital Piano 88-key', 650.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e3/Roland_EP-95_Digital_Piano_-_Many_keys.jpg/400px-Roland_EP-95_Digital_Piano_-_Many_keys.jpg'],
    ['Pearl Export Drum Kit',       425.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e6/Pearl_Masters_Maple_Reserve.jpg/400px-Pearl_Masters_Maple_Reserve.jpg'],
    ['Ibanez Bass Guitar',          320.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Ibanez_GSR200_electric_Bass_body.jpg/400px-Ibanez_GSR200_electric_Bass_body.jpg'],
    ['Korg Synthesizer',            380.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7d/Korg_M3_%26_Trinity.JPG/400px-Korg_M3_%26_Trinity.JPG'],
    ['Bach Trumpet (Silver)',       520.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d1/Bach_Mercedes_Trumpet.jpg/400px-Bach_Mercedes_Trumpet.jpg'],
    ['Yamaha Clarinet',             295.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Yamaha_Clarinet_YCL-457II-22_%28rotated%29.jpg/400px-Yamaha_Clarinet_YCL-457II-22_%28rotated%29.jpg'],
    ['Gibson Les Paul (2005)',     1250.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/1974_Gibson_Les_Paul_Custom.JPG/400px-1974_Gibson_Les_Paul_Custom.JPG'],
    ['Taylor Acoustic 12-String',   580.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/68/Yamaha_FG-312_12-String_Acoustic_Guitar_%281977-1981%29.jpg/400px-Yamaha_FG-312_12-String_Acoustic_Guitar_%281977-1981%29.jpg'],
    ['Fender Precision Bass',       475.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/eb/Fender_us_precision_bass.jpg/400px-Fender_us_precision_bass.jpg'],
    ['Casio Keyboard 61-Key',       180.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1c/Casio_CTK-496_keyboard.jpg/400px-Casio_CTK-496_keyboard.jpg'],
    ['Ludwig Snare Drum',           220.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/58/Zestaw_perkusyjny_Element_Power_Fusion_firmy_Ludwig.jpg/400px-Zestaw_perkusyjny_Element_Power_Fusion_firmy_Ludwig.jpg'],
    ['Selmer Alto Saxophone',       890.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/97/Etude_Alto_Saxophone.JPG/400px-Etude_Alto_Saxophone.JPG'],
    ['Yamaha Flute (Silver)',       340.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/66/Yamaha_Flute_YFL-A421U.png/400px-Yamaha_Flute_YFL-A421U.png'],
    ['Martin Acoustic Guitar',      720.00, 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0c/Martin_D-28_Acoustic_Guitar.jpg/400px-Martin_D-28_Acoustic_Guitar.jpg'],
  ];

  // Insert products if the table is empty
  $count = (int) ($pdo->query('SELECT COUNT(*) AS c FROM products')->fetch()['c'] ?? 0);
  if ($count === 0) {
    $stmt = $pdo->prepare('INSERT INTO products (name, price, image_path) VALUES (?, ?, ?)');
    foreach ($products as $p) {
      $stmt->execute($p);
    }
    println('Inserted demo instruments: ' . count($products));
  } else {
    // Products already exist — patch any rows that are missing image_path
    $missing = (int) ($pdo->query('SELECT COUNT(*) AS c FROM products WHERE image_path IS NULL')->fetch()['c'] ?? 0);
    if ($missing > 0) {
      $imagesByName = [];
      foreach ($products as $p) {
        $imagesByName[$p[0]] = $p[2];
      }
      $stmt = $pdo->prepare('UPDATE products SET image_path = ? WHERE name = ? AND image_path IS NULL');
      $updated = 0;
      foreach ($imagesByName as $name => $url) {
        $stmt->execute([$url, $name]);
        $updated += $stmt->rowCount();
      }
      println('Products already existed. Updated image_path on ' . $updated . ' row(s).');
    } else {
      println('Products already exist (' . $count . '). Nothing to seed.');
    }
    exit(0);
  }

  println('Inserted demo instruments: ' . count($products));
  println('Done. Run: php -S localhost:8000 -t public');

} catch (Exception $e) {
  println('Seed failed: ' . $e->getMessage());
  exit(1);
}
