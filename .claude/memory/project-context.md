# Context proiect PRIMUL

## Ce este
Aplicatie web (XAMPP local, PHP + MySQL + HTML/CSS) pentru o firma de curierat:
gestiune clienti/soferi/rute/stoc, magazin online cu comenzi, expedieri cu
optimizare de rute si portal de urmarire pentru client.

## Stare curenta (2026-07-28)
Functional. Vezi `docs/README.md` (documentatie) si `docs/CHANGELOG.md` (istoric) -
tinute la zi, sursa de adevar.
- CRUD server-rendered pentru clienti, soferi, rute, produse, comenzi, expedieri
  (renderer comun `src/frontend/_crud_page.php`).
- Magazin online cu **login client** (`login.php`: alegi numele, fara parola) -
  magazinul si cosul cer autentificare; cos separat per client logat.
- Optimizare rute (`src/backend/OptimizareRuteService.php`): cele mai bune 3 rute
  depozit->client dupa timp ajustat (viteza + tip drum + vreme din API ANM), plus
  **cost carburant** per ruta (pret motorina din API pretcarburant.ro x consum 12
  l/100km). Genereaza expedieri + AWB. Spec: `docs/algoritm_optimizare_rute.md`.
- La livrare (status "Livrat") **stocul scade** automat din inventory.
- Produse: `Unit_Cost` = pret de vanzare, `Cost_Unitar` = cost de achizitie.
- Portal client (`portal_client.php`): urmarire colet prin nume + AWB.
- Depozite: Arad, Braila, Pitesti. Program curieri 07:00-22:00, toate zilele.
- Date in format european; `Data_expediere`/livrare sunt DATETIME (cu ora).

## Decizii de arhitectura
- Fara framework PHP (Composer/Laravel) - doar daca userul cere explicit.
- Fara strat de API/AJAX/JS pentru date: pagini PHP randate pe server, POST + redirect.
  Exceptii autorizate in scris, consumate server-side de PHP: API-ul meteo ANM
  (`meteoromania.ro`) si pretul carburantului (`pretcarburant.ro`, atribuire ceruta).
- Schimbari de schema doar prin fisiere numerotate in `src/database/` (istoric,
  ajunse la 014).
