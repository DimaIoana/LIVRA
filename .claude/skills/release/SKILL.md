---
name: release
description: Pregateste o versiune noua a proiectului LIVRA pentru publicare - verifica schema bazei de date, fisierele de configurare si documentatia inainte de a marca proiectul gata de livrare.
---

# Ce face
- Verifica ca `src/database/` contine toate migrarile necesare pentru schema curenta.
- Verifica ca fisierele de configurare sensibile (`.env`, parole DB) nu sunt incluse in commit.
- Actualizeaza `docs/` cu schimbarile relevante.

# Cand se foloseste
Inainte de a considera o functionalitate sau versiune gata de livrare/deploy.
