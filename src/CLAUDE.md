# src/

Codul sursa al aplicatiei LIVRA, organizat pe straturi:

- `frontend/` - pagini PHP randate pe server (HTML + CSS), fara API/AJAX/JS
- `backend/` - logica de business si acces la date (repository-uri PHP prin PDO)
- `database/` - conexiune, scheme SQL si migrari

Fluxul de date: pagina `frontend/*.php` include repository-ul potrivit din
`backend/`, face query direct in `database` si randeaza HTML server-side.
Formularele fac POST clasic catre aceeasi pagina (fara fetch/JSON).
