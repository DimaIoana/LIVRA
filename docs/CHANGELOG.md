# Log de proiect - LIVRA

Jurnal cronologic al lucrului pe proiect. Cele mai recente sus.

## 2026-08-10 - Catalogul devine tabela lui: `produse`, cu stocul legat prin cheie straina
- Pana acum catalogul nu exista ca tabela: `inventory` tinea si produsul (nume,
  categorie, pret de vanzare, poza) si stocul lunar, deci identitatea produsului
  era copiata pe fiecare luna - 55 de randuri pentru 19 produse. Puteai schimba
  numele intr-o luna si nu in alta, iar magazinul trebuia sa ghiceasca produsul
  din "cel mai recent rand".
- Migrarea `src/database/026_catalog_produse.sql` desparte cele doua:
  - `produse` = catalogul, cate un rand pe produs. `ProdusID` e cheia tehnica,
    `Product_ID` (PRD-0001) e codul de business, **unic**. Contine Product_Name,
    Category, Unit_Cost (pretul de vanzare) si poza.
  - `inventory` = stocul, cate un rand pe produs si luna: Stock_Level,
    Reorder_Point, Monthly_Sales, Cost_Unitar, `Date`, depozit.
  - Legatura ceruta: **`inventory.Product_ID` = `produse.Product_ID`**, cheie
    straina reala (ON UPDATE CASCADE, ON DELETE RESTRICT). Nu mai poate exista
    stoc pentru un produs care nu e in catalog, nici doua produse cu acelasi cod.
  - `Cost_Unitar` ramane pe stoc: costul de achizitie chiar difera de la o
    luna/depozit la alta (18 din 19 produse aveau valori diferite), deci e
    istoric real. `Unit_Cost` era identic pe toate lunile fiecarui produs, deci
    a fost mutat in catalog.
  - Colatia lui `inventory.Product_ID` a trecut de la utf8 la utf8mb4, ca sa se
    potriveasca cu `produse` si cu `comenzi_produse`.
- Backend: `ProdusRepository` (nou) pentru catalog; `InventoryRepository` scrie
  numai coloanele de stoc si aduce datele produsului prin JOIN pe catalog;
  `MagazinRepository` construieste magazinul direct din `produse`, cu stocul luat
  din ultima luna (produs fara nicio luna de stoc = epuizat, nu dispare).
  `BaseRepository::quote()` accepta acum "tabela.coloana", pentru JOIN-uri.
- Frontend: **Catalog de produse** devine pagina CRUD reala - de acolo se adauga,
  se modifica si se sterg produsele, si tot ce e acolo apare in magazin.
  **Control de stocks** nu mai editeaza produsul: alegi produsul din catalog
  dintr-un dropdown si completezi doar cifrele lunii.
- `_crud_page.php` a primit `$config['intro']`, un carlig optional pentru
  continut intre bara de cautare si tabel (folosit de cifrele din catalog).
- Backup inainte de migrare:
  `src/database/backups/sameday_company_2026-08-10_inainte_de_026.sql`.

## 2026-08-10 - Catalog de produse, "Produse" devine "Control de stocks"
- `inventory` e istoric lunar de stoc, deci are doua numere diferite (55 de
  inregistrari pentru 19 produse). Pana acum o singura pagina le amesteca, si de
  aici impresia ca in magazin lipsesc produse. Acum sunt doua cutii separate:
  - **Catalog de produse** (`src/frontend/catalog_produse.php`, pagina noua) -
    cate un rand pe produs, cu pretul unitar de acum: cele 19 produse de vanzare.
    Doar de citit (produsele se modifica in Control de stocks, adaugandu-le luna
    noua), cu cautare, filtru pe categorie si sortare pe cod / nume / categorie /
    pret / stoc. Sortarea se face in PHP, nu in SQL: catalogul are zeci de randuri.
  - **Control de stocks** (`produse.php`, fostul "Produse") - toate cele 55 de
    inregistrari produs x luna, cu adaugare/editare/stergere, ca inainte.
