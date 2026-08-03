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
- **Exceptii API (autorizate in scris), consumate server-side de PHP:** vremea din
  API-ul ANM (`meteoromania.ro`) si pretul carburantului din `pretcarburant.ro`
  (atribuire: Sursa: PretCarburant.ro) - vezi optimizarea de rute.

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
  Stock_Level, Reorder_Point, Monthly_Sales, Unit_Cost (pretul unitar de vanzare,
  afisat in magazin), **Cost_Unitar** (costul de achizitie, optional), Date,
  **poze**, **depozit** (1=Arad, 2=Braila, 3=Pitesti).
- **comenzi** - ComandaID, ClientID, Data_comanda (datetime), Status, Total,
  Observatii.
- **comenzi_produse** - liniile comenzii: LinieID, ComandaID, Product_ID,
  Product_Name, Pret_unitar, Cantitate, Subtotal.
- **expedieri** - ExpediereID, **awb** (unic), ClientID, SoferID, RutaID,
  **LinieID** (linia de comanda expediata), Data_expediere (datetime),
  Data_livrare_estimata (datetime), Data_livrare_efectiva (datetime),
  Status_expediere, Valoare_expediere, **cost_carburant** (costul de motorina al
  rutei, lei).

Depozitele firmei sunt 3 orase: **Arad, Braila, Pitesti**. Fiecare oras de client
are rute din toate cele 3 depozite.

## Pagini si functionalitati

### Back office (administrare)
- `index.php` - dashboard cu totaluri pe fiecare sectiune.
- CRUD generic (prin `_crud_page.php`): **clienti**, **soferi**, **rute**,
  **produse** (inventory), **comenzi**, **expedieri**.
  - Produse: upload poza + selector de depozit.
  - Rute: distanta, viteza, tip drum, timp de condus.
  - Expedieri: coloana **Algoritm**, cu un buton "Explica" pe fiecare rand.
    Deschide o fereastra cu contextul expedierii (AWB, sofer, date), verdictul
    (ruta castigatoare sau locul N), descompunerea timpului ajustat criteriu cu
    criteriu, km / litri / cost carburant si clasamentul rutelor catre acelasi
    oras - deci de ce a ajuns coletul pe traseul acela
    (`_algoritm_ruta.php`, `OptimizareRuteService::explicaRuta()`).
  - Comenzi: coloana **Produse** (cantitate x nume), **Expediat** (cate linii din
    comanda au deja expediere) si **Depozit plecare** (depozitele reale pe
    comenzile expediate, cele estimate de algoritm pe cele care n-au plecat inca);
    cautarea merge si dupa produs.
- `expediere_comanda.php` - **optimizare rute**: pentru o comanda, arata cele mai
  bune 3 rute per produs (ordonate dupa timp), operatorul alege una si se creeaza
  expedierea + AWB. Vezi `algoritm_optimizare_rute.md`.
- `business.php` - **Business Intelligence si analiza de date**: rapoarte cu grafice desenate din
  HTML + CSS + SVG inline (fara JS), pe baza lui `BusinessRepository`. Primele
  patru stau cate doua pe rand, restul pe toata latimea:
  1. evolutia vanzarilor pe zi (bare verticale);
  2. cheltuielile de produse si carburant pe zi (bare grupate, doua serii);
  3. numarul de comenzi pe oras (grafic tort);
  4. **Business financiar** - vanzari, cost si profit pe zi (grafic cu linii,
     scara cuprinde si valorile negative);
  5. activitatea soferilor (km, timp de condus, carburant - bare orizontale);
  6. **cat de folosit e fiecare traseu** - cate curse au plecat pe fiecare traseu
     (bare orizontale, ordonate descrescator); traseele nefolosite raman in lista,
     cu bara goala, iar extremele sunt marcate cu eticheta scrisa, nu prin culoare;
  7. **cursele soferilor** - tabel cu un rand pe expediere: soferul, traseul si
     kilometrii traseului, grupat pe sofer si cronologic;
  8. **cine a fost cel mai profitabil sofer** - grafic radar cu o axa pe sofer si
     o serie (profitul adus pe traseele lui), cu clasament si tabel alaturi;
  9. **raportul Power BI** publicat, incorporat la sfarsitul paginii.
  Rapoartele de bani iau in calcul **numai expedierile livrate**, pe ziua livrarii
  efective; costul produselor = cantitate x `Cost_Unitar` (depozitul de plecare);
  profitul = vanzari - (cost produse + cost carburant). Zilele fara livrari apar
  cu zero, ca axa de timp sa fie continua. Sub fiecare grafic se poate deschide
  tabelul cu cifrele.
  Adresa raportului Power BI sta in constantele `PBI_EMBED` / `PBI_LINK` din
  `business.php`; asa cum e acum, cere vizitatorului cont Power BI cu drept pe
  raport (vezi comentariul de acolo pentru varianta publica).

