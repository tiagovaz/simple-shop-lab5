# UML Class Diagram

> **Legend:**
> - `User` — already refactored to OOP
> - `Product`, `Order`, `OrderItem`

```mermaid
classDiagram
    direction TB

    class User {
        +int id
        +string email
        +string passwordHash
        +findByEmail(email: string) User$
        +findById(id: int) User$
        +create(email: string, password: string) User$
        +current() User$
        +isLoggedIn() bool$
        +verifyPassword(password: string) bool
        +login() void
        +logout() void$
    }

    class Product {
        +int id
        +string name
        +float price
        +string imagePath
        +findById(id: int) Product$
        +all() Product[]$
        +getFormattedPrice() string
    }

    class Order {
        +int id
        +int userId
        +float total
        +string orderDate
        +string status
        +OrderItem[] items
        +createFromCart(userId: int, cart: array) Order$
        +getByUserId(userId: int) Order[]$
        +findById(id: int) Order$
        +loadItems() void
        +getFormattedTotal() string
        +getFormattedDate() string
    }

    class OrderItem {
        +int id
        +int orderId
        +int productId
        +int quantity
        +float price
        +string productName
        +create(orderId: int, productId: int, quantity: int, price: float) OrderItem$
        +getByOrderId(orderId: int) OrderItem[]$
        +getLineTotal() float
        +getFormattedLineTotal() string
    }

    User "1" --> "0..*" Order : places
    Order "1" *-- "1..*" OrderItem : contains
    Product "1" --> "0..*" OrderItem : referenced in
```

## Notes

- `$` after a method return type denotes a **static method** (called on the class, not on an instance).
- Static factory methods (`findById`, `all`, `create`, `current`, `isLoggedIn`) replace raw SQL scattered across the codebase.
- `Order` holds an `items` array of `OrderItem` objects, populated by `loadItems()`.
- `User` has no direct association to `Product` — the cart is managed via session and the `saved_carts` table (handled in the auth controller), not as a class relationship.