- Cele doua sunt legate in ambele sensuri: din catalog, "Stoc lunar" duce la
  istoricul produsului (`produse.php?search=<cod>`); din Control de stocks, "In
  catalog" duce invers. Pe prima pagina cutiile stau una langa alta.
- Catalogul citeste din `MagazinRepository`, aceeasi sursa din care se construieste
  magazinul, ca back office-ul si magazinul sa nu poata arata liste diferite.
- Redenumit in meniul tuturor paginilor: `_crud_page.php`, `business.php`,
  `expediere_comanda.php`, `laborator_rute.php`, `test.php`.

## 2026-08-10 - Catalogul magazinului: alegerea randului curent
- `MagazinRepository::currentFrom()` alegea luna (`Date` = MAX(`Date`)), nu randul.
  Doua efecte, ambele silentioase: un produs cu `Date` necompletat (coloana e
  nullable, iar un import poate lasa NULL) **dispare complet din magazin**, iar
  un produs cu doua inregistrari in aceeasi luna apare **de doua ori**.
- Acum se alege randul: `ORDER BY Date IS NULL, Date DESC, InventoryID DESC LIMIT 1`
  - lunile completate inaintea celor NULL, cea mai recenta prima, iar la egalitate
  cea mai nou introdusa. Deci exact un rand pe produs, mereu.
- Aceeasi regula e folosita si de numaratoarea "sub prag" de pe prima pagina.

## 2026-08-04 - Asistent pentru clienti: adaugat si scos
- A existat cateva ore un asistent conversational pentru clienti (bula de chat pe
  paginile de front office, model Claude Haiku, baza de cunostinte proprie).
  **Scos la cererea userului in aceeasi zi** - nu mai era necesar.
- Sterse: `src/frontend/asistent.php`, `_asistent.php`, `_chat_widget.php`,
  `src/backend/AsistentClientService.php`, `config_ai.example.php`,
  `tools/test_asistent.php`, `docs/baza_cunostinte_client.md`, stilurile de chat
  din `app.css` si linkurile din meniul de magazin. Nu au ramas urme in cod.

## 2026-08-04 - Numele firmei: LIVRA, cu logo
- "PRIMUL" a devenit **LIVRA** peste tot in aplicatie si in documentatie. Caile
  de pe disc raman `C:\xampp\htdocs\CLAUDE\PRIMUL` (doar numele afisat s-a
  schimbat, nu si folderul).
- Logo-ul primit (`src/backend/imagini/logo livra.png`, 1536x1024) a fost decupat
  in doua variante mici, in `src/frontend/img/`:
  - `logo-livra-mark.png` - doar marca (coletul cu liniile de viteza), pusa in
    `.logo::before` din `app.css`. Fiind pe clasa comuna, apare automat in
    antetul tuturor paginilor, langa textul LIVRA;
  - `logo-livra.png` - logo-ul complet cu slogan, pe pagina de login.
- Decupajul s-a facut cu System.Drawing din PowerShell (GD nu e activat in
  php.ini), dupa marginile reale ale desenului, calculate din pixeli.

## 2026-08-04 - Login de back office cu parola criptata
- Pagina noua `src/frontend/admin_login.php`: ecran impartit, cu panoul firmei in
  stanga (logo, cifre din baza: curieri, rute, orase) si formularul in dreapta.
- Parolele nu se mai tin in clar: `users.parola` a devenit hash bcrypt
  (migrarea `025_users_parola_criptata.sql`), verificat cu `password_verify()`
  in `src/backend/UserRepository.php`. Nici din phpMyAdmin nu se mai pot citi.
- Toate paginile de back office (index, clienti, comenzi, expedieri, soferi,
  rute, produse, business, laborator, test) incep cu `cere_admin()` din
  `_auth.php` si arata cine e conectat, cu buton de iesire. Magazinul si
  urmarirea coletului au ramas publice.
- Aparari: acelasi mesaj la user gresit si la parola gresita, pauza de 60 s dupa
  5 incercari, `session_regenerate_id` la login, sesiune expirata dupa 2 h de
  inactivitate si intoarcere doar pe cai din proiect (fara redirect in afara).
