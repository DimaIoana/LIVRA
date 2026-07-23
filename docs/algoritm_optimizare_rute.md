# Algoritm de optimizare a rutelor

Specificatie pentru functionalitatea de optimizare a rutelor + generare automata de
expedieri si AWB la plasarea unei comenzi.

## 1. Scop si declansare

Cand o comanda este inregistrata in tabela `comenzi` (din magazin), sistemul trebuie:

1. sa ruleze automat algoritmul de optimizare a rutelor;
2. sa creeze expedierile corespunzatoare;
3. sa genereze un numar **AWB** unic pentru fiecare expediere;
4. sa prezinte in back office cele mai bune 3 rute (de la cea mai optimizata la cea
   mai putin optimizata), cu explicatia pentru fiecare.

**Cea mai optimizata ruta = cea cu cel mai mic timp** (timpul ajustat, vezi mai jos).

## 2. Date de intrare

Pentru fiecare produs cumparat dintr-o comanda se iau in calcul:

- **orasul clientului** — `clienti.Oras` (prin `comenzi.ClientID`);
- **depozitele care au produsul** — `inventory.depozit` distinct pentru acel
  `Product_ID` (1 = Arad, 2 = Braila, 3 = Pitesti). Un produs poate fi in mai multe
  depozite, deci pot exista pana la 3 rute candidate.

Pentru fiecare pereche (depozit -> oras client) exista o **ruta** in tabela `rute`,
cu: `Distanta_km`, `Durata_min` (timpul de rute prestabilit), `viteza`, `tip_strada`.

## 3. Reguli de ajustare a timpului

Se porneste de la **timpul de rute prestabilit** (`rute.Durata_min`) si se aplica
doua ajustari: dupa viteza si dupa tipul de drum.

### 3.1. Dupa viteza (`rute.viteza`)

Pe intervale, ca sa acopere si vitezele multiplu de 5 (50, 55, 65, 75...):

| Viteza (km/h) | Ajustare timp |
|---------------|---------------|
| <= 60         | +60 min       |
| 61 – 70       | +40 min       |
| 71 – 80       | +30 min       |
| > 80          | +0 min        |

### 3.2. Dupa tipul de drum (`rute.tip_strada`)

| Tip drum      | Ajustare timp |
|---------------|---------------|
| autostrada    | −30 min       |
| dn            | +0 min        |
| drum judetean | +10 min       |
| drum comunal  | +20 min       |

(Aminteste- te de ierarhia de dificultate: autostrada = cel mai bun, comunal = cel
mai greu.)

### 3.3. Dupa vreme (API meteoromania.ro)

Se ia vremea in timp real din API-ul public ANM
(`https://www.meteoromania.ro/wp-json/meteoapi/v2/starea-vremii`), pentru orasele
rutei (origine = depozit, destinatie = client). Se aplica cea mai grea vreme dintre
cele doua orase:

| Vreme         | Ajustare timp |
|---------------|---------------|
| ninsoare      | +60 min       |
| ploaie        | +30 min       |
| fara / necunoscut | +0 min    |

Detectie: `fenomen_e` (ex: „aversa ploaie" => ploaie; „ninsoare"/„lapovita" =>
ninsoare) si `zapada` (strat masurat => ninsoare). Datele se pun in cache ~30 min
(`cache/meteo.json`). Daca API-ul nu raspunde si nu exista cache, ajustarea e 0.
Implementare in `src/backend/MeteoService.php`.

### 3.4. Formula timpului ajustat

```
timp_ajustat = Durata_min + ajustare_viteza + ajustare_tip + ajustare_vreme
```

## 4. Cum functioneaza algoritmul

1. Intra o comanda -> se identifica orasul clientului.
2. Pentru produsul cumparat, se gasesc depozitele care il au pe stoc.
3. Pentru fiecare depozit candidat, se ia ruta depozit -> oras client si se
   calculeaza `timp_ajustat`.
4. Se ordoneaza rutele crescator dupa `timp_ajustat` (cel mai mic = cea mai
   optimizata).
5. Se prezinta in back office primele 3 (sau cate exista), de la cea mai optimizata
   la cea mai putin optimizata, cu explicatia fiecareia (timp prestabilit + ce s-a
   adaugat/scazut din viteza si tip, si totalul).
6. Se creeaza expedierea si i se genereaza un AWB.

### Exemplu (date curente)

Produs `P003` (in depozitele Arad, Braila, Pitesti), client in **Iasi**:

| Ruta            | Durata_min | viteza | tip        | ajustare     | timp ajustat |
|-----------------|-----------:|-------:|------------|--------------|-------------:|
| Braila -> Iasi  |        252 |     70 | dn         | +40, +0      |      **292** |
| Pitesti -> Iasi |        476 |     65 | dn         | +40, +0      |          516 |
| Arad -> Iasi    |        800 |     80 | autostrada | +30, −30     |          800 |

Cea mai optimizata: **Braila -> Iasi** (292 min). Viteza 65 intra in intervalul
61–70 => +40 min.

## 5. Iesire in back office

Pentru fiecare comanda/produs, o lista ordonata cu cele 3 rute:

- locul (1 = cea mai optimizata), depozitul si orasul;
- timpul prestabilit si timpul ajustat;
- explicatie: „autostrada (−30) si viteza 80 (+30) => 800 min; cea mai rapida
  deoarece are cel mai mic timp ajustat”.

## 6. Decizii confirmate

1. **Viteze neacoperite** — se trateaza pe intervale (vezi 3.1): `<=60 => +60`,
   `61–70 => +40`, `71–80 => +30`, `>80 => +0`.
2. **Granularitate** — o expediere + un AWB **per linie de produs** din comanda,
   fiecare optimizata independent.
3. **Alegerea rutei** — se prezinta cele 3 rute in back office si **operatorul alege**
   una; abia dupa selectie se creeaza expedierea (nu automat).
4. **Soferul** — se alege **aleator** dintre soferi.

### Inca de stabilit (propuneri, se pot ajusta la implementare)

- **Format AWB:** `AWB` + data (AAAALLZZ) + 6 cifre, unic (ex: `AWB20260723000042`).
  Necesita coloana noua `awb` in `expedieri`.
- **Data livrarii estimate** (`DATE`): `Data_expediere` + `ceil(timp_ajustat / 480)`
  zile (o zi de condus ~ 8 h). Ex: 292 min => +1 zi.
- **Valoarea expedierii:** valoarea liniei de produs (cantitate x pret).

## 7. Componente de implementat (schita)

- Migrare: coloana `awb` in `expedieri` (unic).
- Serviciu backend `OptimizareRuteService` (sau in repository): pentru o linie de
  comanda, intoarce rutele candidate ordonate dupa `timp_ajustat`, cu detalii.
- Pagina back office: pentru o comanda, listeaza produsele si cele 3 rute per produs,
  cu buton de selectie; la selectie -> creeaza expedierea + AWB.
- Generator AWB unic.
