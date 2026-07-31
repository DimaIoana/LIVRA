# Log de proiect - PRIMUL

Jurnal cronologic al lucrului pe proiect. Cele mai recente sus.

## 2026-07-31 - Radar: cine a fost cel mai profitabil sofer
- Raport nou in Business dashboard, inainte de cardul Power BI: **grafic radar**
  cu o axa pentru fiecare sofer si o singura serie - **profitul** adus pe traseele
  lui (vanzari - marfa - carburant, doar expedieri livrate).
- Scara radiala merge de la o valoare rotunda sub cel mai slab rezultat pana peste
  cel mai bun, in 5 trepte (nu 4 ca la restul graficelor: pe radar forma
  poligonului e mesajul, deci merita o scara mai stransa). Cand pe grafic incap si
  valori negative, inelul lui **zero** se ingroasa - ce cade in interiorul lui e
  pierdere; cand toti sunt pe plus, centrul e zero si scrie asta in descriere.
- Langa panza sta si **clasamentul** (nume, baza, livrari, profit), cu primul loc
  evidentiat, plus tabelul complet cu vanzari / marfa / carburant / profit. Radarul
  singur nu poate fi citit exact, deci cifrele sunt mereu la vedere.
- `BusinessRepository::profitSoferi()`: acelasi mod de calcul al costului marfii ca
  la `cheltuieliPeZi()`, deci totalul pe soferi = totalul pe zile. Ordonarea dupa
  profit se face in PHP, fiindca profitul se compune abia dupa query.
  Fara schimbari de schema.

## 2026-07-29 - Utilizarea traseelor si cursele soferilor
- Doua rapoarte noi in Business dashboard, amandoua pe toata latimea:
  **"Cat de folosit e fiecare traseu"** (bare orizontale) si **"Cursele soferilor"**
  (tabel: sofer, traseu, kilometri).
- Graficul de trasee arata cate curse au plecat pe fiecare traseu, ordonate
  descrescator, deci cel mai folosit e primul si cel mai putin folosit e ultimul.
  Traseele pe care n-a plecat nimeni raman in lista, cu bara goala si eticheta
  "Nefolosit" - fara ele n-ar exista raspuns la "care e cel mai putin folosit".
- Extremele se marcheaza cu o **eticheta scrisa** ("Cel mai folosit" / "Nefolosit"),
  nu cu o culoare de bara: culoarea ar ajunge sa poarte rangul, care se schimba la
  fiecare cursa noua, iar diferenta s-ar pierde alb-negru. Toate barele raman pe
  `--s1`. Cand mai multe trasee sunt la egalitate, toate primesc eticheta.
- Se numara toate expedierile, nu doar cele livrate: drumul e facut si daca
  coletul s-a intors (aceeasi regula ca la activitatea soferilor).
- Tabelul curselor are un rand pe expediere, grupat pe sofer si cronologic; numele
  soferului se scrie o singura data pe grup, iar linia de deasupra desparte
  grupurile. Km-ii sunt ai rutei, deci o cursa dus, fara intoarcere.
- `BusinessRepository::utilizareTrasee()` (LEFT JOIN `rute` -> `expedieri`, ca sa
  ramana si traseele cu zero curse) si `BusinessRepository::curseSoferi()`.
  Fara schimbari de schema.

## 2026-07-29 - Comenzile existente, rescrise la preturile curente
- Migrarea **019** (cerere explicita): tot istoricul trece la pretul de vanzare
  curent din `inventory.Unit_Cost`. Se rescriu `comenzi_produse.Pret_unitar` si
  `Subtotal`, `comenzi.Total` (suma liniilor) si `expedieri.Valoare_expediere`
  (subtotalul liniei dusa de expediere). 98 de linii, 36 de comenzi, 97 de expedieri.
- Asta e opusul regulii obisnuite: pana acum o comanda isi pastra pretul de la
  momentul plasarii, de aia 017 si 018 nu miscau rapoartele pe zile deja livrate.
  Acum se recalculeaza toate zilele. **Pretul cu care s-a vandut efectiv fiecare
  comanda se pierde** - revenirea se poate face doar din backup.
- Migrarea e idempotenta: scrie mereu pretul curent, nu unul inmultit.
- Business dashboard: vanzari 42.643,60 -> **135.550,80 lei**, profit
  -12.454,90 -> **+80.452,30 lei**. Toate cele 13 zile cu livrari au acum profit
  pozitiv. Costurile nu s-au schimbat (produse 16.155,48 / carburant 38.943,02).

