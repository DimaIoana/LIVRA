---
name: anomaly-trend-detection
description: Identifica tendinte reale (nu zgomot statistic) in datele de livrare - compara perioade, verifica marimea esantionului si sezonalitatea inainte de a numi ceva "tendinta". Foloseste-l cand se compara performanta in timp sau intre regiuni/curieri.
---

# Ce face
Diferenta intre un analist rigoros si unul superficial e ca primul nu numeste o fluctuatie "tendinta" fara sa verifice daca e reala.

## Reguli de rigoare statistica
1. **Marime minima de esantion** — o concluzie despre o regiune sau un curier necesita un numar minim de livrari relevante (nu se trage o concluzie pe 3-4 comenzi). Daca esantionul e mic, se spune explicit ca rezultatul e nesigur.
2. **Sezonalitate si confounderi cunoscuti** — inainte de a spune "intarzierile cresc vinerea", se verifica daca nu e efect de volum (mai multe comenzi vineri), trafic, sau sarbatori/evenimente punctuale dintr-o singura saptamana.
3. **Comparatie perioada-cu-perioada** — luna vs luna sau saptamana vs saptamana se face pe perioade de aceeasi lungime si acelasi tip (nu se compara o luna completa cu o luna partiala).
4. **Distinctie intre corelatie si cauzalitate** — daca doi curieri au rate diferite de intarziere, se verifica intai daca nu acopera zone diferite (ex: rural vs urban) inainte de a concluziona ca unul e "mai slab".

## Cum se semnaleaza o anomalie
- Se precizeaza magnitudinea (cat de mare e abaterea fata de normal) si increderea (cat de solid e sustinuta de date).
- Anomaliile posibil cauzate de probleme de date (vezi `data-quality-audit`) se elimina din discutie inainte de a fi raportate ca fenomen real.

# Cand se foloseste
Cand raportul cere comparatii in timp, pe regiuni sau pe curieri, sau cand cineva din management intreaba "de ce s-a intamplat X" — raspunsul trebuie sustinut de date suficiente, nu de o singura observatie.
