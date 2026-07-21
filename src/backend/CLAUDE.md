# src/backend/

Logica de business si acces la date: clase PHP, repository-uri, validari.

## Reguli
- Aici traieste toata logica reala; paginile din `src/frontend` doar includ
  repository-ul si il apeleaza direct (fara strat de API intre ele).
- Acces la baza de date exclusiv prin PDO cu prepared statements.
- Fara dependinte de framework-uri externe fara acordul explicit al userului.