- Conturi noi / parole schimbate: `php tools/hash_parola.php`.

## 2026-08-01 - Optimizat vs neoptimizat: doua grafice de bani in laborator
- Sectiune noua in `laborator_rute.php`, **"Performanta financiara: cu si fara
  optimizare"**: doua grafice cu bare verticale alaturate, fiecare cu **incasare,
  cost si profit**.
  - **Stanga - "Rute optimizate"**: cifrele reale, de pe rutele pe care au plecat
    coletele, cu costul de carburant inregistrat pe expediere.
  - **Dreapta - "Rute neoptimizate"**: aceleasi colete, dar duse fara algoritm -
    **media** tuturor rutelor catre orasul lor.
- Media, nu cel mai prost candidat: fara algoritm nu alegi anume varianta cea mai
  rea, ci una oarecare, deci media e rezultatul asteptat. Comparatia cu cel mai
  prost ar umfla castigul. Scrie asta pe pagina.
- Amandoua graficele impart **aceeasi scara**, altfel doua bare la fel de inalte ar
  insemna sume diferite si comparatia ar fi falsa.
- Pe datele de acum (96 de livrari): profit **80.452,30** optimizat vs
  **69.663,13** neoptimizat, adica **+10.789,17 lei (15,5%)** din optimizare.
  Incasarea e identica in ambele (135.550,80 lei) - vanzarea nu depinde de drum -
  deci toata diferenta vine din cost: **10.925,09 lei** carburant economisit,
  8.927 km mai putin de condus.
- `AnalizaRuteService::comparatieFinanciara()`. Pretul carburantului pentru
  varianta imaginara se deduce din expedierea reala (lei inregistrati / km
  parcursi), nu din API, ca ambele scenarii sa fie socotite la acelasi pret.
  Costul marfii se recalculeaza per depozit de plecare, fiindca fiecare depozit
  are alt cost de achizitie. Intra numai expedierile livrate, deci coloana din
  stanga da exact cifrele din Business dashboard - verificat.
- Helperii de scara (`scara_y`, `pas_rotund`, `scara_interval`, `inaltime`) au
  iesit din `business.php` in `_grafic_scara.php`, folosit acum de ambele pagini,
  in loc sa fie copiati. Adaugata clasa `.chart__bar--s3` (bara verde de profit).

## 2026-08-03 - Coloana "Depozit plecare" la comenzi
- Tabelul de comenzi arata de unde pleaca marfa fiecarei comenzi:
  - pe comenzile **deja expediate**, depozitele **reale**, luate din rutele
    expedierilor (`GROUP_CONCAT DISTINCT` pe `rute.Oras_origine`), ex.
    "Braila, Pitesti" cand liniile au plecat din depozite diferite;
  - pe cele care **n-au plecat inca**, depozitele pe care le-ar alege algoritmul,
    marcate cu "(estimat)", ca sa nu se confunde cu un fapt;
  - "Fara stoc" daca niciun depozit n-are produsele, "-" daca n-are linii.
- Coloana e sortabila. Depozitele reale vin din acelasi SELECT ca restul listei,
  deci nu costa nimic in plus; estimarea se calculeaza doar pentru comenzile
  neexpediate.
- `situatieFinanciara()` isi tine acum rezultatul intr-o memorie pe cerere:
  aceeasi comanda era intrebata de trei ori pe pagina (eticheta butonului,
  culoarea lui, coloana noua), iar calculul trece prin algoritmul de rute.

## 2026-08-03 - Semafor pe cutia Comenzi + coloana "Depozit" la produse
- Cutia **Comenzi** din pagina principala arata trei becuri colorate, cu numarul
  fiecarei stari:
  - **galben** - comenzi noi (status `Noua`), inca nedecise;
  - **rosu** - comenzi **pe pierdere**, adica toate cele care n-au plecat inca
    (`Noua`, `In procesare`, **`Anulata`**) si a caror estimare de profit e
    negativa. Anulatele intra dinadins: o comanda anulata fiindca pierdea bani
    tot pe pierdere ramane, si tocmai ea trebuie sa se vada;
  - **verde** - comenzi **acceptate** la trimitere: au decizia luata (rand in
    `comenzi_financiar`) sau sunt deja `Trimisa`.
