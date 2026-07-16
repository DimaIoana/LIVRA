# src/api/

Strat subtire de endpoint-uri PHP, consumate de `src/frontend` prin AJAX/fetch.

## Reguli
- Fiecare fisier = un endpoint (ex: `login.php`, `get-orders.php`).
- Raspuns intotdeauna JSON: `{ "success": true|false, "data": ..., "error": "..." }`.
- Fara logica de business aici - deleaga catre clase/functii din `src/backend`.
- Validare input minima (tip, prezenta) inainte de a apela backend-ul.
