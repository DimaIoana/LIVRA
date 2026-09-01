<?php require __DIR__ . "/_acces.php"; ?>
<!DOCTYPE html>
<html lang="ro" data-theme="">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Ioana Dima — analist de date</title>
<meta name="description" content="Analist de date junior. Transform date operationale in rapoarte si decizii: SQL, Power BI, Excel, Python. Portofoliu, studii de caz si dashboard live.">
<meta name="author" content="Ioana Dima">

<!-- Cum arata linkul cand il dai pe LinkedIn / WhatsApp -->
<meta property="og:type" content="website">
<meta property="og:title" content="Ioana Dima — analist de date">
<meta property="og:description" content="Transform date operationale in decizii. SQL, Power BI, Excel. Studii de caz reale si un dashboard interactiv.">
<meta property="og:image" content="assets/portret.jpg">
<meta property="og:locale" content="ro_RO">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="css/style.css?v=54">

<!-- Tema salvata se aplica inainte de primul randat, ca sa nu palpaie ecranul -->
<script>
  (function () {
    try {
      var t = localStorage.getItem("tema");
      if (t) { document.documentElement.setAttribute("data-theme", t); }
    } catch (e) {}
  })();
</script>

<!-- Google Analytics (GA4).
     Scriptul nu e pus direct in pagina, ci adaugat din cod, ca sa se incarce
     doar de pe domeniul public: deschis local (localhost / fisier pe disc) nu
     trimite nimic, altfel fiecare test ar aparea ca vizita in rapoarte.
     "zona" separa in rapoarte site-ul de CV de aplicatia LIVRA; ca sa apara ca
     dimensiune trebuie inregistrata o data in Analytics, la
     Admin > Definitii personalizate. -->
<script>
  (function () {
    var gazda = location.hostname;

    if (!gazda || gazda === "localhost" || gazda === "127.0.0.1" || /\.(local|test)$/.test(gazda)) {
      return;
    }

    var id = "G-1LY95M3J22";

    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { dataLayer.push(arguments); };
    gtag("js", new Date());
    gtag("config", id, { zona: "cv", autentificat: "nu" });

    var s = document.createElement("script");
    s.async = true;
    s.src = "https://www.googletagmanager.com/gtag/js?id=" + id;
    document.head.appendChild(s);
  })();
</script>
</head>

<body>
<a class="skip" href="#continut">Sari la continut</a>

<!-- ============ NAVIGATIE ============ -->
<header class="topbar">
  <div class="wrap">
    <!-- Numele si functia stau aici, sus in stanga, deasupra liniei barii.
         In hero nu mai apar deloc. -->
    <h1 class="logo">Ioana Dima<span class="sep" aria-hidden="true">&middot;</span><span class="rol">Junior Data Analyst</span></h1>

    <div class="topbar-actions">
      <button class="btn theme-toggle" type="button" id="tema" aria-label="Schimba tema deschis / intunecat" title="Schimba tema">
        <span class="sun" aria-hidden="true">☼</span><span class="moon" aria-hidden="true">☾</span>
      </button>

      <!-- Pagina e in spatele unei parole (vezi _acces.php); de aici se iese. -->
      <a class="btn" href="?logout" title="Iesi din pagina privata">Iesire</a>
    </div>
  </div>
</header>