### Client
- `login.php` - **login client**: pentru a cumpara, clientul se logheaza alegandu-si
  numele din lista (fara parola, doar clientii existenti). Magazinul si cosul cer
  autentificare; checkout-ul foloseste clientul logat.
- `magazin.php` + `cos.php` - catalog pe categorii, cos, plasare comanda (necesita login).
  Cosul e legat de clientul logat (`$_SESSION['cosuri'][ClientID]`), deci doi clienti
  care se logheaza pe acelasi browser nu isi vad cosul unul altuia.
- `portal_client.php` - **urmarire colet**: clientul isi alege numele, introduce
  AWB-ul si vede statusul coletului (produs, km, timp, timp trecut in program,
  timp ramas estimat).

## Reguli de afisare
- Datele se afiseaza in **format european**: `DD.MM.YYYY` si `DD.MM.YYYY HH:MM`.
- Programul curierilor: **07:00-22:00**, toate zilele (inclusiv weekend). Timpii
  scursi/ramasi si data livrarii estimate se calculeaza doar in acest interval.

## Cost carburant pe ruta
Costul de motorina al fiecarei rute se calculeaza si se salveaza in expediere
(`cost_carburant`) la crearea ei din optimizare:
`cost = distanta_km / 100 x consum x pret_motorina`, unde consumul mediu al dubei e
**12 l/100 km** (`OptimizareRuteService::CONSUM_L_100KM`) si pretul motorinei
standard vine din API-ul `pretcarburant.ro` (`CarburantService`, cache 6 h,
fallback la un pret implicit). Costul per ruta se vede si in back office la alegerea
rutei. Atribuire: "Sursa: PretCarburant.ro (https://pretcarburant.ro)".

## Stoc la livrare
Cand o expediere devine **"Livrat"**, cantitatea livrata se scade automat din
`inventory` (produsul, la depozitul de plecare al rutei). Scaderea se face o
singura data (flag `expedieri.stoc_scazut`), la salvarea expedierii cu statusul
Livrat din back office (`expedieri.php`).

## Migrari (istoric schema)
Orice schimbare de schema trece printr-un fisier numerotat in `src/database/`:
`001`-`004` (fix-uri + comenzi), `005` Durata_min, `006` viteza, `007` tip_strada,
`008` awb + LinieID, `009` creata_la (inlocuita ulterior), `010` reset comenzi/
expedieri + date DATETIME, `011` stoc_scazut, `012`-`013` Cost_Unitar la produse,
`014` cost_carburant la expedieri, `022` statusul "Anulat" la expedieri,
`023` tabela `comenzi_financiar` (decizia de trimitere) si stergerea lui
`expedieri_financiar`.

Fisiere de **date**, nu de schema, care nu se ruleaza de doua ori: `015` si `016`
Cost_Unitar scazut cu 20% de doua ori (cumulat -36%), `017` si `018` Unit_Cost
crescut cu 40% si apoi cu 200%, `019` comenzile existente rescrise la preturile
curente (idempotenta), `020` stocuri aleatoare peste 10.000, `021` coduri de produs
`PRD-0001`..., `024` 12 produse noi in catalog (7 -> 19 produse).

`seed_rute.sql` populeaza cele 18 rute (km din `distanta_orase.xlsx`, timp din
`timp_orase.xlsx`). `seed_comenzi_test.sql` e invechit (cauta coduri `P001`);
pentru comenzi de test se foloseste `tools/seed_comenzi_25_test.php`.

## Cum rulezi local
1. Porneste XAMPP (Apache + MySQL).
2. Baza `sameday_company` cu credentialele din `src/database/db_connection.php`
   (root, fara parola, `127.0.0.1`).
3. Deschide `http://localhost/CLAUDE/PRIMUL/index.php`.
4. Migrari noi: `mysql -u root sameday_company < src/database/0NN_*.sql`.
