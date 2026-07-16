# src/

Codul sursa al aplicatiei PRIMUL, organizat pe straturi:

- `api/` - endpoint-uri PHP consumate de frontend (JSON)
- `frontend/` - HTML, CSS, JS
- `backend/` - logica de business si servicii PHP
- `database/` - scheme SQL si migrari

Fluxul de date: `frontend` -> `api` -> `backend` -> `database`.
