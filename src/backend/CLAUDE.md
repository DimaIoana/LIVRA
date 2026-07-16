# src/backend/

Logica de business a aplicatiei: clase PHP, servicii, validari complexe.

## Reguli
- Aici traieste toata logica reala; `src/api` doar o expune.
- Acces la baza de date exclusiv prin PDO cu prepared statements.
- Fara dependinte de framework-uri externe fara acordul explicit al userului.
