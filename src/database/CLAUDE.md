# src/database/

Scheme SQL si migrari pentru baza de date MySQL a proiectului.

## Reguli
- Fiecare schimbare de schema = fisier nou, numerotat secvential (ex: `001_create_users.sql`).
- Nu se modifica direct schema din phpMyAdmin - orice schimbare trece prin fisier aici, pentru istoric.
- Fisierele de seed/date de test se marcheaza clar (ex: `seed_demo_data.sql`).
