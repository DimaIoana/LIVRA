---
name: data-quality-audit
description: Auditeaza datele brute de livrare inainte de orice calcul de KPI sau raport - verifica duplicate, valori lipsa, formate inconsistente, outlieri si integritate referentiala intre tabele. Foloseste-l inaintea oricarei analize din delivery-analytics, niciodata dupa.
---

# Ce face
Nu se calculeaza niciun indicator pe date needitate. Inainte de orice analiza, se face un audit sistematic:

1. **Duplicate** — aceeasi comanda/AWB aparand de doua ori (ex: re-import, retry pe API).
2. **Valori lipsa** — timestamp-uri nule pentru "livrat", adrese fara regiune/oras asociat, curier neasignat.
3. **Formate inconsistente** — date in formate diferite (`DD/MM/YYYY` vs `YYYY-MM-DD`), fus orar amestecat (local vs UTC), status scris cu litere mari/mici diferit ("Livrat" vs "livrat" vs "LIVRAT").
4. **Outlieri fizic imposibili** — timp de livrare negativ (livrat inainte de a fi expediat), durate de sute de zile pentru un colet care ar trebui sa dureze cateva zile, viteze/distante imposibile pentru rute.
5. **Integritate referentiala** — comenzi care trimit catre un curier sau o adresa care nu mai exista in tabelele asociate din `src/database/`.

# Cum se raporteaza
- Fiecare problema gasita se raporteaza explicit inainte de a continua analiza: cate randuri afectate, ce procent din total, ce s-a facut cu ele (excluse, corectate, marcate).
- Nu se sterg si nu se "corecteaza" tacit date suspecte — orice curatare se documenteaza (ce regula s-a aplicat si de ce), ca rezultatul sa fie reproductibil.
- Daca volumul de date needitate e prea mare pentru a trage o concluzie de incredere, se spune explicit acest lucru in loc sa se continue cu un rezultat nesigur.

# Cand se foloseste
Primul pas, obligatoriu, inainte de `kpi-definitions` sau `anomaly-trend-detection`. Fara acest audit, orice KPI calculat e suspect.
