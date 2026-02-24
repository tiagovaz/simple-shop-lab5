# Refactoring to use OOP - starting with `User` model

This document shows how our codebase from Assignment 1 has been modified in order to 
follow a more professional standard for MVC applications. The final goal is to move everything
from procedural to object-oriented. For now, let's check how the `User` entity became a class
in our application, and how it affected the whole system.

---

## Starting point: the procedural version

Before the refactoring, all user-related logic lived directly inside the controller
functions in `app/controllers/auth.php`. A simplified version looked like this:

```php
// app/controllers/auth.php  (BEFORE)

function login() {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db   = db();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();                         // $user is just an array

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user['email'];         // only the email was saved
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid email or password.';
    require __DIR__ . '/../views/login.php';
}

function register() {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email.';
        require __DIR__ . '/../views/register.php';
        return;
    }

    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/';
    if (!preg_match($pattern, $password)) {
        $error = 'Password too weak.';
        require __DIR__ . '/../views/register.php';
        return;
    }

    $db   = db();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
    $stmt->execute([$email, $hash]);

    header('Location: index.php?action=login');
    exit;
}

function logout() {
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
    header('Location: index.php');
    exit;
}
```

### What are the problems here?

- **`$user` is a plain array.** You access data with `$user['email']`, `$user['id']`, etc.
  There is no type safety — you can accidentally write `$user['emal']` and PHP won't warn you.
- **SQL queries are scattered.** If another part of the app needs a user, it has to repeat
  the same `SELECT * FROM users WHERE email = ?` query.
- **Validation lives in the controller.** The password strength rule is buried in `register()`.
  If you add an admin panel that also creates users, you would copy-paste it.
- **Session management is inline.** The lines that write to `$_SESSION` are mixed in with
  redirect logic, making both harder to understand.
- **No way to "get the current user".** Other parts of the app only know `$_SESSION['user']`
  (a plain email string). To get the user's ID, you need another DB query.

---

## Step 1: Create the file `app/models/User.php`

The first decision is **where** the new code lives. We put it in `app/models/` because
a model represents a database entity and the business rules that apply to it.
The controller (`auth.php`) will keep handling HTTP concerns — reading POST data,
redirecting, requiring views — but all user-specific logic moves to the model.

```
app/
├── controllers/
│   └── auth.php       ← stays here, but becomes much thinner
└── models/
    └── User.php       ← new file we are about to build
```

Start with an empty class:

```php
<?php
// app/models/User.php

class User {

}
```

---

## Step 2: Decide what data a User holds — add properties

A `User` in our database has three columns: `id`, `email`, `password_hash`.
We translate each column into a **class property**.

```php
class User {
    public ?int $id;
    public string $email;
    public string $passwordHash;   // camelCase in PHP, snake_case in the DB column
}
```

**Why `?int` for `$id`?** The `?` means "nullable" — it can be `null`. This matters
because when a user object is created in memory *before* being saved to the database,
it does not yet have an ID. Once the `INSERT` runs and we call `lastInsertId()`, we
assign it. Using `?int` makes this possible without a workaround.

**Why camelCase for `$passwordHash`?** PHP convention is camelCase for properties.
The database column is `password_hash` (snake_case) because that is SQL convention.
They name the same thing, just in the style of their respective language.

---

## Step 3: Write the constructor

The constructor is called whenever we write `new User(...)`. Its job is to receive
all the data and assign it to the properties.

```php
public function __construct(?int $id, string $email, string $passwordHash) {
    $this->id           = $id;
    $this->email        = $email;
    $this->passwordHash = $passwordHash;
}
```

`$this` refers to the specific object being created. Think of it as "the user I'm
currently working with". Without `$this->`, `$email` would be just a local variable
that disappears when the constructor finishes.

Now we can create a User object manually:

```php
$user = new User(1, 'alice@example.com', '$2y$...');
echo $user->email;        // "alice@example.com"
echo $user->id;           // 1
```

---

## Step 4: Move database reads into static factory methods

The controller's `login()` function queries the database to get a user. That query
belongs in the model, not the controller. We create a **static method** for it.

A static method is called on the **class itself**, not on an instance:

```php
// Instance method — needs an object first:
$user->doSomething();

// Static method — called on the class directly:
User::findByEmail('alice@example.com');
```

Static methods are the right choice here because we do not *have* a User object yet —
we are trying to *find* one.

```php
public static function findByEmail(string $email): ?User {
    $db   = db();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $data = $stmt->fetch();

    if (!$data) return null;

    return new User($data['id'], $data['email'], $data['password_hash']);
}
```

The return type `?User` means "returns either a User object or null".
The method queries the database, and if it finds a row, it wraps it in a `User`
object and returns it. The caller receives a typed object, not a raw array.

We add the same pattern for looking up by ID:

