<h2>Login</h2>

<?php if (!empty($success ?? null)): ?>
  <div class="alert success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($error ?? null)): ?>
  <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($_SESSION['timeout_message'])):?>
  <div class="alert error"><?= htmlspecialchars($_SESSION['timeout_message']) ?></div>
<?php endif; ?>

<form method="post" action="index.php?action=login" class="form">
  <h2>Welcome Back</h2>
  <label>Email</label>
  <input type="email" name="email" required placeholder="you@example.com">

  <label>Password</label>
  <input type="password" name="password" required placeholder="Enter your password">

  <button type="submit">Login</button>
  
  <p style="margin-top: 20px; text-align: center; color: var(--text-muted);">
    Don't have an account? <a href="index.php?action=register" style="font-weight: 600;">Register here</a>
  </p>
</form>
