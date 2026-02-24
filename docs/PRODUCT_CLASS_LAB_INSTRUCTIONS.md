# Lab 5 instructions - continue the refactoring, now with a `Product` class

This lab walks you through refactoring the `Product` entity from procedural PHP to
Object-Oriented PHP, following the exact same approach already applied to the `User`
class. By the end you will also add a small new feature on your own.

---

## Part 1 — Remember what we did with the User class

Before you write a single line of code, make sure you understand what was already done.

### The problem with procedural code

In the original codebase, `app/controllers/auth.php` contained raw SQL queries mixed
directly with HTTP logic:

```php
function login() {
    $db = db();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();   // $user is a plain PHP array

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user['email'];
    }
}
```

This has several problems:

- `$user` is just an anonymous bag of data — nothing says what fields it has or what
  operations are valid on it.
- Every function that needs user data must either repeat the query or pass raw arrays
  around.
- Validation and business logic (password rules, session handling) are scattered across
  multiple controller functions.

### The solution: a User class

We created `app/models/User.php`. That file now owns **everything** related to users:

| What it does | How |
|---|---|
| Holds user data | Properties: `$id`, `$email`, `$passwordHash` |
| Fetches users from the DB | Static methods: `findByEmail()`, `findById()` |
| Creates a user (with validation) | Static method: `create()` |
| Exposes safe, named behaviour | Instance methods: `verifyPassword()`, `login()` |
| Session helpers | Static methods: `isLoggedIn()`, `current()`, `logout()` |

The controller became thin — it calls model methods and hands data to the view, nothing
more:

```php
function login() {
    $user = User::findByEmail($email);          // returns a User object or null

    if ($user && $user->verifyPassword($password)) {
        $user->login();                         // sets session variables
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid email or password.';
}
```

### Important decisions to make when refactoring

**Static methods for DB lookups** (`User::findByEmail()`) — you call these on the
*class* because you do not yet have a specific user object. You are asking the class
to go and find one.

**Instance methods for behaviour** (`$user->verifyPassword()`) — these operate on a
*specific* user. The method needs `$this->passwordHash`, which only exists once you
have an object.

**`$this->` vs `self::`** — inside an instance method, `$this` refers to the current
object. Inside a static method, `self::` refers to the class itself.

**The controller does not query the database** — if a controller function contains
`$db->prepare(...)`, something is wrong. SQL belongs in the model.

---

## Part 2 — Reset Your Database

Before you start, drop the old database and recreate it cleanly using the seed script.
I've changed a bit the tables, including a new field for `products` containing an image path for the products.
You'll also need to create a new user to test the system.

1. Open **phpMyAdmin** in your browser.
2. In the left sidebar, click on the `simple-shop` database.
3. Click the **Operations** tab at the top.
4. Scroll to the **Drop the database** section and click the button. Confirm.

### Recreate the database and seed demo data

Open this URL in your browser:

```
http://localhost/simple-shop-lab5/scripts/seed.php
```

You should see a plain-text response listing each step that ran.

Refresh the home page at `http://localhost/simple-shop-lab5/public/` and
confirm the product listing appears.

---

## Part 3 — Refactor the Product Entity

### What exists now

| File | What it does now |
|---|---|
| `app/controllers/products.php` | Runs a raw `SELECT * FROM products` query |
| `app/views/home.php` | Reads `$p['name']`, `$p['price']`, etc. as array keys |
| `app/models/` | `User.php` only — no `Product.php` yet |

Your goal: create `Product.php`, update the controller and the view, and leave the
browser output identical to what it was.

---

### Step 1 — Create `app/models/Product.php`

Create a new file at that path and paste in the following code exactly:

```php
<?php
// app/models/Product.php

class Product {

    // ========== Properties ==========

    // TODO: create the four properties for `Product`.
    // Keep the same names as in the SQL table, converting to camelCase if needed
    // Pay attention to the nullable properties use the ? (question mark) accordingly

    // TODO: create the constructor as we did for `User`.
    // Remember that in PHP the constructor is named `__construct`.

    // ========== Static Methods ==========

    public static function all(): array {
        $db   = db();
        $rows = $db->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();

        $products = [];
        foreach ($rows as $row) {
            $products[] = new Product(
                (int)$row['id'],
                $row['name'],
                (float)$row['price'],
                $row['image_path']
            );
        }
        return $products;
    }

    public static function findById(int $id): ?Product {
        $db   = db();
        $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        if (!$data) return null;

        return new Product(
            (int)$data['id'],
            $data['name'],
            (float)$data['price'],
            $data['image_path']
        );
    }

    // ========== Instance Methods ==========

    public function getFormattedPrice(): string {
        return '$' . number_format($this->price, 2);
    }
}
```

#### Questions — Step 1

1. `all()` and `findById()` are `static`, but `getFormattedPrice()` is not. Can you
   explain the difference in simple terms? Think about whether you need a specific
   product to call each one.

2. `findById()` returns `null` when no product is found. Why is that more useful than
   returning an empty `Product` object with no data?

3. Look at `all()`: it creates a `Product` object from each database row. What would
   the view code look like if we skipped that and returned the raw rows directly? Would
   that be better or worse, and why?

---

### Step 2 — Register the class in the front controller

Open **`public/index.php`** and "import" the `Product` class as we did for `User`, before the controllers.

