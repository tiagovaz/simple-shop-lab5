<div style="text-align: center; padding: 40px 20px;">
  <div style="max-width: 500px; margin: 0 auto;">

    <!-- Success Icon -->
    <div style="font-size: 60px; color: #4CAF50; margin-bottom: 20px;">
      ✓
    </div>

    <h2 style="color: #4CAF50; margin-bottom: 10px;">Order Placed Successfully!</h2>

    <p style="font-size: 16px; color: #666; margin-bottom: 30px;">
      Thank you for your purchase. Your order has been confirmed.
    </p>

    <!-- Order Details Box -->
    <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin-bottom: 30px;">
      <div style="margin-bottom: 15px;">
        <span style="color: #666; font-size: 14px;">Order Number</span>
        <div style="font-size: 24px; font-weight: 600; color: #333;">
          #<?= (int)$order_id ?>
        </div>
      </div>

      <div style="border-top: 1px solid #ddd; padding-top: 15px;">
        <span style="color: #666; font-size: 14px;">Total Amount</span>
        <div style="font-size: 28px; font-weight: 600; color: #4CAF50;">
          $<?= number_format((float)$total, 2) ?>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
      <a href="index.php?action=order_history" class="btn">
        View Order History
      </a>
      <a href="index.php?action=home" class="btn" style="background: #fff; border: 1px solid #ddd; color: #333;">
        Continue Shopping
      </a>
    </div>

    <!-- Additional Info -->
    <p class="muted" style="margin-top: 30px; font-size: 13px;">
      A confirmation has been recorded in your order history.
    </p>

  </div>
</div>
