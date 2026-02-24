# Entity Relationship Diagram

```mermaid
erDiagram
    users {
        int id PK
        varchar email UK
        varchar password_hash
    }
    products {
        int id PK
        varchar name
        decimal price
        varchar image_path "nullable"
    }
    saved_carts {
        int id PK
        int user_id FK
        int product_id FK
        int quantity
    }
    orders {
        int id PK
        int user_id FK
        decimal total
        datetime order_date
        varchar status
    }
    order_items {
        int id PK
        int order_id FK
        int product_id FK
        int quantity
        decimal price
    }

    users ||--o{ saved_carts : "saves"
    products ||--o{ saved_carts : "saved in"
    users ||--o{ orders : "places"
    orders ||--|{ order_items : "contains"
    products ||--o{ order_items : "included in"
```

## Notes

- `saved_carts` is a temporary persistence table: it stores a user's cart between sessions (populated on logout/timeout, cleared on login).
- `order_items.price` stores the product price **at the time of purchase** — not the current price — so historical order totals remain accurate.
- `orders.status` defaults to `'completed'` (no payment gateway in this project).
- `image_path` is nullable so products without an image still work.
