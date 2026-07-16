# Standarde de cod - PRIMUL

## PHP
- PSR-12 pentru formatare (indentare 4 spatii, acolade pe linie noua pentru functii/clase).
- Interogari SQL doar prin PDO cu prepared statements - niciodata concatenare directa de input in query.
- Escapare output HTML cu `htmlspecialchars()` pentru orice date afisate care provin din input utilizator.

## JavaScript
- Fara framework la inceput; JS vanilla, modular pe fisiere in `src/frontend`.
- Fara build step (webpack/vite) pana nu e nevoie clara.

## Baza de date
- Fiecare schimbare de schema = fisier nou in `src/database/` (ex: `001_create_users.sql`), nu modificare directa in phpMyAdmin.

## General
- Fara comentarii care descriu ce face codul; doar acolo unde ratiunea (WHY) nu e evidenta.
- Fara abstractii sau configurabilitate pentru cazuri ipotetice viitoare.
