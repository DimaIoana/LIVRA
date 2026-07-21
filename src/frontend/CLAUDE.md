# src/frontend/

Pagini PHP randate integral pe server: HTML + CSS, fara API/AJAX/JS.

## Reguli
- Fara strat de API/fetch/JSON. Fiecare pagina face query direct in DB prin
  repository-urile din `src/backend`.
- Formularele fac POST clasic catre aceeasi pagina; dupa o operatie reusita se
  face redirect (Post-Redirect-Get). Reincarcarea de pagina e acceptabila.
- `_crud_page.php` e renderer-ul comun (tabel + cautare + sortare + formular in
  modal + confirmare de stergere). Fiecare pagina (`clienti.php`, `expedieri.php`,
  ...) pregateste `$repo` si `$config`, apoi il include.
- Fara build step (webpack/vite).