## 2026-07-29 - Pret de vanzare mai mare cu 200%
- Migrarea **018**: `inventory.Unit_Cost` (pretul din magazin) se tripleaza (x3.00),
  peste cresterea de 40% din 017. Costul de achizitie ramane neschimbat, deci
  costul ajunge la 8%-13% din pret (era 23%-39%).
- Ex: Laptop Pro 840 -> 2.520 lei, Monitor 27 350 -> 1.050 lei, pc ioana
  1.680 -> 5.040 lei, USB-C Cable 7 -> 21 lei.
- Business dashboard-ul ramane neschimbat (vanzari 42.643,60 lei, profit
  -12.454,90 lei): comenzile deja plasate isi pastreaza `Pret_unitar`/`Subtotal`,
  iar expedierile `Valoare_expediere`. Pretul nou se vede abia la comenzi noi.

## 2026-07-29 - 25 de comenzi noi, cu tot procesul pana la livrare
- `tools/seed_comenzi_25_test.php`: 25 de comenzi pe clienti aleatori, fiecare cu
  **2-4 produse** (78 de linii in total, 36.300,60 lei). Nu scrie direct in tabele -
  cheama `MagazinRepository::creeazaComanda()`, adica exact metoda cosului din
  magazin, deci verificarea de stoc, preturile curente si totalurile sunt identice
  cu o comanda pusa de un client real.
- Datele comenzilor se imprastie pe ultimele 14 zile (16.07 - 29.07), in orele de
  program, altfel toate ar cadea azi si graficele pe zile n-ar avea ce arata.
  Cantitatile sunt limitate la 60% din stocul curent al fiecarui produs, ca sa
  ramana stoc de plecare pentru expedieri.
- Procesul de expediere s-a facut cu `tools/seed_expedieri_test.php`, nemodificat:
  78 de expedieri noi pe ruta cea mai optimizata (AWB, sofer, cost carburant),
  datele aliniate la comanda, 79 marcate livrate (cu scaderea stocului) si cele 25
  de comenzi trecute pe "Trimisa". Total acum: 97 de expedieri, 96 livrate, 1 in tranzit.
- Efect in Business dashboard: vanzari 4.900 -> **42.643,60 lei**, carburant
  7.484,69 -> **38.943,02 lei**, profit -4.947,13 -> **-12.454,90 lei**. Raportul
  vanzari/carburant se imbunatateste (de la 0,65 la 1,10), fiindca noile comenzi au
  mai multe produse si preturile de dupa migrarea 017 - dar fiecare linie de comanda
  pleaca tot ca expediere separata, cu drumul ei, deci pierderea in valoare absoluta
  creste. Pe grafic se vad acum si zile cu profit pozitiv.

## 2026-07-29 - Raportul Power BI, la sfarsitul dashboard-ului
- Sectiune noua la finalul paginii `business.php`: raportul Power BI publicat de
  user, adus in pagina intr-un `iframe` (70% din inaltimea ecranului, minim 520px).
- Adresele stau in doua constante la inceputul fisierului: `PBI_EMBED` (adresa de
  incorporare `reportEmbed`, fiindca adresa normala e refuzata in iframe) si
  `PBI_LINK` (deschidere intr-o fila noua, sub raport).
- Asa cum e acum, raportul cere vizitatorului sa fie logat in Power BI cu drept pe
  raport. Ca sa se vada fara cont: in Power BI, File -> Embed report -> Publish to
  web (public) si se pune adresa `app.powerbi.com/view?r=...` in `PBI_EMBED`
  (atentie: face raportul public pentru oricine are linkul).

## 2026-07-29 - Activitatea soferilor (km, timp de condus, carburant)
- Raport nou in Business dashboard: **"Activitatea soferilor"** - un rand pe sofer,
  cu trei coloane de bare orizontale: kilometri, timp de condus si carburant
  consumat (litri). Cardul ocupa toata latimea, sub celelalte patru rapoarte.
- Cele trei masuri au unitati diferite, deci **nu** stau pe aceeasi axa: fiecare
  coloana are scara ei, dusa de maximul ei, iar randurile pastreaza aceeasi ordine
  (dupa km) in toate trei, ca soferii sa se poata compara de la un panou la altul.
  Cifrele si barele stau in coloane separate de grila, deci capetele barelor raman
  aliniate indiferent cat de lunga e cifra de langa ele.
