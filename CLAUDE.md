# PRIMUL

## Descriere generala
Proiect web dezvoltat local pe XAMPP, cu backend PHP, frontend HTML/CSS/JS si baza de date MySQL. Proiectul gestioneaza comenzi si livrare de produse prin curieri si obiectivul este sa se optimizeze prin aceasta aplicatie calitate, viteza  de livrare si satisfacerea clientului.
Functionalitati principale: 
1. gestiune trasee, clienti, produse, financiar, stocks etc.
2. grafice pentru un dashboard.
3. un sistem pentru client care poate sa puna comanda
Aplicatia trebuie sa fie usor de folosit, dar sa aiba interfata frumoasa


## Structura proiectului
- `src/api/` - endpoint-uri PHP consumate de frontend (JSON)
- `src/frontend/` - HTML, CSS, JS, componente de interfata
- `src/backend/` - logica de business, clase PHP, servicii
- `src/database/` - scheme SQL, migrari, seed-uri
- `tools/` - scripturi, template-uri si utilitare auxiliare
- `docs/` - documentatie de proiect
- `.claude/memory/` - context persistent despre proiect, standarde si conventii

## Reguli generale de lucru
- Codul PHP nou se scrie in stil consistent cu ce exista deja in `src/backend` si `src/api`.
- Nu se introduc dependinte noi (Composer, framework-uri) fara acordul explicit al userului.
- Orice modificare de schema in baza de date se face prin fisiere in `src/database/`, nu direct in phpMyAdmin, ca sa ramana istoric.
- Vezi `.claude/memory/coding-standards.md` si `.claude/memory/conventions.md` pentru detalii.

## Mediu de dezvoltare
- Server local: XAMPP (Apache + MySQL)
- Root web: `C:\xampp\htdocs\CLAUDE\PRIMUL`

## Resurse in tools/utilities/
Documente de referinta puse de user, neintegrate inca in agenti/skill-uri (doar notate, fara actiune):
- `ghidul_curierului.pdf` - manual "Pandoras Courier" pentru curierul de teren: dictionar de termeni (AWB, ramburs, despagubire), reguli de comunicare cu clientul, proceduri de ridicare/livrare, etichetare speciala (fragil/frig), verificare vehicul, GDPR.
- `Ghid-de-ambalare.pdf` - ghid FAN Courier despre ambalare corecta: limite greutate/dimensiuni, produse interzise la transport, stivuire paleti.
- `rute romania.pdf` - harta de baza a Romaniei (doar imagine, fara date structurate).
- `duba mercedes.pdf` - fisier prea mare pentru a fi citit direct (peste 20MB).

Daca se decide integrarea lor (ex: agent dedicat curierului de teren, skill de politica ambalare), acestea sunt sursele de plecare.
