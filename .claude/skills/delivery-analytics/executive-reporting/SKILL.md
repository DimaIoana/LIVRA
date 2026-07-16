---
name: executive-reporting
description: Transforma KPI-uri si anomalii verificate intr-un dashboard sau raport clar pentru management - neutru, fara sa "infrumuseteze" rezultatele slabe, cu vizualizarea potrivita pentru fiecare tip de date. Ultimul pas dupa data-quality-audit, kpi-definitions si anomaly-trend-detection.
---

# Ce face
Datele corecte, prezentate prost, nu ajuta pe nimeni sa ia decizii. Acest skill se ocupa de forma finala a analizei.

## Reguli de prezentare
- **Neutralitate** — daca performanta e slaba (ex: rata de intarziere a crescut), raportul o arata clar, fara formulari care sa dilueze mesajul ("usor sub asteptari" cand de fapt e o scadere de 20%).
- **Context, nu doar cifra** — orice KPI apare alaturi de perioada anterioara sau de un reper (target, medie istorica), nu izolat.
- **Vizualizare potrivita tipului de date**: serii de timp -> grafic de linie; comparatie intre categorii (regiuni/curieri) -> bar chart; distributie (ex: timpi de livrare) -> histograma, nu doar o medie.
- **O concluzie pe grafic** — fiecare vizualizare are un titlu care spune ce arata, nu doar numele coloanelor.

## Structura recomandata a unui raport
1. Rezumat de o fraza — cea mai importanta concluzie a perioadei.
2. KPI-uri cheie cu comparatie fata de perioada anterioara.
3. Detaliu pe regiuni/curieri, doar unde exista suficiente date (vezi `anomaly-trend-detection`).
4. Riscuri/anomalii semnalate, cu nivelul de incredere mentionat explicit.

# Cand se foloseste
La finalul oricarui ciclu de analiza, dupa ce datele au trecut prin `data-quality-audit`, KPI-urile sunt calculate conform `kpi-definitions`, iar tendintele au fost verificate cu `anomaly-trend-detection`.