- Spre deosebire de rapoartele de bani, aici intra si expedierile **in tranzit**:
  drumul e facut si carburantul e ars din clipa plecarii. Soferii fara expedieri
  raman in lista, cu zero.
- Litrii se calculeaza din km cu `OptimizareRuteService::CONSUM_L_100KM` (12 L/100 km);
  costul in lei e cel inregistrat pe expediere (`expedieri.cost_carburant`) si apare
  in tooltip si in tabelul cu cifrele.
- `BusinessRepository::activitateSoferi()` face un singur query cu LEFT JOIN pe
  `expedieri` + `rute`. Fara schimbari de schema.

## 2026-07-29 - Pret de vanzare mai mare cu 40%
- Migrarea **017**: `inventory.Unit_Cost` (pretul afisat in magazin) creste cu
  40%; costul de achizitie ramane neschimbat, deci costul scade la 23%-39% din
  pret. Ex: Laptop Pro 600 -> 840 lei, Monitor 27 250 -> 350 lei.
- Pretul nou se aplica **doar comenzilor viitoare**: `comenzi_produse` pastreaza
  `Pret_unitar`/`Subtotal` de la momentul comenzii, iar expedierile pastreaza
  `Valoare_expediere`. Business dashboard-ul arata deci aceleasi vanzari pentru
  zilele deja livrate - se va misca pe masura ce apar comenzi noi.

## 2026-07-29 - Inca 20% jos la costul de achizitie
- Migrarea **016**: a doua scadere de 20%, peste cea din 015 (cerere explicita).
  Cumulat, costul e la 64% din cel initial (-36%): intre ~32% si ~55% din pretul
  de vanzare, care ramane neschimbat.
- In Business dashboard: cheltuielile de produse 2.953,10 -> **2.362,44 lei**,
  pierderea -5.537,79 -> **-4.947,13 lei**. Carburantul (7.484,69 lei) ramane
  singurul motiv al pierderii - e mai mare decat toate vanzarile la un loc.

## 2026-07-29 - Cost de achizitie mai mic cu 20%
- Migrarea **015**: `inventory.Cost_Unitar` scazut cu 20% pentru toate produsele
  (pretul de vanzare `Unit_Cost` ramane neschimbat), deci marja creste - costul
  e acum intre 40% si 68% din pret, in loc de 50%-85%.
- Efect in Business dashboard: cheltuielile de produse scad de la 3.691,32 lei la
  **2.953,10 lei**, iar pierderea se subtiaza de la -6.276,01 la **-5.537,79 lei**.
  Rapoartele citesc costul curent din `inventory`, deci si zilele deja livrate se
  recalculeaza cu noul cost.

## 2026-07-29 - Business financiar (grafic cu linii) + doua rapoarte pe rand
- Raport nou **"Business financiar"**: grafic cu linii cu trei serii pe zi -
  **vanzari**, **cost** (produse + carburant) si **profit** (vanzari - cost),
  de la prima zi cu livrari pana in prezent. Scara axei cuprinde si valorile
  negative, cu linia lui zero pe o gradatie. Reper vertical si tooltip cu toate
  cele trei cifre la trecerea peste o zi; legenda arata si totalul fiecarei serii.
- Liniile se deseneaza cu SVG inline (`polyline`, `preserveAspectRatio="none"`,
  `vector-effect: non-scaling-stroke`), punctele sunt HTML ca sa ramana rotunde.
  Tot server-side, fara JS.
- **Profit** aparea acum si intre cifrele de sus, verde sau rosu dupa semn.
- Zilele fara livrari se completeaza cu zero in toate rapoartele pe zile: pe o
  axa de timp o zi lipsa ar lipi doua zile care nu sunt vecine.
- Rapoartele stau cate doua pe rand, fiecare pe jumatate de pagina (sub 1100px
  latime trec unul sub altul).

## 2026-07-29 - Business dashboard (modul de rapoarte)
- Modul nou in back office (`business.php`, link din pagina principala si din
  navigatie): trei rapoarte pe o singura pagina, sub titlul "Business dashboard".
  1. **Evolutia vanzarilor pe zi** - bare verticale, valoarea expedierilor livrate.
  2. **Cheltuieli produse si carburant pe zi** - bare verticale grupate (doua serii).
  3. **Numarul de comenzi pe oras** - grafic tort, cu procente in legenda.
