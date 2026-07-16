---
name: kpi-definitions
description: Defineste si calculeaza indicatorii de livrare (timp mediu de livrare, rata de intarzieri, rata de retur, throughput per curier) cu formule exacte si tratarea cazurilor limita, ca numerele sa fie reproductibile si comparabile in timp. Foloseste-l ori de cate ori se raporteaza un KPI.
---

# Ce face
Un KPI fara o definitie exacta e o sursa de neincredere ("de ce saptamana trecuta rata de intarziere era alta pentru aceleasi date?"). Fiecare indicator raportat trebuie sa aiba o formula fixa si documentata:

## Timp mediu de livrare
- Se calculeaza doar pe comenzile cu status final "livrat" (nu si cele in tranzit sau anulate).
- Formula: `data_livrare - data_expediere`, in ore. Se precizeaza daca fusul orar e local sau UTC.
- Se raporteaza si mediana pe langa medie — media poate fi distorsionata de cateva livrari extrem de intarziate.

## Rata de intarzieri
- Necesita o definitie explicita de "intarziat": livrare dupa termenul promis clientului (nu dupa o estimare interna).
- Formula: `numar comenzi livrate dupa termenul promis / total comenzi livrate in perioada`.
- Comenzile inca in tranzit nu intra la numitor decat daca au deja depasit termenul promis (caz separat: "in intarziere curenta").

## Rata de retur
- Formula: `numar colete returnate / total colete expediate in perioada`, cu mentiune clara a perioadei (data expedierii, nu data returului, ca sa nu se amestece perioade).

## Throughput per curier
- Numar de colete livrate cu succes / zi lucrata, nu / zi calendaristica (o zi de concediu nu trebuie sa scada media curierului).

# Reguli generale
- Orice KPI raportat mentioneaza explicit: perioada acoperita, filtrele aplicate (regiune, tip livrare) si ce e exclus.
- Cand o definitie se schimba (ex: se redefineste "intarziat"), se mentioneaza explicit in raport ca numerele nu mai sunt comparabile 1:1 cu perioadele anterioare.
- Presupune ca `data-quality-audit` a fost deja rulat pe datele folosite.

# Cand se foloseste
De fiecare data cand se raporteaza un numar catre management — niciun KPI nu se afiseaza fara sa se stie exact formula din spatele lui.