- Becurile galben si rosu **licaresc** incet (1,6 s), fiindca cer o decizie; verdele
  sta linistit, e o stare incheiata. Animatia se opreste singura daca sistemul
  cere miscare redusa (`prefers-reduced-motion`).
- Culoarea nu e singurul semn: langa fiecare bec scrie si ce inseamna ("1 noi",
  "1 pe pierdere", "35 acceptate"). O stare cu zero nu apare deloc.
- Rosul cere estimarea de profit, care trece prin algoritmul de rute, deci se
  calculeaza doar pentru comenzile inca deschise (`ComandaRepository::deschise()`),
  nu pentru toate. Verdele e un singur COUNT (`numarAcceptate()`).
- La **Produse**, coloana "Locatie" se numeste acum **"Depozit"**, si in tabel si
  in formular (unde scria "Locatie (depozit)"). Cheia din baza de date era deja
  `depozit`, deci sortarea merge la fel.

## 2026-08-03 - Modulul se numeste "Business Intelligence si analiza de date"
- Redenumit peste tot unde se vede: cardul din pagina principala, titlul paginii
  (`<title>`), subtitlul din antet si titlul mare din pagina. Fisierul ramane
  `business.php`, iar linkul scurt din navigatie ramane "Business" - in bara de
  navigatie n-ar incapea numele intreg.
- Actualizata si descrierea de pe card, ramasa la trei rapoarte: acum spune ce
  contine cu adevarat (vanzari, cheltuieli si profit pe zi, comenzi pe oras,
  activitatea soferilor, raportul Power BI) si numara **9 rapoarte**.

## 2026-08-03 - 12 produse noi in magazin
- Migrarea **024**: catalogul creste de la 7 la **19 produse** (PRD-0008 ...
  PRD-0019), pe categoriile existente: 6 Electronics (Mechanical Keyboard,
  Wireless Headset, Webcam HD, Docking Station, External SSD 1TB, WiFi 6 Router),
  3 Furniture (Standing Desk, Filing Cabinet, Monitor Stand) si 3 Accessories
  (Mousepad XL, Laptop Bag, USB Hub 4 Port).
- Fiecare produs are **cate un rand pe fiecare depozit** (Arad, Braila, Pitesti),
  cu luni diferite - acelasi tipar ca produsele vechi. Asa magazinul arata un
  singur rand pe produs (cel mai recent), iar algoritmul de optimizare are toate
  cele trei depozite drept candidati. Verificat: pentru Standing Desk catre
  Cluj-Napoca ies 3 rute (Arad 268 km, Pitesti 326 km, Braila 568 km).
- Preturile stau in scara catalogului de acum (63 - 2.100 lei), iar costul de
  achizitie e 8-13% din pret, ca la produsele existente, si difera putin de la un
  depozit la altul.
- Produsele noi n-au poza: in magazin primesc placa colorata pe categorie. Se pot
  adauga poze oricand din back office, la Produse.

## 2026-08-01 - Decizia de trimitere s-a mutat la comenzi
- Butonul de profitabilitate a fost scos din **Expedieri** si pus in **Comenzi**:
  decizia "trimit sau anulez" se ia inainte ca marfa sa plece, nu dupa. In
  `expedieri` nu exista stare "inainte de plecare" - o expediere se naste direct
  "In tranzit".
- Butonul apare **doar pe comenzile inca deschise**: status `Noua` sau
  `In procesare` **si** nicio linie expediata. Pe cele `Trimisa` sau `Anulata`
  celula arata doar "-".
