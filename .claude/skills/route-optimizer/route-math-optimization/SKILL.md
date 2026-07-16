---
name: route-math-optimization
description: Aplica algoritmi corecti de optimizare a rutelor (tip TSP/VRP) pentru a ordona opririle unui curier, in loc de o ordonare naiva "cel mai apropiat urmator". Foloseste-l la calculul propriu-zis al ordinii de livrare.
---

# Ce face
Ordonarea unei liste de adrese pentru livrare nu e "de la cel mai apropiat la cel mai indepartat" — asta e o euristica naiva care poate produce rute cu 20-30% mai lungi decat optimul. Acest skill aplica rigoare matematica reala.

## Problema si tipul ei
- Un singur curier, mai multe opristiri, revine (sau nu) la depozit — asta e o instanta de **TSP (Traveling Salesman Problem)**.
- Mai multi curieri, capacitate limitata per vehicul, ferestre orare — asta e **VRP (Vehicle Routing Problem)**, mai complex.
- Se identifica explicit care problema se rezolva inainte de a alege algoritmul — nu se aplica o solutie de TSP simplu la o problema cu constrangeri multiple.

## Euristici practice (cand nu e nevoie de solutie exacta)
- **Nearest neighbor** — rapid, dar poate produce rute prost optimizate spre final (ramane cu opriri indepartate la coada); util doar ca punct de plecare.
- **2-opt** — imbunatateste o ruta existenta eliminand incrucisari (cand ruta "se intoarce peste ea insasi"); usor de aplicat peste rezultatul unui nearest neighbor.
- **Clustering geografic** — pentru multi curieri, se grupeaza intai adresele pe zone (ex: clustere pe cartier/sector), apoi se optimizeaza ruta in fiecare cluster; evita ca doi curieri sa acopere aceeasi zona.

## Cand se justifica un solver "greu"
- Pentru zeci de opriri per curier, o euristica buna (nearest neighbor + 2-opt) e suficienta si rapida.
- Pentru probleme mari (sute de adrese, multi curieri, ferestre orare stricte), se ia in calcul un solver dedicat de VRP (ex: OR-Tools) — nu se reinventa un algoritm exact de mana.

## Validare a rezultatului
- Dupa optimizare, se verifica vizual/logic ca ruta nu are "zig-zag-uri" evidente (trecere inainte-inapoi prin aceeasi zona).
- Timpul total estimat al rutei optimizate se compara cu suma timpilor individuali A-B ca sanity check.

# Cand se foloseste
La pasul de calcul propriu-zis al ordinii de livrare, dupa ce restrictiile (ferestre orare) au fost colectate — vezi `time-window-constraints`.
