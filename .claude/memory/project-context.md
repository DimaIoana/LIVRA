# Context proiect PRIMUL

## Ce este
Aplicatie web (XAMPP local, PHP + MySQL + HTML/CSS) pentru o firma de curierat:
gestiune clienti/soferi/rute/stoc, magazin online cu comenzi, expedieri cu
optimizare de rute si portal de urmarire pentru client.

## Stare curenta (2026-07-23)
Functional. Vezi `docs/README.md` (documentatie) si `docs/CHANGELOG.md` (istoric).
- CRUD server-rendered pentru clienti, soferi, rute, produse, comenzi, expedieri
  (renderer comun `src/frontend/_crud_page.php`).
- Magazin online (`magazin.php`, `cos.php`) + comenzi.
- Optimizare rute (`src/backend/OptimizareRuteService.php`): alege cele mai bune 3
  rute depozit->client dupa timp ajustat (viteza + tip drum + vreme din API ANM via
  `MeteoService`), genereaza expedieri + AWB. Spec: `docs/algoritm_optimizare_rute.md`.
- Portal client (`portal_client.php`): login prin nume + AWB, urmarire colet.
- Depozite: Arad, Braila, Pitesti. Program curieri 07:00-22:00, toate zilele.
- Date in format european; `Data_expediere`/livrare sunt DATETIME (cu ora).

## Decizii de arhitectura
- Fara framework PHP (Composer/Laravel) - doar daca userul cere explicit.
- Fara strat de API/AJAX/JS pentru date: pagini PHP randate pe server, POST + redirect.
  Exceptie autorizata in scris: API-ul meteo ANM, consumat server-side.
- Schimbari de schema doar prin fisiere numerotate in `src/database/` (istoric).
