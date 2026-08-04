# Conventii - LIVRA

## Nume de fisiere
- PHP: `snake_case.php` (ex: `user_login.php`)
- SQL: numerotate secvential in `src/database/` (ex: `001_create_users.sql`, `002_add_orders.sql`)
- JS/CSS: `kebab-case` (ex: `main-menu.js`)

## Structura API
- Fiecare endpoint din `src/api/` raspunde in JSON.
- Format raspuns standard: `{ "success": true|false, "data": ..., "error": "..." }`

## Organizare foldere
- `src/frontend/` - doar prezentare (HTML/CSS/JS), fara logica de business.
- `src/backend/` - logica de business, clase si servicii PHP.
- `src/api/` - strat subtire care leaga frontend de backend, fara logica complexa.
- `src/database/` - scheme si migrari SQL.
