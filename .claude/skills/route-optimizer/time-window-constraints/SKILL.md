---
name: time-window-constraints
description: Colecteaza si respecta restrictiile de livrare (interval orar cerut de client, prioritate, capacitate vehicul) inainte ca ruta sa fie optimizata matematic. Foloseste-l inaintea route-math-optimization, niciodata dupa.
---

# Ce face
O ruta scurta care ajunge la un client in afara intervalului orar cerut e o ruta gresita, indiferent cat de eficienta e din punct de vedere al distantei. Restrictiile se colecteaza si se respecta inainte de optimizare, nu se adauga la final ca o corectie.

## Tipuri de restrictii de colectat pentru fiecare oprire
1. **Fereastra orara** — client disponibil doar intr-un interval (ex: 14:00-16:00). O oprire cu fereastra ingusta limiteaza flexibilitatea intregii rute din jurul ei.
2. **Prioritate** — livrari urgente (ex: promise "azi pana la ora 12") care trebuie plasate devreme in ruta, chiar daca geografic ar fi mai eficient mai tarziu.
3. **Durata estimata la oprire** — timp de descarcare/semnatura, diferit intre un colet mic si o livrare cu mai multe colete pentru acelasi client.
4. **Capacitate vehicul** — greutate/volum total al coletelor alocate unui curier nu poate depasi capacitatea vehiculului; se verifica inainte de a construi ruta, nu dupa.

## Cum se trateaza conflictele
- Daca doua ferestre orare stranse sunt incompatibile geografic (clientul A cere 10:00-10:30 la un capat al orasului, clientul B cere 10:15-10:45 la celalalt capat), conflictul se semnaleaza explicit — nu se alege arbitrar cine ramane nemultumit.
- Daca o restrictie nu poate fi respectata cu resursele disponibile (prea multe livrari, prea putin timp), livrarea respectiva se reprogrameaza si se anunta un om — vezi constrangerea din `AGENT.md` al `route-optimizer`: nu se elimina o livrare din ruta fara sa fie semnalat.

# Cand se foloseste
Primul pas, inainte de `route-math-optimization` — algoritmul de optimizare primeste restrictiile ca input fix, nu le ignora si nu le adauga ulterior.