- Rapoartele de bani se calculeaza **numai pe expedierile livrate**, grupate dupa
  ziua livrarii efective. Costul produselor = cantitatea din linia de comanda x
  `inventory.Cost_Unitar` de la depozitul de plecare al rutei.
- `BusinessRepository` (doar citire) tine cele trei interogari agregate.
- Graficele sunt desenate din HTML + CSS (`business.css`): inaltimi in procente
  si `conic-gradient` pentru tort, calculate in PHP. Fara JS si fara librarii.
  Fiecare grafic are titlu, etichete pe axe, legenda, tooltip la hover si tabelul
  cu cifrele sub el.

## 2026-07-28 - Expedieri pentru toate comenzile de test
- `tools/seed_expedieri_test.php`: creeaza expedierea lipsa pentru fiecare linie de
  comanda (comenzile anulate se sar), pe ruta cea mai optimizata, folosind exact
  clasele din back office (AWB, cost carburant, scaderea stocului la livrare).
- Datele expedierii se aliniaza la comanda (pleaca la 2 h de program dupa comanda),
  altfel toate ar pleca "acum" desi comenzile sunt din zilele trecute. Cele cu
  livrarea estimata deja trecuta devin "Livrat"; comanda complet expediata trece
  pe "Trimisa".

## 2026-07-28 - Comenzi de test + comenzi mai clare in back office
- `seed_comenzi_test.sql`: 10 comenzi de test pe 10 clienti diferiti, cu produse si
  cantitati variate si toate statusurile (Noua, In procesare, Trimisa, Anulata).
  Preturile vin din inventory, totalul se recalculeaza din liniile comenzii.
- Back office comenzi: coloana **Produse** (ce s-a comandat, cu cantitati), coloana
  **Expediat** (Neexpediata / x din y linii / Complet) si cautare si dupa produs
  (nume sau cod). Totalul se evidentiaza daca nu corespunde sumei liniilor.

## 2026-07-28 - Cos separat pentru fiecare client
- **Bug**: cosul statea in `$_SESSION['cos']`, deci era comun pe browser - cand se
  loga alt client, vedea produsele lasate de cel dinainte.
- Cosul e acum legat de clientul logat: `$_SESSION['cosuri'][ClientID]`. Fara client
  logat nu exista cos. Cosul vechi se sterge automat la prima incarcare.
- Login-ul regenereaza id-ul de sesiune (protectie session fixation).

## 2026-07-28 - Cost carburant pe ruta
- Se calculeaza costul de motorina al fiecarei rute (`km/100 x consum x pret`) si se
  salveaza in expediere (coloana `cost_carburant`, migrarea 014). Consum mediu duba
  = 12 l/100 km; pretul motorinei standard vine din API-ul `pretcarburant.ro`
  (`CarburantService`, cache 6 h). Costul per ruta se vede in back office la alegere.
  Atribuire: Sursa: PretCarburant.ro.

## 2026-07-28 - Pret unitar vs cost unitar la produse
- La produse (`inventory`), coloana `Unit_Cost` e redenumita in back office
  **"Pret unitar"** (asa e folosita deja: pretul afisat clientului in magazin).
- Coloana noua **`Cost_Unitar`** = costul de achizitie pe firma, optionala
  (migrarea 012). Apare in tabel si in formular, sortabila.
- Costurile existente au fost completate aleator, intre 50% si 85% din pret
  (migrarea 013), deci costul e mereu sub pret.

## 2026-07-28 - Login client + scadere stoc la livrare
- **Login client** (`login.php`): pentru a cumpara, clientul se logheaza alegandu-si
  numele din lista (fara parola, doar clientii existenti). Magazinul si cosul cer
  autentificare; checkout-ul foloseste clientul logat (fara dropdown). Sesiune +
  buton de iesire. Helperi in `_shop.php` (`client_login/logat/logout`, `cere_login`).
- **Scadere stoc la livrare**: cand o expediere devine "Livrat", cantitatea livrata
  se scade automat din `inventory` (produsul, la depozitul de plecare al rutei).
  Idempotent, printr-un flag `stoc_scazut` (migrarea 011), ca sa nu se scada de doua ori.

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
