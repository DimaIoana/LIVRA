# PRIMUL

## Descriere generala
Proiect web dezvoltat local pe XAMPP, cu backend PHP, frontend HTML/CSS/JS si baza de date MySQL. Proiectul gestioneaza comenzi si livrare de produse prin curieri si obiectivul este sa se optimizeze prin aceasta aplicatie calitate, viteza  de livrare si satisfacerea clientului.
Functionalitati principale: 
1. gestiune trasee, clienti, produse, financiar, stocks etc.
2. grafice pentru un dashboard.
3. un sistem pentru client care poate sa puna comanda
Aplicatia trebuie sa fie usor de folosit, dar sa aiba interfata frumoasa


## Structura proiectului
- `src/frontend/` - pagini PHP randate pe server (HTML/CSS), fara API/AJAX/JS
- `src/backend/` - logica de business, repository-uri PHP, servicii
- `src/database/` - scheme SQL, migrari, seed-uri
- `tools/` - scripturi, template-uri si utilitare auxiliare
- `docs/` - documentatie de proiect
- `.claude/memory/` - context persistent despre proiect, standarde si conventii

## Reguli generale de lucru
- Codul PHP nou se scrie in stil consistent cu ce exista deja in `src/backend` si `src/frontend`.
- Aplicatia e PHP randat pe server: fara strat de API/AJAX/JSON/JS pentru date. Paginile fac query direct in DB prin repository-uri. Nu se introduce API fara acordul explicit, in scris, al userului.
- Nu se introduc dependinte noi (Composer, framework-uri) fara acordul explicit al userului.
- Orice modificare de schema in baza de date se face prin fisiere in `src/database/`, nu direct in phpMyAdmin, ca sa ramana istoric.
- Vezi `.claude/memory/coding-standards.md` si `.claude/memory/conventions.md` pentru detalii.

## Mediu de dezvoltare
- Server local: XAMPP (Apache + MySQL)
- Root web: `C:\xampp\htdocs\CLAUDE\PRIMUL`

## Resurse in tools/utilities/
Fisiere puse de user. Cele Excel sunt deja integrate; PDF-urile raman doar notate.
- `distanta_orase.xlsx` - **integrat**: distantele rutiere intre orase, sursa pentru km-ii rutelor (vezi `src/database/seed_rute.sql`).
- `timp_orase.xlsx` - **integrat**: timpii de condus intre orase, sursa pentru `Durata_min` al rutelor.
- `ghidul_curierului.pdf` - manual "Pandoras Courier" pentru curierul de teren: dictionar de termeni (AWB, ramburs, despagubire), reguli de comunicare cu clientul, proceduri de ridicare/livrare, etichetare speciala (fragil/frig), verificare vehicul, GDPR. (neintegrat)
- `Ghid-de-ambalare.pdf` - ghid FAN Courier despre ambalare corecta: limite greutate/dimensiuni, produse interzise la transport, stivuire paleti. (neintegrat)
- `duba mercedes.pdf` - fisier prea mare pentru a fi citit direct (peste 20MB). (neintegrat)

Documentatia de proiect e in `docs/` (`README.md`, `CHANGELOG.md`, `algoritm_optimizare_rute.md`). PDF-urile neintegrate raman sursele de plecare daca se decide integrarea lor (ex: agent curier de teren, skill de politica ambalare).