- Profitul e o **estimare**, fiindca inca nu exista expediere: pentru fiecare
  produs se ia ruta pe care ar alege-o algoritmul (prima din `ruteOptimizate`),
  cu carburantul la pretul de azi si marfa de la depozitul de plecare al acelei
  rute. Fereastra arata defalcarea pe produs: ruta, km, incasare, marfa,
  carburant, profit.
- **Trimite** inregistreaza cifrele in `comenzi_financiar` si trece comanda pe
  "In procesare" (aprobata pentru expediere; expedierile se creeaza mai departe
  din `expediere_comanda.php`). Idempotent.
- **Anuleaza** nu inregistreaza nimic: comanda trece pe "Anulata" si nu mai
  pleaca. Stocul nu se atinge - comanda n-a expediat nimic.
- Migrarea **023**: tabela `comenzi_financiar` si stergerea lui
  `expedieri_financiar` (ramasa nefolosita din 022). Statusul "Anulat" de la
  expedieri ramane - se poate pune din formularul de editare.
- Curatate din `ExpediereRepository` metodele ramase fara folos dupa mutare
  (`situatieFinanciara`, `financiarInregistrat`, `inregistreazaFinanciar`,
  `anuleaza`, `refaStoc`). `costMarfa` ramane, o foloseste fereastra de algoritm.
- `_crud_page.php`: eticheta goala de buton inseamna "randul asta n-are buton",
  deci o coloana de tip `modal` poate fi selectiva pe rand.

## 2026-08-01 - Decizie de trimitere pentru expedierile neprofitabile
- Coloana noua **Profit** in tabelul de expedieri, cu un buton pe fiecare rand:
  scrie **"Neprofitabil"** (rosu) cand coletul iese pe pierdere, **"Profitabil"**
  cand aduce bani, sau **"Anulata"** daca decizia a fost deja luata. Pe datele de
  acum: 43 neprofitabile din 97.
- Butonul deschide o fereastra cu situatia financiara (incasare, cost marfa, cost
  carburant, profit) si **doua optiuni**:
  - **Trimite** - inregistreaza cifrele in tabela noua `expedieri_financiar`,
    inghetate asa cum erau la momentul deciziei. Idempotent: a doua apasare
    rescrie acelasi rand, nu adauga altul.
  - **Anuleaza** - nu inregistreaza nimic. Expedierea trece pe statusul nou
    **"Anulat"**, iese din toate rapoartele financiare (care se uita doar la
    "Livrat"), i se goleste data livrarii efective si **marfa se pune la loc pe
    stoc** daca fusese scazuta la livrare.
- Migrarea **022**: `Anulat` adaugat la `Status_expediere` si tabela
  `expedieri_financiar` (cheie unica pe ExpediereID, stergere in cascada).
- **Bug gasit la test**: `ExpediereRepository::getById()` nu aducea coloana
  `stoc_scazut`, deci anularea nu punea marfa inapoi pe stoc. Coloana e acum in
  `selectFrom()`; verificat pe o expediere reala (stoc 14.727 -> 14.729 la
  anulare, inapoi la 14.727 dupa restaurare).
- `_crud_page.php` a primit doua mecanisme noi, refolosibile:
  - `rowModals` (in locul lui `rowModal`) - mai multe ferestre pe aceeasi pagina,
    fiecare cu coloana ei; eticheta si clasa butonului pot fi si functii de rand;
  - `rowActions` - actiuni POST proprii paginii, pe un rand, cu mesaj dupa redirect.

## 2026-08-01 - Coduri de produs incrementale (PRD-0001...)
- Migrarea **021**: codurile P001...P007 devin **PRD-0001...PRD-0007**. Prefix
  clar plus patru cifre, deci se poate creste pana la PRD-9999 fara sa-si schimbe
  forma. Maparea trece printr-un tabel temporar, ca ambele tabele sa primeasca
  exact aceleasi coduri.
- **Legaturile nu se pierd.** `Product_ID` nu are cheie straina, dar apare in doua
  tabele - `inventory` si `comenzi_produse` - si amandoua se schimba in aceeasi
  tranzactie. Restul merg pe alte chei si nu se ating: expedierile sunt legate prin
  `LinieID` de `comenzi_produse`, deci urmeaza automat. Verificat dupa rulare:
  98 de linii de comanda mapate, **0 linii orfane**, **0 expedieri** fara produs.
