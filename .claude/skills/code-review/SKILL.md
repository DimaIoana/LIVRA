---
name: code-review
description: Revizuieste codul PHP/JS modificat in proiectul PRIMUL pentru bug-uri de corectitudine si probleme de securitate (SQL injection, XSS) inainte de commit.
---

# Ce face
Verifica diff-ul curent din proiect pentru:
- bug-uri de logica si edge case-uri netratate
- vulnerabilitati specifice PHP: SQL injection (query-uri neparametrizate), XSS in output HTML, includeri de fisiere nesigure
- consistenta cu conventiile din `.claude/memory/coding-standards.md`

# Cand se foloseste
Inainte de a marca o functionalitate ca terminata sau inainte de commit.
