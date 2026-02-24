<h2>Order History</h2>

<?php if (empty($orders)): ?>
  <div class="alert" style="text-align: center; padding: 40px;">
    <p style="font-size: 18px; margin: 16px 0 8px 0; font-weight: 600;">No orders yet</p>
    <p class="muted">Start shopping to see your orders here!</p>
    <a href="index.php?action=home" class="btn" style="margin-top: 20px;">Browse Instruments</a>
  </div>
<?php else: ?>

  <p class="muted" style="margin-bottom: 20px;">
    You have placed <?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?>
  </p>

  <?php foreach ($orders as $order): ?>
    <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px;">

      <!-- Order Header -->
      <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
        <div>
          <div style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 5px;">
            Order #<?= (int)$order['id'] ?>
          </div>
          <div class="muted" style="font-size: 13px;">
            Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['order_date'])) ?>
          </div>
        </div>
        <div style="text-align: right;">
          <div class="muted" style="font-size: 12px; margin-bottom: 3px;">Status</div>
          <div style="display: inline-block; background: #4CAF50; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: capitalize;">
            <?= htmlspecialchars($order['status']) ?>
          </div>
        </div>
      </div>

      <!-- Order Items Table -->
      <div class="table" style="margin-bottom: 15px;">
        <div class="row head">
          <div>Product</div><div>Price</div><div>Qty</div><div>Subtotal</div>
        </div>

        <?php foreach ($order['items'] as $item): ?>
          <div class="row">
            <div><?= htmlspecialchars($item['product_name']) ?></div>
            <div>$<?= number_format((float)$item['price'], 2) ?></div>
            <div><?= (int)$item['quantity'] ?></div>
            <div>$<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Order Total -->
      <div style="text-align: right; padding-top: 10px; border-top: 2px solid #ddd;">
        <span style="font-size: 16px; color: #666;">Total: </span>
        <strong style="font-size: 20px; color: #333;">$<?= number_format((float)$order['total'], 2) ?></strong>
      </div>

    </div>
  <?php endforeach; ?>

<?php endif; ?>