#### Questions — Step 2

1. The `require` for `Product.php` must be placed *before* the controller files.
   What error would you see if you put it after them?

---

### Step 3 — Update `app/controllers/products.php`

Replace the entire file with:

```php
<?php
// app/controllers/products.php

function show_products() {
    $products = Product::all();
    require __DIR__ . '/../views/home.php';
}
```

#### Questions — Step 3

1. The SQL query that used to be in the controller is now gone. Where did it go?

2. Imagine a future admin page that also needs to list all products. With the old
   code, you would copy and paste the SQL query. What do you do now instead?

---

### Step 4 — Update `app/views/home.php`

Replace the entire file with:

```php
<h2>Featured Instruments</h2>
<p class="muted">Quality pre-owned instruments - Affordable music for everyone</p>

<?php if (empty($products)): ?>
  <div class="alert error">No instruments available. Run <code>php scripts/seed.php</code> to add demo instruments.</div>
<?php endif; ?>

<div class="grid">
<?php foreach ($products as $p): ?>
  <div class="card">
    <?php if (!empty($p->imagePath)): ?>
      <img src="<?= htmlspecialchars($p->imagePath) ?>" alt="<?= htmlspecialchars($p->name) ?>">
    <?php endif; ?>
    <h3><?= htmlspecialchars($p->name) ?></h3>
    <p><strong><?= htmlspecialchars($p->getFormattedPrice()) ?></strong></p>
    <a class="btn" href="index.php?action=add_to_cart&id=<?= (int)$p->id ?>">Add to Cart</a>
  </div>
<?php endforeach; ?>
</div>
```

Notice the changes from the previous version:

| Before (array) | After (object) |
|---|---|
| `$p['image_path']` | `$p->imagePath` |
| `$p['name']` | `$p->name` |
| `$p['price']` + `number_format(...)` | `$p->getFormattedPrice()` |
| `$p['id']` | `$p->id` |

#### Questions — Step 4

1. The view used to format the price itself with `number_format(...)`. Now it just
   calls `$p->getFormattedPrice()`. If the store decided to show prices in euros
   instead of dollars, what would you change with the new approach? What would you
   have had to do with the old approach?

2. Before this change, a typo like `$p['naem']` in the view would silently return
   nothing. Now, `$p->naem` on a `Product` object causes an error immediately. Is
   catching mistakes early a good or bad thing? Why?

---

### Step 5 — Test

Reload `http://localhost/simple-shop-lab5/public/` in your browser. The
product grid should look exactly the same as before.

To confirm `$p` is now a `Product` object and not a plain array, temporarily add this
line at the very top of the `foreach` loop in `home.php`:

```php
<?php var_dump($p); die(); ?>
```

You should see `object(Product)` in the output. Remove that line before continuing.

---

## Part 4 — New Feature: Product Search

You will now add a feature entirely on your own — no code is provided. Use everything
you have learned so far.

### What to build

Add a static method `Product::search(string $term): array` to `Product.php`. It
should:

- Accept a search term string.
- Query the `products` table for rows where the `name` column contains that term
  (case-insensitive, partial match).
- Return an array of `Product` objects, following the same pattern as `all()`.

Then add a minimal search form to `home.php` and update `show_products()` in
`app/controllers/products.php` to use the search result when a term is submitted.

### Step-by-step guidance

1. **Use this SQL query** inside `search()`:

   ```sql
   SELECT * FROM products WHERE name LIKE ? ORDER BY id DESC
   ```

   The `?` placeholder should be bound to `"%{$term}%"` — the `%` symbols mean
   "anything before or after the term". The prepared statement handles it safely,
   so you do not need to escape the input yourself.

2. **Write the method.** Add `search()` to `Product.php`. It follows the same
   structure as `all()`: get a DB connection, prepare a query, execute it, loop
   through rows, build and return an array of `Product` objects.

3. **Add the form.** Paste this just above the `<div class="grid">` line in
   `app/views/home.php`:

   ```html
   <form method="GET" action="index.php" style="margin-bottom: 1rem;">
     <input type="hidden" name="action" value="home">
     <input type="text" name="q" placeholder="Search instruments..."
            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
     <button type="submit">Search</button>
   </form>
   ```

   The hidden `action` field keeps the routing working. The `value` attribute
   re-fills the box with the current search term after the page reloads.

4. **Update the controller.** In `show_products()`, check whether `$_GET['q']` is set
   and non-empty. If so, call `Product::search($_GET['q'])`. Otherwise, call
   `Product::all()`. Either way, assign the result to `$products` and render the view.

5. **Test.** Search for "guitar" — you should see only guitar products. Search for
   something that does not exist — you should see an empty grid. Clear the search —
   all products return.

### Questions — Part 4

1. Why did you add `search()` to the `Product` class rather than writing the SQL
   query directly inside `show_products()` in the controller?

2. `search()` is a static method. Does that make sense for a method that searches for
   products? Why do you not need an existing `Product` object to call it?

3. If you later needed to also search by price range, where would you add that
   method, and why?

4. Before this lab, a `Product` was just a plain PHP array — any piece of code could
   read or write any key on it. Now it is an object with defined properties and
   methods. What does that change for a teammate who reads your code for the first
   time?

---

*Reference: open `app/models/User.php` any time you need to see a complete, working
example of every pattern used in this lab.*
