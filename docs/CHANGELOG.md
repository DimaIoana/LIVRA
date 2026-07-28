# Log de proiect - PRIMUL

Jurnal cronologic al lucrului pe proiect. Cele mai recente sus.

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