- La **creare**, campul "Cod produs" vine precompletat cu urmatorul cod liber
  (`InventoryRepository::codNou()`: cel mai mare numar folosit + 1, deci codurile
  nu se refolosesc nici dupa o stergere). Lasat gol, se genereaza tot asa.
- `inventory` ramane istoric lunar: acelasi produs are cate un rand pe luna, cu
  acelasi cod. Ca sa adaugi o luna noua la un produs existent, pui codul lui in
  loc de cel generat. Daca pui un cod luat de **alt** produs, adaugarea e oprita cu
  un mesaj care spune al cui e codul si care ar fi cel nou - altfel doua produse ar
  imparti un cod si n-ar mai putea fi deosebite. Editarea nu e afectata (altfel
  n-ai mai putea redenumi un produs).
- `_crud_page.php`: campurile accepta acum si o **functie** ca `default`, evaluata
  la deschiderea formularului. Se poate refolosi oriunde e nevoie de o valoare
  calculata din baza.
- `seed_comenzi_test.sql` a ramas in urma (cauta P001...P007) - marcat ca invechit
  in antet, cu trimitere la `tools/seed_comenzi_25_test.php`.

## 2026-08-01 - Stocuri aleatoare, toate peste 10.000
- Migrarea **020**: `inventory.Stock_Level` primeste o cifra aleatoare intre
  10.001 si 50.000 pe fiecare din cele 19 randuri (fiecare luna a fiecarui produs,
  in fiecare depozit). Verificat dupa rulare: minim 10.088, maxim 49.380, niciun
  rand sub 10.000.
- Trei efecte de stiut:
  - **"sub prag" a devenit 0** in back office - pragurile de reaprovizionare
    (`Reorder_Point`) au ramas la 10-100, deci nu mai poate fi atins nimic;
  - **toate depozitele au acum orice produs pe stoc**, deci algoritmul de
    optimizare are mereu toti candidatii si va alege ruta cea mai rapida.
    Procentul "pe ruta optima" din laborator (acum 50,5%) va creste pe masura ce
    apar expedieri noi - vechile 97 raman cum sunt;
  - magazinul nu mai respinge comenzi pentru stoc insuficient.
- Migrarea foloseste `RAND()`, deci la fiecare rulare ies alte cifre (mereu peste
  10.000). Stocurile de dinainte se pierd - erau oricum date de test.

## 2026-08-01 - Schema algoritmului, in pagina de laborator
- Poza pusa de user in `src/backend/imagini/schema algoritm.jpg` apare acum in
  `laborator_rute.php`, in cardul "Ce face algoritmul": pasii la stanga, schema
  la dreapta, pe o coloana de 260px (sub 860px latime trece sub pasi).
- Poza sta la 260px, nu pe toata latimea cardului - e un desen de citit dintr-o
  privire. Fiindca are text scris pe ea, e si link catre imaginea intreaga
  (fila noua), plus "Vezi schema mare" in legenda.
- E servita direct de Apache din folderul de backend, unde a pus-o userul; nu
  exista `.htaccess` care sa blocheze folderul, deci merge cu cale relativa.

## 2026-08-01 - Bani pe fiecare ruta, cu grafic sub tabelul KPI
- Tabelul KPI din fereastra "Algoritm" are randuri noi de bani: **cost marfa**,
  **cost total**, **incasare** si **profit** (verde/rosu dupa semn), plus un
  **grafic cu bare verticale** sub ele, cate unul pe coloana: cost, incasare,
  profit.
- Pe coloana **Selectata** sunt cifrele **reale** ale expedierii (incasarea si
  costul de carburant inregistrate la plecare, cu pretul motorinei de atunci);
  pe celelalte e o **simulare** a aceluiasi colet dus pe ruta aceea, cu marfa
  luata din depozitul ei si carburantul la pretul de azi. Scrie sub fiecare
  grafic: "cifre reale" / "simulare".
