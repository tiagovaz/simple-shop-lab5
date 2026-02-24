<h2>Your Shopping Cart</h2>

<?php if (empty($items)): ?>
  <div class="alert" style="text-align: center; padding: 40px;">
    <p style="font-size: 18px; margin: 16px 0 8px 0; font-weight: 600;">Your cart is empty</p>
    <p class="muted">Browse our collection of quality pre-owned instruments!</p>
    <a href="index.php?action=home" class="btn" style="margin-top: 20px;">Browse Instruments</a>
  </div>
<?php else: ?>

  <div class="table">
    <div class="row head">
      <div>Instrument</div><div>Price</div><div>Qty</div><div>Line</div><div></div>
    </div>

  <!-- Investigate $items data structure <?php var_dump($items); ?> -->

    <?php foreach ($items as $it): $p = $it['product']; ?>
      <div class="row">
        <div><?= htmlspecialchars($p['name']) ?></div>
        <div>$<?= number_format((float)$p['price'], 2) ?></div>
        <div><?= (int)$it['qty'] ?></div>
        <div>$<?= number_format((float)$it['line'], 2) ?></div>
        <div><a class="danger" href="index.php?action=remove_from_cart&id=<?= (int)$p['id'] ?>">Remove</a></div>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="total">Total: <strong>$<?= number_format((float)$total, 2) ?></strong></p>

  <?php if (!User::isLoggedIn()): ?>
    <div class="alert" style="text-align: center;">
      <p>Please <a href="index.php?action=login" style="font-weight: 600;">login</a> to proceed to checkout</p>
    </div>
  <?php else: ?>
    <div style="text-align: center; margin-top: 20px;">
      <a href="index.php?action=checkout" class="btn">Proceed to Checkout</a>
    </div>
  <?php endif; ?>
<?php endif; ?>
