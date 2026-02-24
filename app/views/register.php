<h2>Register</h2>

<?php if (!empty($error ?? null)): ?>
  <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="index.php?action=register" class="form">
  <h2>Create Account</h2>
  <label>Email</label>
  <!-- inserting a bug here would make the backend email filter relevant! -->
  <input type="email" name="email" required placeholder="you@example.com">

  <label>Password (min 6 chars)</label>
  <input type="password" name="password" minlength="6" required placeholder="Create a password">

  <button type="submit">Create Account</button>
  
  <p style="margin-top: 20px; text-align: center; color: var(--text-muted);">
    Already have an account? <a href="index.php?action=login" style="font-weight: 600;">Login here</a>
  </p>
</form>