<main id="continut">

  <!-- ============ 01. HERO ============ -->
  <section class="hero wrap" id="top">
    <div class="grid">
      <div class="intro">
        <p class="lede">Bună! Sunt Ioana, o persoană organizată și atentă la detalii, pasionată de analiza datelor și de inteligența artificială, precum și de modul în care acestea pot contribui la luarea unor decizii cât mai bune.</p>

        <p>Îmi place să înțeleg provocările, să identific sursele potrivite de date, să selectez și să validez informațiile relevante și să folosesc instrumentele și tehnologiile adecvate pentru a oferi soluții cât mai eficiente pentru echipă și pentru business, urmând o metodologie bine structurată.</p>

        <p>Sunt o persoană curioasă, învăț rapid și îmi place să lucrez în echipă, într-un mediu bazat pe colaborare și comunicare. Îmi doresc să îmi dezvolt cariera în domeniul analizei de date și să evoluez constant atât din punct de vedere profesional, cât și personal.</p>

        <p>Vă mulțumesc pentru timpul acordat și vă invit să vizionați proiectele pe care le dezvolt în laboratorul meu, la <a class="link" href="https://www.ioanaanalytics.com/app">ioanaanalytics.com/app</a>.</p>

      </div>

      <div class="hero-side">
        <figure class="portrait">
          <img src="assets/portret.jpg" alt="Ioana Dima" width="400" height="400">
        </figure>

        <dl class="hero-facts">
          <dt>Oras</dt>
          <dd>Bucuresti, Romania</dd>
          <dt>Email</dt>
          <dd><a href="mailto:dimaioana5@gmail.com">dimaioana5@gmail.com</a></dd>
        </dl>
      </div>

      <!-- Butoanele stau sub ambele coloane, ca sa incapa toate pe un rand -->
      <div class="hero-actions">
        <a class="btn btn-primary" href="#experienta-profesionala">Experiență profesională</a>
        <a class="btn btn-primary" href="#studii">Studii</a>
        <a class="btn btn-primary" href="#competente">Competențe tehnice</a>
        <a class="btn btn-primary" href="#soft-skills">Soft skills</a>
        <a class="btn btn-primary" href="#metodologie">Metodologia</a>
        <a class="btn btn-primary" href="#proiecte">Proiecte</a>
      </div>
    </div>
  </section>

  <!-- ============ 01b. EXPERIENTA PROFESIONALA (cutie, structura din CV) ============ -->
  <!-- wrap-wide: aceeasi coloana ca la Competente tehnice, ca marginile din
       stanga si din dreapta sa cada pe aceeasi verticala. -->
  <section class="wrap wrap-wide cv-section" id="experienta-profesionala" aria-labelledby="titlu-experienta-pro">
    <div class="cv-cols">
    <div class="cv-box">
      <h2 class="cv-box-title" id="titlu-experienta-pro">Experiența profesională</h2>

      <div class="cv-entries">
      <article class="cv-entry">
        <div class="cv-meta">
          <b>Domus Capital</b>
          <span>București</span>
          <span>2026</span>
        </div>

        <div class="cv-body">
          <h3>Junior Data Analyst</h3>
          <p class="cv-desc">Domus Capital este o companie de investiții imobiliare nou-înființată în România, axată pe identificarea, achiziția, renovarea și valorificarea proprietăților rezidențiale, cu focus pe dezvoltarea și optimizarea investițiilor imobiliare.</p>
          <ul>
            <li>Colectarea și verificarea periodică a datelor privind costurile și progresul proiectelor imobiliare din surse diferite</li>
            <li>Crearea și actualizarea rapoartelor privind costurile și progresul proiectelor</li>
            <li>Analiza și controlul costurilor proiectelor imobiliare, inclusiv materiale, furnizori, termene de livrare, incidente și penalități</li>
            <li>Identificarea și raportarea discrepanțelor și incidentelor care pot afecta costurile sau termenele proiectelor</li>
            <li>Analiza abaterilor de cost față de buget și estimările inițiale</li>
            <li>Monitorizarea furnizorilor, comenzilor, livrărilor și termenelor de execuție</li>
            <li>Colaborarea directă cu managementul pentru furnizarea datelor necesare luării deciziilor</li>
          </ul>
        </div>
      </article>

      <article class="cv-entry">
        <div class="cv-meta">
          <b>Brink's Cash<br>Solution</b>
          <span>București</span>
          <span>2023&ndash;2024</span>
        </div>

        <div class="cv-body">
          <h3>Junior Data Analyst</h3>
          <p class="cv-desc">Brink's Cash Solutions este o companie specializată în cash management, transportul și procesarea valorilor și administrarea rețelelor de ATM-uri, oferind servicii de alimentare, reconciliere, monitorizare și raportare pentru clienți.</p>
          <ul>
            <li>Analiza și centralizarea datelor operaționale provenite din rețeaua de ATM-uri</li>
            <li>Colectarea și verificarea datelor furnizate de echipele de casieri din teren</li>
            <li>Reconcilierea datelor și verificarea consistenței informațiilor financiare și operaționale</li>
            <li>Identificarea și investigarea discrepanțelor financiare și a incidentelor operaționale</li>
            <li>Introducerea și validarea datelor în baze de date, fișiere Excel și aplicații corporative</li>
            <li>Pregătirea datelor și efectuarea pre-reconcilierii</li>
            <li>Centralizarea și transmiterea rapoartelor către clienți și departamentele interne</li>
            <li>Monitorizarea indicatorilor operaționali și urmărirea situațiilor care necesitau intervenție</li>
            <li>Colaborarea cu echipele de casieri și instituțiile bancare pentru clarificarea discrepanțelor și incidentelor</li>
          </ul>
        </div>
      </article>

      <article class="cv-entry">
        <div class="cv-meta">
          <b>iSense</b>
          <span>București</span>
          <span>2023</span>
        </div>

        <div class="cv-body">
          <h3>Junior Data Analyst</h3>
          <p class="cv-desc">iSense Solutions este o companie specializată în cercetare de piață și consumer insights, care colectează și analizează date despre consumatori pentru a sprijini procesul de luare a deciziilor.</p>
          <ul>
            <li>Colectarea datelor din surse multiple</li>
            <li>Curățarea și prelucrarea datelor</li>
            <li>Normalizarea și standardizarea datelor</li>
            <li>Validarea calității și consistenței datelor</li>
            <li>Organizarea și structurarea datelor pentru analiză</li>
            <li>Pregătirea dataset-urilor pentru analiză și raportare</li>
          </ul>
        </div>
      </article>
      </div>
    </div>

    <div class="cv-box" id="studii">
      <h2 class="cv-box-title" id="titlu-studii">Studii</h2>

      <!-- Invelisul desenaza o singura linie verticala, continua de la primul
           studiu pana la ultimul; bulinele stau pe ea. -->
      <div class="studii-lista">
      <article class="cv-entry cv-entry-stacked">
        <div class="cv-meta">
          <b>Universitatea Româno-Americană</b>
          <span>București</span>
          <span>2023&ndash;2025</span>
        </div>
        <div class="cv-body">
          <h3>Masterand</h3>
          <ul>
            <li>Facultatea de Informatică Managerială, specializarea Informatică Economică</li>
          </ul>
        </div>
      </article>

      <article class="cv-entry cv-entry-stacked">
        <div class="cv-meta">
          <b>Universitatea Româno-Americană</b>
          <span>București</span>
          <span>2020&ndash;2023</span>
        </div>
        <div class="cv-body">
          <h3>Licență</h3>
          <ul>
            <li>Facultatea de Informatică Managerială, specializarea Informatică Economică</li>
          </ul>
        </div>
      </article>

      <article class="cv-entry cv-entry-stacked">
        <div class="cv-meta">
          <b>Liceul Teoretic &bdquo;Eugen Lovinescu&rdquo;</b>
          <span>București</span>
          <span>2016&ndash;2020</span>
        </div>
        <div class="cv-body">
          <h3>Diplomă de Bacalaureat</h3>
          <ul>
            <li>Profil Filologie</li>
          </ul>
        </div>
      </article>
      </div>
    </div>
    </div>

  </section>

  <!-- ============ 03. COMPETENTE ============ -->
  <section class="section" id="competente" aria-labelledby="titlu-competente">
    <div class="wrap wrap-wide">
      <div class="cv-box">
        <h2 class="cv-box-title" id="titlu-competente">Competențe tehnice</h2>

        <div class="tech-grid">

          <div class="tech-col">
            <h3>Excel</h3>
            <ul>
              <li>External Data Connections</li>
              <li>Pivot Tables</li>
              <li>Charts / KPI</li>
              <li>Conditional Formatting</li>
              <li>Data Validation</li>
              <li>Sort &amp; Filter</li>
              <li>XLOOKUP / VLOOKUP / INDEX / MATCH</li>
              <li>IF / IFS / AND / OR / IFERROR</li>
              <li>SUM / SUMIF / SUMIFS</li>
              <li>COUNT / COUNTIF / COUNTIFS</li>
              <li>AVERAGE / MIN / MAX</li>
              <li>ROUND / ABS / TRUNC</li>
              <li>LEFT / RIGHT / MID / LEN</li>
              <li>TRIM / CONCAT / TEXT</li>
              <li>TODAY() / NOW() / DATE()</li>
              <li>YEAR() / MONTH() / DAY()</li>
              <li>UNIQUE / FILTER / SORT</li>
              <li>What-If Analysis</li>
              <li>Forecast Sheet</li>
            </ul>
          </div>

          <div class="tech-col">
            <h3>SQL &amp; Databases</h3>
            <p class="tech-sub">SQL</p>
            <ul>
              <li>SELECT</li>
              <li>INSERT / UPDATE / DELETE</li>
              <li>WHERE / BETWEEN / LIKE / IN / IS NULL</li>
              <li>ORDER BY / GROUP BY / HAVING / DISTINCT / LIMIT</li>
              <li>INNER / LEFT / RIGHT JOIN</li>
              <li>COUNT / SUM / AVG / MIN / MAX</li>
              <li>ROUND / ABS / TRUNC</li>
              <li>CASE / AS (Aliases)</li>
              <li>CONCAT / SUBSTRING / LENGTH</li>
              <li>UPPER / LOWER</li>
              <li>IFNULL</li>
              <li>DATE() / YEAR() / MONTH() / DAY() / NOW()</li>
            </ul>
            <p class="tech-sub">Database</p>
            <ul>
              <li>MySQL / MariaDB</li>
            </ul>
            <p class="tech-sub">phpMyAdmin</p>
            <ul>
              <li>Structure</li>
              <li>SQL Query Console</li>
              <li>Export / Import</li>
              <li>Operations</li>
              <li>Routines</li>
              <li>Events</li>
              <li>Triggers</li>
              <li>Designer</li>
            </ul>
          </div>

          <div class="tech-col">
            <h3>ETL: Power Query</h3>
            <ul>
              <li>Data Import / Data Connections</li>
              <li>Append Queries / Merge Queries</li>
              <li>Filter / Sort</li>
              <li>Remove Duplicates</li>
              <li>Replace Values</li>
              <li>Split Columns / Merge Columns</li>
              <li>Pivot / Unpivot</li>
              <li>Change Data Types</li>
              <li>Fill Down / Fill Up</li>
              <li>Custom Columns</li>
              <li>Conditional Columns</li>
              <li>Group By</li>
              <li>Date &amp; Time Transformations</li>
              <li>Text Transformations</li>
            </ul>
          </div>

          <div class="tech-col">
            <h3>BI: Power BI</h3>
            <ul>
              <li>Data Connections / Data Import</li>
              <li>Data Modeling / Relationships</li>
              <li>DAX (Basic)</li>
              <li>Measures / Calculated Columns</li>
              <li>SUM / AVERAGE / COUNT / IF</li>
              <li>Charts / Visualizations</li>
              <li>Slicers / Drill Down</li>
              <li>Filters / Bookmarks / Tooltips</li>
              <li>Dashboards / Reports</li>
              <li>Publish / Data Refresh</li>
              <li>KPIs</li>
              <li>Cards / Tables</li>
            </ul>
          </div>

          <div class="tech-col">
            <h3>Artificial Intelligence</h3>
            <ul>
              <li>Prompting</li>
              <li>AI Assistants</li>
              <li>Claude Code (contexts, agents, skills, tools)</li>
              <li>Visual Studio Code</li>
              <li>API Integration</li>
              <li>Database Integration</li>
              <li>GitHub</li>
            </ul>
          </div>

        </div>
      </div>

      <!-- Cutie de jumatate de pagina: aceeasi grila pe doua coloane, dar cu o
           singura cutie, deci ocupa exact jumatatea din stanga. -->
      <div class="cv-cols">
        <div class="cv-box" id="instrumente">
          <h2 class="cv-box-title" id="titlu-instrumente">Instrumente</h2>

          <ul class="cv-list" aria-labelledby="titlu-instrumente">
            <li>Microsoft Excel</li>
            <li>Power Query</li>
            <li>Power BI</li>
            <li>SQL</li>
            <li>MySQL</li>
            <li>MariaDB</li>
            <li>phpMyAdmin</li>
            <li>ODBC</li>
            <li>Visual Studio Code</li>
            <li>GitHub</li>
            <li>REST APIs</li>
            <li>Claude Code</li>
            <li>AI Assistants</li>
          </ul>
        </div>

        <div class="cv-box" id="metodologie">
          <h2 class="cv-box-title" id="titlu-metodologie">Metodologia de lucru</h2>

          <ol class="cv-list cv-list-num" aria-labelledby="titlu-metodologie">
            <li>Înțelegerea cerinței și a obiectivului analizei</li>
            <li>Colectarea și integrarea datelor din surse diferite</li>
            <li>Curățarea și verificarea calității datelor</li>
            <li>Structurarea și pregătirea datelor pentru analiză</li>
            <li>Analiza datelor și identificarea tendințelor, abaterilor și discrepanțelor</li>
            <li>Calcularea și monitorizarea indicatorilor (KPI)</li>
            <li>Crearea rapoartelor și vizualizarea rezultatelor</li>
            <li>Interpretarea rezultatelor și formularea concluziilor</li>
            <li>Comunicarea rezultatelor către stakeholderi / management</li>
            <li>Identificarea posibilităților de îmbunătățire și automatizare</li>
          </ol>
        </div>
      </div>

      <div class="cv-cols">
        <div class="cv-box" id="soft-skills">
          <h2 class="cv-box-title" id="titlu-soft-skills">Soft skills</h2>

          <ul class="cv-list" aria-labelledby="titlu-soft-skills">
            <li>Gândire analitică</li>
            <li>Orientare către rezultate</li>
            <li>Atenție la detalii</li>
            <li>Lucru în echipă</li>
            <li>Organizare</li>
            <li>Comunicare</li>
            <li>Adaptabilitate</li>
            <li>Gestionarea timpului</li>
            <li>Curiozitate și dorință de învățare continuă</li>
            <li>Capacitatea de a lucra cu volume mari de date</li>
          </ul>
        </div>

        <div class="cv-box" id="alte-informatii">
          <h2 class="cv-box-title" id="titlu-alte-informatii">Alte informații</h2>

          <ul class="cv-list" aria-labelledby="titlu-alte-informatii">
            <li>Permis de conducere categoria B</li>
            <li>Autoturism personal</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ 05. PROIECTE ============ -->
  <section class="section" id="proiecte" aria-labelledby="titlu-proiecte">
    <!-- wrap-wide: aceeasi coloana ca la Soft skills / Alte informatii -->
    <div class="wrap wrap-wide">
      <div class="projects">

        <!-- proiect 1 -->
        <article class="project">
          <h2 class="cv-box-title" id="titlu-proiecte">Proiecte</h2>

          <div>
            <h3>LIVRA - Sistem analitic pentru o companie de curierat</h3>

            <p class="project-desc">In cadrul acestui proiect am dorit sa creez un sistem analitic care sa sprijine procesul de luare a deciziilor intr-o companie de curierat pe care am numit-o LIVRA. Sistemul permite crearea unor rapoarte dinamice conectate la date provenite din surse diferite, precum baza de date si API-uri, precum si integrarea cu Power BI.</p>

            <p class="project-desc">De asemenea, am dezvoltat un algoritm de optimizare a rutelor cu ajutorul AI (Claude Code), cu scopul de a identifica rute mai eficiente si de a contribui la reducerea costurilor si la cresterea profitabilitatii companiei.</p>

            <!-- Doua randuri separate de etichete: sus instrumentele de analiza,
                 sub ele tehnologiile pe care e construit proiectul. -->
            <div class="tags">
              <span>Excel</span><span>SQL</span><span>Power Query</span><span>Power BI</span>
            </div>
            <div class="tags tags-rand-2">
              <span>MySQL</span><span>MariaDB</span><span>PHP</span><span>Claude Code</span>
            </div>

            <p class="project-links">
              <!-- Local proiectul sta langa webpersonal/ (../proiect.php), pe server
                   sta inauntru, in webpersonal/app/. $cv_link_proiect vine din
                   _acces.php si alege singur varianta, ca fisierul sa fie identic
                   si local, si in folderul de urcat. -->
              <a class="btn btn-primary" href="<?= cv_h($cv_link_proiect) ?>" rel="noopener" target="_blank">Acces la proiect &rarr;</a>
            </p>
          </div>

          <figure class="figure">
            <img src="assets/proiect-livra.png" width="1536" height="1024" loading="lazy"
                 alt="Ecranul principal al aplicatiei LIVRA: indicatori de rute optimizate, livrari si timp mediu, grafice de livrari pe zi si pe status, performanta vehiculelor si harta cu ruta optimizata">
          </figure>
        </article>

      </div>
    </div>
  </section>


</main>

<!-- ============ SUBSOL ============ -->
<footer class="site-foot">
  <div class="wrap">
    <span class="end">&copy; 2026 Ioana Dima</span>
  </div>
</footer>

<script>
  /* Singurul JavaScript din pagina: comutatorul de tema.
     Site-ul functioneaza complet si fara el. */
  (function () {
    var buton = document.getElementById("tema");
    if (!buton) { return; }

    buton.addEventListener("click", function () {
      var acum = document.documentElement.getAttribute("data-theme");
      if (!acum) {
        var preferaIntunecat = window.matchMedia("(prefers-color-scheme: dark)").matches;
        acum = preferaIntunecat ? "dark" : "light";
      }
      var nou = acum === "dark" ? "light" : "dark";
      document.documentElement.setAttribute("data-theme", nou);
      try { localStorage.setItem("tema", nou); } catch (e) {}
    });
  })();
</script>
</body>
</html>