```php
public static function findById(int $id): ?User {
    $db   = db();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if (!$data) return null;

    return new User($data['id'], $data['email'], $data['password_hash']);
}
```

---

## Step 5: Move validation and INSERT into `User::create()`

The `register()` controller function validates and inserts a new user. All of that
moves into a static method called `create()`:

```php
public static function create(string $email, string $password): ?User {
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    // Validate password strength
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/';
    if (!preg_match($pattern, $password)) {
        return null;
    }

    // Check that email is not already taken
    if (self::findByEmail($email) !== null) {
        return null;
    }

    // Hash the password and insert the row
    $db   = db();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
    $stmt->execute([$email, $hash]);

    $id = (int) $db->lastInsertId();
    return new User($id, $email, $hash);
}
```

Notice `self::findByEmail($email)` — inside a static method, `self::` means
"call another static method on this same class". It is the static equivalent
of `$this->`.

The method returns `null` on any failure, or the newly created `User` object on
success. The controller only needs to check `if ($user)` — it does not care *why*
creation failed.

---

## Step 6: Move password checking into an instance method

`password_verify()` compares a plain password against the stored hash. This is
behaviour that belongs to a specific user, so it becomes an **instance method**
(called on a `$user` object, not on the class):

```php
public function verifyPassword(string $password): bool {
    return password_verify($password, $this->passwordHash);
}
```

`$this->passwordHash` reads the hash that belongs to *this specific* user.
The controller now reads like plain English:

```php
if ($user && $user->verifyPassword($password)) { ... }
```

---

## Step 7: Move session management into instance and static methods

The lines that write to and clear `$_SESSION` are user-related behaviour.
We move them into the class:

```php
// Called on a specific user after their password checks out
public function login(): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $this->id;
    $_SESSION['user_email'] = $this->email;
}

// Called from anywhere — no specific user needed
public static function logout(): void {
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
    session_start();    // restart a clean session so $_SESSION is usable again
}
```

Notice the session now stores **both** `user_id` and `user_email` — previously it
only stored the email string. Storing the ID means we can query the database by ID
anywhere without needing to look up the email first.

We also add two utility methods that other parts of the app can use:

```php
// Returns the currently logged-in user as an object, or null
public static function current(): ?User {
    if (!isset($_SESSION['user_id'])) return null;
    return self::findById($_SESSION['user_id']);
}

// Quick boolean check — no DB query needed
public static function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}
```

`isLoggedIn()` is used constantly (in controllers, in `index.php` for session
timeout logic). It is a static method because we check login status from the class
level, not from an existing user object.

---

## Step 8: Update `app/controllers/auth.php` to use the class

With the model in place, the controller becomes thin. Each function now:
1. Reads from `$_POST`
2. Calls a method on the `User` class
3. Redirects or renders a view

```php
// app/controllers/auth.php  (AFTER)

require_once __DIR__ . '/../models/User.php';

function login() {
    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);      // returns User object or null

        if ($user && $user->verifyPassword($password)) {
            $user->login();                     // sets $_SESSION['user_id'] etc.
            header('Location: index.php');
            exit;
        }

        $error = 'Invalid email or password.';
    }

    require __DIR__ . '/../views/login.php';
}

function register() {
    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = User::create($email, $password); // validation + INSERT inside User

        if ($user) {
            header('Location: index.php?action=login');
            exit;
        }

        $error = 'Invalid email/password or email already exists.';
    }

    require __DIR__ . '/../views/register.php';
}

function logout() {
    save_cart_to_db();    // save cart before clearing the session
    User::logout();
    header('Location: index.php');
    exit;
}
```

The controller no longer contains any SQL. It also no longer contains any
validation rules. Those concerns are now fully owned by the model.

---

## Step 9: Register the model in `public/index.php`

`public/index.php` is the front controller — every request passes through it.
We add a `require` for `User.php` so the class is available everywhere:

```php
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/models/User.php';        // ← added here
require __DIR__ . '/../app/controllers/products.php';
require __DIR__ . '/../app/controllers/cart.php';
require __DIR__ . '/../app/controllers/auth.php';
require __DIR__ . '/../app/controllers/checkout.php';
```

Loading it here (rather than inside each controller with `require_once`) means
all controllers and views automatically have access to the `User` class without
repeating the require.

The session timeout logic in `index.php` also becomes readable:

```php
// Before: checking $_SESSION['user'] (a plain string)
if (isset($_SESSION['user']) && ...) { ... }

// After: using the static helper methods
if (User::isLoggedIn() && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        save_cart_to_db();
        User::logout();
        ...
    }
}
```

---

## Summary: what moved where

