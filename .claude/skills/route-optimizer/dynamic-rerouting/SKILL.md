---
name: dynamic-rerouting
description: Recalculeaza ruta unui curier in timpul zilei cand apare o perturbare (drum blocat, comanda noua, comanda anulata, intarziere mare la o oprire) fara sa strice restrictiile deja confirmate. Foloseste-l dupa ce ruta initiala e deja in executie.
---

# Ce face
O ruta calculata dimineata nu ramane valabila toata ziua. Acest skill gestioneaza recalcularea, nu doar calculul initial.

## Evenimente care declanseaza recalculare
1. **Drum blocat / trafic neasteptat** — se recalculeaza doar portiunea ramasa a rutei, nu se reia optimizarea de la zero pentru opririle deja facute.
2. **Comanda noua aparuta in timpul zilei** — se insereaza in ruta ramasa doar daca nu incalca ferestrele orare deja confirmate pentru opririle existente (vezi `time-window-constraints`); daca nu incape, se semnaleaza pentru reprogramare, nu se forteaza.
3. **Comanda anulata** — se scoate din ruta si se re-optimizeaza restul opririlor ramase; timpul castigat se comunica (util pentru a prelua eventual o comanda noua).
4. **Intarziere mare la o oprire** (ex: clientul nu raspunde, acces blocat) — se estimeaza impactul in lant asupra opririlor urmatoare cu fereastra orara stransa si se semnaleaza din timp riscul de a rata o fereastra, nu dupa ce s-a intamplat deja.

## Reguli
- Recalcularea nu repune in discutie opririle deja finalizate — se lucreaza doar pe portiunea ramasa a rutei curierului.
- Orice schimbare de ruta transmisa curierului e clara si minimala (nu se retrimite intreaga lista daca s-a schimbat doar ordinea a 2 opriri).
- Daca o perturbare face imposibila respectarea unei ferestre orare deja promise clientului, decizia finala (renuntare/reprogramare) ramane la un om — agentul semnaleaza, nu decide singur.

# Cand se foloseste
Dupa ce ruta initiala (rezultata din `time-window-constraints` + `route-math-optimization`) e deja trimisa curierului si apare o schimbare in timpul zilei.
