<h2>Featured Instruments</h2>
<p class="muted">Quality pre-owned instruments - Affordable music for everyone</p>

<?php if (empty($products)): ?>
  <div class="alert error">No instruments available. Run <code>php scripts/seed.php</code> to add demo instruments.</div>
<?php endif; ?>

<div class="grid">
<?php foreach ($products as $p): ?>
  <div class="card">
    <?php if (!empty($p['image_path'])): ?>
      <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
    <?php endif; ?>
    <h3><?= htmlspecialchars($p['name']) ?></h3>
    <p><strong>$<?= number_format((float)$p['price'], 2) ?></strong></p>
    <a class="btn" href="index.php?action=add_to_cart&id=<?= (int)$p['id'] ?>">Add to Cart</a>
  </div>
<?php endforeach; ?>
</div>