| Before (procedural) | After (OOP) |
|---|---|
| `SELECT * FROM users WHERE email = ?` in `login()` | `User::findByEmail()` |
| `SELECT * FROM users WHERE id = ?` — repeated everywhere | `User::findById()` |
| Validation + `INSERT` in `register()` | `User::create()` |
| `password_verify(...)` inline in `login()` | `$user->verifyPassword()` |
| `$_SESSION['user'] = $user['email']` inline in `login()` | `$user->login()` |
| Session teardown inline in `logout()` | `User::logout()` |
| `isset($_SESSION['user'])` checks scattered everywhere | `User::isLoggedIn()` |
| Re-querying DB to get user object | `User::current()` |

---

## UML Class Diagram — `User`

Underlined names are static (called on the class). Non-underlined are instance (called on an object).

```
┌─────────────────────────────────────────────────────────┐
│                         User                            │
├─────────────────────────────────────────────────────────┤
│ + id : ?int                                             │
│ + email : string                                        │
│ + passwordHash : string                                 │
├─────────────────────────────────────────────────────────┤
│  INSTANCE METHODS (need a $user object)                 │
│                                                         │
│ + __construct(id, email, passwordHash) : void           │
│ + verifyPassword(password : string) : bool              │
│ + login() : void                                        │
│                                                         │
│  STATIC METHODS (called on the class directly)          │
│                                                         │
│ + findByEmail(email : string) : ?User        «static»   │
│ + findById(id : int) : ?User                 «static»   │
│ + create(email : string, password : string)             │
│         : ?User                              «static»   │
│ + current() : ?User                          «static»   │
│ + isLoggedIn() : bool                        «static»   │
│ + logout() : void                            «static»   │
└─────────────────────────────────────────────────────────┘
```

---

## HTTP Request / Response Flow Through the MVC Pattern

The diagram below traces a **login request** from the browser all the way through
the stack and back. Every other action (register, show products, checkout) follows
the same shape.

```
BROWSER
  │
  │  POST /index.php?action=login
  │  body: email=alice@…  password=secret
  ▼
┌──────────────────────────────────────────────────────────────┐
│  public/index.php   (Front Controller)                       │
│                                                              │
│  1. session_start()                                          │
│  2. require config, db, models, controllers                  │
│  3. check session timeout → User::isLoggedIn()               │
│  4. read $action = $_GET['action']  →  'login'               │
│  5. switch ($action) { case 'login': login(); }              │
└──────────────────────┬───────────────────────────────────────┘
                       │  calls login()
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  app/controllers/auth.php   (Controller)                     │
│                                                              │
│  login():                                                    │
│    read $_POST['email'] and $_POST['password']               │
│    call  User::findByEmail($email)  ──────────────────────┐  │
│                                                           │  │
│    ◄─────────────────────── returns User object or null ──┘  │
│                                                              │
│    call  $user->verifyPassword($password)  ───────────────┐  │
│                                                           │  │
│    ◄─────────────────────────────── returns true/false ───┘  │
│                                                              │
│    if ok: call  $user->login()  ──────────────────────────┐  │
│                                                           │  │
│    ◄──────────────────────────── writes to $_SESSION ─────┘  │
│                                                              │
│    header('Location: index.php')  ←─ redirect to browser     │
│    exit                                                      │
└──────────────────────┬───────────────────────────────────────┘
          │ (called by controller)
          ▼
┌──────────────────────────────────────────────────────────────┐
│  app/models/User.php   (Model)                               │
│                                                              │
│  findByEmail():   SELECT * FROM users WHERE email = ?        │
│  verifyPassword(): password_verify(input, hash)              │
│  login():         $_SESSION['user_id'] = $this->id           │
│                   $_SESSION['user_email'] = $this->email     │
└──────────────────────┬───────────────────────────────────────┘
                       │  SQL queries
                       ▼
              ┌─────────────────┐
              │    Database     │
              │   users table   │
              └────────┬────────┘
                       │  row data
                       └──────────────────────────► back up to Model
                                                    → back up to Controller


  After the redirect, browser makes a new GET request:

BROWSER
  │
  │  GET /index.php   (redirected here)
  ▼
┌──────────────────────────────────────────────────────────────┐
│  public/index.php                                            │
│  $action = 'home'  →  show_products()                        │
└──────────────────────┬───────────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  app/controllers/products.php   (Controller)                 │
│  show_products():  $products = Product::all()                │
│                    require 'views/home.php'                  │
└──────────────────────┬───────────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  app/views/home.php   (View)                                 │
│  Receives $products (array of objects)                       │
│  Outputs HTML only — no SQL, no business logic               │
└──────────────────────┬───────────────────────────────────────┘
                       │  HTML response
                       ▼
                    BROWSER
```

### Roles at a glance

| Layer | File(s) | Responsibility |
|---|---|---|
| Front controller | `public/index.php` | Boots the app, routes the request |
| Controller | `app/controllers/*.php` | Reads input, calls model, redirects or requires view |
| Model | `app/models/User.php` | Database access, validation, business rules |
| View | `app/views/*.php` | Renders HTML from variables — nothing else |
| Database | MySQL | Stores and retrieves data |
