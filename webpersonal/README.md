# Site personal - Ioana Dima, analist de date

Site static (HTML + CSS), fara framework si fara baza de date. Se poate deschide
direct din fisier sau prin XAMPP:
`http://localhost/CLAUDE/PRIMUL/webpersonal/`

**Continutul de acum este inventat**, pentru a vedea cum arata pagina plina.
Toate locurile de inlocuit sunt marcate mai jos.

## Structura

```
webpersonal/
├── index.html          toata pagina, o sectiune per bloc
├── css/style.css       variabilele de culoare + stilurile
├── assets/
│   ├── portret.svg     substituent pentru fotografie
│   ├── favicon.svg     iconita din fila de browser
│   ├── CV-Ioana-Dima.pdf   (lipseste - de adaugat)
│   └── og-image.png        (lipseste - imaginea la partajare pe LinkedIn, 1200x630)
└── README.md
```

## Ce se inlocuieste cu date reale

| Ce | Unde |
|---|---|
| Nume, titlu, fraza de prezentare | `index.html`, sectiunea HERO |
| Cele 4 cifre-cheie | `index.html`, sectiunea CIFRE-CHEIE |
| Textul "Despre" si fisa din dreapta | sectiunea DESPRE |
| Competentele si nivelurile (`<i class="on">` = o treapta plina, din 4) | sectiunea COMPETENTE |
| Cele 3 studii de caz | sectiunea PROIECTE |
| Raportul Power BI | sectiunea DASHBOARD - vezi comentariul din HTML |
| Experienta, educatia, certificarile | sectiunea EXPERIENTA |
| Email, LinkedIn, GitHub, locatie | sectiunea CONTACT **si** subsolul **si** meta-etichetele din `<head>` |
| Fotografia | `assets/portret.svg` -> `portret.jpg` (4:5, min. 640x800), plus `src` in HERO |
| CV-ul | `assets/CV-Ioana-Dima.pdf` |

## Integrari externe

- **Power BI** - raportul se pune ca `<iframe>` in sectiunea *Dashboard live*.
  Se obtine din Power BI: *Fisier > Incorporare raport > Publicare pe web*.
  **Atentie:** "Publicare pe web" face raportul public pentru oricine are linkul.
  Se foloseste doar cu date de test, niciodata cu date reale de la un angajator.
  Pana atunci, in pagina sta un tablou de bord desenat local, cu date de exemplu.
- **GitHub** - linkurile din cardurile de proiect. Tot GitHub poate gazdui si site-ul
  (GitHub Pages, gratuit).
- **LinkedIn** - link in hero, contact si subsol.
- **Plausible / Umami** (optional, neadaugat inca) - un singur `<script>` in `<head>`
  daca vrei sa vezi cati vizitatori ai si de unde vin.
- **Formspree** (optional, neadaugat) - daca te razgandesti si vrei totusi un formular
  de contact, trimite pe email fara server propriu, gratuit pana la 50 de mesaje/luna.
- **Calendly** (optional, neadaugat inca) - `<iframe>` in sectiunea de contact, ca
  recrutorul sa isi rezerve singur 20 de minute.

## Culori si tipografie

Toate culorile sunt variabile CSS, definite o singura data la inceputul lui
`css/style.css`, in doua seturi: tema deschisa si tema intunecata. Ca sa schimbi
accentul in tot site-ul, modifici `--accent` in ambele seturi.

Fonturile sunt de sistem (serifa pentru titluri, sans umanista pentru text,
monospatiat pentru cifre si etichete), deci pagina se incarca instant si nu depinde
de niciun serviciu extern.

## Teme

Butonul cu soare/luna din bara de sus comuta tema si o tine minte in `localStorage`.
Este singurul JavaScript din pagina; fara el, site-ul functioneaza complet si
respecta tema sistemului.
