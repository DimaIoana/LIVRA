# PRIMUL - documentatie proiect

Aplicatie web pentru o firma de curierat/livrare colete: gestiune clienti, soferi,
rute, stoc/produse, magazin online cu comenzi, expedieri cu optimizare de rute si
un portal de urmarire pentru client.

## Stack si mediu
- **Backend:** PHP (fara framework), acces la date prin PDO cu prepared statements.
- **Frontend:** pagini PHP randate integral pe server (HTML + CSS), fara API/AJAX/JS
  pentru date. Formularele fac POST clasic + redirect (Post-Redirect-Get).
- **Baza de date:** MySQL/MariaDB (`sameday_company`).
- **Server local:** XAMPP (Apache + MySQL), root web `C:\xampp\htdocs\CLAUDE\PRIMUL`.
- **Exceptie API (autorizata in scris):** vremea se ia din API-ul public ANM
  (`meteoromania.ro`), consumat server-side de PHP - vezi optimizarea de rute.

## Structura
- `src/frontend/` - pagini PHP (HTML/CSS), inclusiv renderer-ul CRUD comun
  `_crud_page.php` si bootstrap-ul de magazin `_shop.php`.
- `src/backend/` - repository-uri PDO + servicii (`OptimizareRuteService`,
  `MeteoService`).
- `src/database/` - conexiune (`db_connection.php`) + migrari numerotate + seed-uri.
- `docs/` - aceasta documentatie + specificatia algoritmului + changelog.
- `tools/utilities/` - resurse (ghiduri PDF, tabele Excel de distante/timpi).

## Baza de date (tabele principale)

- **clienti** - ClientID, Nume, Tip (fizica/juridica), Email, Telefon, Oras,
  Data_inregistrare.
- **soferi** - SoferID, Nume, Telefon, Oras_baza, Data_angajare.
- **rute** - RutaID, Oras_origine (depozit), Oras_destinatie (oras client),
  Distanta_km, **Durata_min** (timp prestabilit), **viteza** (km/h),
  **tip_strada** (autostrada/dn/drum judetean/drum comunal).
- **inventory** - istoric lunar de stoc: Product_ID, Product_Name, Category,
  Stock_Level, Reorder_Point, Monthly_Sales, Unit_Cost, Date, **poze**,
  **depozit** (1=Arad, 2=Braila, 3=Pitesti).
- **comenzi** - ComandaID, ClientID, Data_comanda (datetime), Status, Total,
  Observatii.
- **comenzi_produse** - liniile comenzii: LinieID, ComandaID, Product_ID,
  Product_Name, Pret_unitar, Cantitate, Subtotal.
- **expedieri** - ExpediereID, **awb** (unic), ClientID, SoferID, RutaID,
  **LinieID** (linia de comanda expediata), Data_expediere (datetime),
  Data_livrare_estimata (datetime), Data_livrare_efectiva (datetime),
  Status_expediere, Valoare_expediere.

Depozitele firmei sunt 3 orase: **Arad, Braila, Pitesti**. Fiecare oras de client
are rute din toate cele 3 depozite.

## Pagini si functionalitati

### Back office (administrare)
- `index.php` - dashboard cu totaluri pe fiecare sectiune.
- CRUD generic (prin `_crud_page.php`): **clienti**, **soferi**, **rute**,
  **produse** (inventory), **comenzi**, **expedieri**.
  - Produse: upload poza + selector de depozit (locatie).
  - Rute: distanta, viteza, tip drum, timp de condus.
- `expediere_comanda.php` - **optimizare rute**: pentru o comanda, arata cele mai
  bune 3 rute per produs (ordonate dupa timp), operatorul alege una si se creeaza
  expedierea + AWB. Vezi `algoritm_optimizare_rute.md`.

### Client
- `magazin.php` + `cos.php` - catalog pe categorii, cos, plasare comanda.
- `portal_client.php` - **Login clienti**: clientul isi alege numele, introduce
  AWB-ul si vede statusul coletului (produs, km, timp, timp trecut in program,
  timp ramas estimat). Buton in bara de navigare langa Produse/Cos.

## Reguli de afisare
- Datele se afiseaza in **format european**: `DD.MM.YYYY` si `DD.MM.YYYY HH:MM`.
- Programul curierilor: **07:00-22:00**, toate zilele (inclusiv weekend). Timpii
  scursi/ramasi si data livrarii estimate se calculeaza doar in acest interval.

## Migrari (istoric schema)
Orice schimbare de schema trece printr-un fisier numerotat in `src/database/`:
`001`-`004` (fix-uri + comenzi), `005` Durata_min, `006` viteza, `007` tip_strada,
`008` awb + LinieID, `009` creata_la (inlocuita ulterior), `010` reset comenzi/
expedieri + date DATETIME. `seed_rute.sql` populeaza cele 18 rute (km din
`distanta_orase.xlsx`, timp din `timp_orase.xlsx`).

## Cum rulezi local
1. Porneste XAMPP (Apache + MySQL).
2. Baza `sameday_company` cu credentialele din `src/database/db_connection.php`
   (root, fara parola, `127.0.0.1`).
3. Deschide `http://localhost/CLAUDE/PRIMUL/index.php`.
4. Migrari noi: `mysql -u root sameday_company < src/database/0NN_*.sql`.
