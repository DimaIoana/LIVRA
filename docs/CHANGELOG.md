# Log de proiect - PRIMUL

Jurnal cronologic al lucrului pe proiect. Cele mai recente sus.

## 2026-07-23 - Optimizare rute, AWB, vreme, portal client, date cu ora
- **Optimizare rute** (`OptimizareRuteService`): pentru fiecare produs dintr-o
  comanda se aleg rutele candidate (depozitele care au produsul -> orasul
  clientului) si se ordoneaza dupa timpul ajustat.
  - Ajustari: viteza (pe intervale), tip drum, si **vreme in timp real** (API ANM
    prin `MeteoService`, cache 30 min): ninsoare +60 min, ploaie +30 min.
- **Back office expediere** (`expediere_comanda.php`): prezinta 3 rute optimizate
  per produs; operatorul alege, se creeaza expedierea + **AWB unic**.
- **Portal client** (`portal_client.php`): login prin nume + AWB, lista clienti cu
  nr. in tranzit, raport colet (produs, km, timp, timp trecut, timp ramas estimat).
  Estimarile nu contrazic statusul real (nu spune "ajuns" cand e "In tranzit").
- **Program 07:00-22:00** (toate zilele) pentru calculul timpilor si al livrarii.
- **Date cu ora**: `Data_expediere` si datele de livrare devin DATETIME; afisare in
  **format european** peste tot.
- Coloana `tip_strada` la rute; migrari 007-010; sterse datele vechi de comenzi/
  expedieri (start curat).
- Documentatie: `algoritm_optimizare_rute.md`, `README.md`, acest changelog.

## 2026-07-22 - Poze, locatie produse, rute reconstruite
- Produse: camp de **upload poza** (thumbnail + preview) si **selector de depozit**
  (1=Arad, 2=Braila, 3=Pitesti).
- Rute reconstruite pe modelul depozit -> oras client (18 rute), cu **distante**
  (`distanta_orase.xlsx`), **timp de condus** (`timp_orase.xlsx`) si **viteza**.
- Migrari 005 (Durata_min), 006 (viteza); `seed_rute.sql`.

## 2026-07-21 - Server-rendered, magazin, comenzi
- Migrare la pagini PHP randate pe server (fara API/AJAX). Renderer CRUD comun
  `_crud_page.php`.
- Magazin online (`magazin.php`, `cos.php`) + model de comenzi (`comenzi`,
  `comenzi_produse`), migrarea 004. Redesign UI.

## 2026-07-17 - CRUD de baza
- CRUD pentru clienti, expedieri, soferi, produse (repository-uri PDO).
- Migrari 001-003 (fix-uri de schema).

## 2026-07-16 - Initializare
- Structura proiect: `CLAUDE.md`, `docs/`, `tools/`, `src/{frontend,backend,
  database}`, `.claude/` (agenti, skill-uri, memorie).
- Conexiune PDO catre baza `sameday_company`.