- Costul marfii difera de la o coloana la alta fiindca fiecare depozit are
  propriul cost de achizitie (`ExpediereRepository::costMarfa()`, cu revenire pe
  cel mai recent rand al produsului daca depozitul n-are stoc inregistrat).
- Toate graficele impart **aceeasi scara**, altfel doua coloane alaturate ar
  arata bare la fel de inalte pentru sume diferite. Cand profitul e negativ,
  scara are si partea de sub zero, iar bara coboara sub linia lui zero.
- `ExpediereRepository::selectFrom()` aduce si `LinieID`, de care depinde costul
  marfii. Fara expediere (fereastra deschisa din alta parte) se arata doar
  partea de timp.

## 2026-08-01 - Fereastra de algoritm, redusa la verdict + tabelul KPI
- Scoase sectiunile **"Cum se calculeaza timpul ajustat"** si **"Drum si
  carburant"**: amandoua vorbeau doar despre ruta curenta, iar cifrele lor
  (timp prestabilit, viteza, tip drum, vreme, km, motorina, cost) sunt deja in
  tabelul KPI, pe toate rutele deodata.
- Fereastra are acum trei lucruri: contextul expedierii, verdictul si tabelul KPI.
- Curatate din PHP variabilele ramase fara folos (`$criterii`, `$clasaAjustare`,
  `$litri`) si din CSS clasele `.alg__cifre` / `.alg__cifra` / `.alg__eticheta` /
  `.alg__valoare`, care nu mai erau pe nicio pagina.

## 2026-08-01 - Tabel KPI pe coloane in fereastra de algoritm
- Tabelul de comparatie din fereastra "Algoritm" e acum **pivotat**: o coloana
  pentru fiecare ruta candidata, cu **ruta scrisa in cap de coloana**
  (ex: "Braila → Iasi") si o eticheta care spune daca e **Selectata** (cea pe
  care a plecat coletul) sau **Normala**. Coloana selectata e evidentiata.
- Randurile sunt indicatorii: loc dupa algoritm, timp ajustat, diferenta fata de
  locul 1, timp prestabilit, viteza, tip drum, vreme, distanta, motorina si cost
  carburant. Asa se compara acelasi KPI intre rute citind pe orizontala.
- Verdictul de sus spune acum si timpul fiecarei rute, nu doar diferenta dintre
  ele ("Ruta asta face 292 min ... urmatoarea ar face 516 min, adica cu 224 min
  mai mult"), fiindca inainte cifra de diferenta se putea citi drept timp total.

## 2026-08-01 - Coloana "Algoritm" mutata la expedieri
- Coloana **Algoritm** a fost scoasa din pagina **Rute** si pusa in pagina
  **Expedieri**, unde intrebarea "de ce s-a ales traseul asta" are un raspuns
  concret: coletul chiar a plecat pe el.
- Fereastra incepe acum cu contextul expedierii (AWB, traseu, data plecarii,
  soferul, data estimata si cea efectiva), apoi urmeaza aceleasi lucruri ca
  inainte: verdictul (castigatoare / locul N), calculul timpului ajustat criteriu
  cu criteriu, drumul si carburantul, si clasamentul rutelor catre acelasi oras.
- `_algoritm_ruta.php` primeste acum ruta in `$rutaRand` si, optional, expedierea
  in `$expediere`, deci poate fi refolosit si din alta pagina.
- Lista tipurilor de drum a ajuns constanta unica, `RutaRepository::TIPURI_STRADA`,
  in loc sa fie scrisa in fiecare pagina care o afiseaza.

## 2026-08-01 - Coloana "Algoritm" la rute
- Pagina **Rute** are o coloana noua **Algoritm**, cu un buton "Explica" pe fiecare
  rand. Butonul deschide o fereastra care arata de ce ar fi (sau nu ar fi) aleasa
  ruta aceea:
  - **verdictul**: "Ruta castigatoare" sau "Locul N", cu cate minute pierde fata
    de prima si mentiunea ca se foloseste doar daca depozitul castigator n-are
    produsul pe stoc;
  - **calculul timpului ajustat**, criteriu cu criteriu (timp prestabilit, viteza,
    tip drum, vreme in timp real), cu regula fiecaruia si minutele adaugate sau
    scazute - rosu cand incetinesc, verde cand ajuta;
  - **drum si carburant**: km, litri de motorina (12 L/100 km) si costul, la
    pretul curent al motorinei;
  - **toate rutele catre acelasi oras**, ordonate dupa timp ajustat, cu ruta
    curenta evidentiata - asa se vede direct pe ce loc iese.
- `OptimizareRuteService::explicaRuta()` face calculul si clasamentul, folosind
  exact aceleasi ajustari ca la expedierea reala. Continutul ferestrei sta in
  `src/frontend/_algoritm_ruta.php`.
- `_crud_page.php` a primit un mecanism generic `rowModal`: o coloana de tip
  `modal` pune butonul pe fiecare rand, iar pagina isi tipareste continutul
  ferestrei printr-un callback. Se poate refolosi la orice alt tabel.

## 2026-08-01 - Laborator: algoritmul de optimizare rute si performanta lui
- Cutie noua in back office (`index.php`) si pagina noua `laborator_rute.php`:
  ce decide algoritmul, dupa ce criterii, si cat de bine a ales pe cursele deja
  plecate. Legata si din navigatie ("Laborator").
- Explicatia are pasii algoritmului (intrare, candidati, scor, ordonare, decizia
  operatorului, iesire) si cele **4 criterii** care compun timpul ajustat.
  Pragurile nu sunt scrise de mana: se obtin chemand
  `OptimizareRuteService::ajustareViteza()` / `ajustareTip()`, deci pagina nu poate
  ramane in urma daca se schimba regulile in cod.
- "Algoritmul, rulat acum": ruleaza efectiv `ruteOptimizate()` pe un produs si un
  oras, cu vremea in timp real, si arata clasamentul cu toate ajustarile.
- Performanta, masurata pe cele 97 de curse din baza: **50,5%** au plecat pe ruta
  cea mai rapida catre orasul lor; **36.145 min** economisiti fata de alegerea
  celui mai prost candidat; **13.036 km** in plus fata de ruta optima
  (1.564 L, 16.628,72 lei). Tabel pe orase, plus toate rutele grupate pe destinatie.
- Cifra de km in plus e explicata pe pagina ca fiind **costul asezarii marfii in
  depozite**, nu o greseala a algoritmului: el alege doar dintre depozitele care
  aveau produsul pe stoc.
- Masurarea foloseste doar partea determinista a scorului (prestabilit + viteza +
  drum). Vremea e luata in timp real la expediere si nu se salveaza, deci nu se
  poate reconstitui; altfel aceeasi cursa ar da alt rezultat la fiecare incarcare.
- Constatare pe datele de acum: in toate cele 6 orase ruta cea mai rapida e si cea
  mai scurta, deci criteriul de timp nu costa carburant - dar e o proprietate a
  datelor, nu o garantie a algoritmului.
- `AnalizaRuteService` (doar citire) + `css/laborator.css`. Fara schimbari de schema.

## 2026-08-01 - Rapoartele lungi se pliaza sub un buton
- **"Cat de folosit e fiecare traseu"** si **"Cursele soferilor"** stau acum sub
  cate un buton ("Arata traseele" / "Arata cursele"), **inchise la incarcarea
  paginii**. Butonul arata si cat e inauntru (18 trasee, 97 curse), iar eticheta
  si sageata se schimba cand se deschide.
- Comutarea se face cu `details`/`summary`, deci fara JS si accesibil de la
  tastatura. Clasa comuna `.pliant` in `business.css`; tabelul "Vezi cifrele" al
  traseelor ramane inauntru, ca al doilea nivel.
- Bara de derulare orizontala se pune numai pe continutul care e doar tabel: un
  container care taie pe orizontala ar taia si tooltipurile barelor.

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
