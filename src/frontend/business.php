<?php

/**
 * Business Intelligence si analiza de date (back office): rapoartele de business,
 * intr-o singura pagina.
 *
 * Rapoarte:
 *   1. evolutia vanzarilor pe zi         - bare verticale (doar expedieri livrate)
 *   2. cheltuieli produse si carburant   - bare verticale grupate (doar livrate)
 *   3. comenzi pe oras                   - grafic tort
 *   4. business financiar                - grafic cu linii (vanzari, cost, profit)
 *   5. activitatea soferilor             - bare orizontale, un panou pe masura
 *   6. utilizarea traseelor              - bare orizontale, cu extremele marcate
 *   7. cursele soferilor                 - tabel: sofer, traseu, kilometri
 *   8. profitul pe sofer                 - grafic radar, o axa pe sofer
 *   9. raport Power BI                   - raportul publicat, adus in iframe
 *
 * Graficele sunt desenate din HTML + CSS (inaltimi in procente, `conic-gradient`
 * pentru tort), server-side, fara JS si fara librarii externe. Sub fiecare grafic
 * exista si tabelul cu cifrele, ca valorile sa nu depinda numai de culoare.
 */

// Back office: doar pentru utilizatorii autentificati (vezi _auth.php).
require __DIR__ . '/_auth.php';
cere_admin();

require_once __DIR__ . '/../database/db_connection.php';
require __DIR__ . '/../backend/BusinessRepository.php';
require_once __DIR__ . '/_grafic_scara.php';

/** Scapa text pentru HTML. */
function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** 2026-07-19 -> "19.07"; pentru eticheta scurta de pe axa X. */
function zi_scurta($date)
{
    $d = explode('-', (string) $date);

    return count($d) === 3 ? $d[2] . '.' . $d[1] : (string) $date;
}

/** 2026-07-19 -> "19.07.2026". */
function zi_lunga($date)
{
    $d = explode('-', (string) $date);

    return count($d) === 3 ? $d[2] . '.' . $d[1] . '.' . $d[0] : (string) $date;
}

/** "2026-07-19 14:30:00" -> "19.07.2026, 14:30". */
function data_ora($valoare)
{
    $t = strtotime((string) $valoare);

    return $t ? date('d.m.Y, H:i', $t) : '-';
}

/** "Braila" + "Cluj-Napoca" -> "Braila -> Cluj-Napoca". */
function traseu(array $ruta)
{
    return $ruta['Oras_origine'] . ' → ' . $ruta['Oras_destinatie'];
}

/** Clasa de status pentru eticheta unei expedieri (la fel ca in expedieri.php). */
function clasa_status($status)
{
    if ($status === 'Livrat') {
        return 'tag--ok';
    }

    if ($status === 'Intarziat' || $status === 'Returnat') {
        return 'tag--fail';
    }

    return 'tag--warn';
}

/** 1234.5 -> "1.234,50 lei". */
function lei($value)
{
    return number_format((float) $value, 2, ',', '.') . ' lei';
}

/** 1234.5 -> "1.235" (fara zecimale, pentru axa si etichetele de pe bare). */
function lei_scurt($value)
{
    return number_format((float) $value, 0, ',', '.');
}

/** 1234 -> "1.234"; numar intreg cu separator de mii. */
function numar($value)
{
    return number_format((float) $value, 0, ',', '.');
}

/** 155 -> "2 h 35 min"; timpul de condus, citibil. */
function ore($minute)
{
    $minute = (int) round($minute);
    $h = intdiv($minute, 60);

    return $h > 0 ? $h . ' h ' . ($minute % 60) . ' min' : $minute . ' min';
}

/** Eticheta de langa bara, pentru fiecare din cele trei masuri ale soferului. */
function masura_scurta($masura, array $sofer)
{
    if ($masura === 'km') {
        return numar($sofer['km']) . ' km';
    }

    if ($masura === 'minute') {
        return ore($sofer['minute']);
    }

    return numar($sofer['litri']) . ' L';
}

/** Lungimea unei bare orizontale, in procente din maximul coloanei ei. */
function lungime($valoare, $max)
{
    if ($max <= 0) {
        return 0;
    }

    return round(max(0, (float) $valoare) / $max * 100, 2);
}

/**
 * Clasele unei bare. Barele cu valoare mica ar iesi de sub un pixel, deci
 * primesc o inaltime minima; o valoare de zero ramane fara bara.
 */
function bara_clasa($valoare, $serie)
{
    $clase = 'chart__bar chart__bar--' . $serie;

    return (float) $valoare > 0 ? $clase . ' chart__bar--minim' : $clase;
}

/** Luminanta relativa a unei culori (formula WCAG). */
function luminanta($hex)
{
    $canale = [
        hexdec(substr($hex, 1, 2)) / 255,
        hexdec(substr($hex, 3, 2)) / 255,
        hexdec(substr($hex, 5, 2)) / 255,
    ];

    foreach ($canale as $i => $c) {
        $canale[$i] = $c <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
    }

    return 0.2126 * $canale[0] + 0.7152 * $canale[1] + 0.0722 * $canale[2];
}

/**
 * Culoarea textului scris peste o umplutura colorata: se alege dintre alb si
 * cerneala varianta cu contrastul mai mare, ca eticheta din felie sa ramana
 * lizibila pe orice culoare.
 */
function cerneala($hex)
{
    $l = luminanta($hex);
    $peAlb = 1.05 / ($l + 0.05);
    $peCerneala = ($l + 0.05) / (luminanta(CERNEALA) + 0.05);

    return $peAlb >= $peCerneala ? '#ffffff' : CERNEALA;
}

// Paleta categorica pentru tort. Aceleasi valori ca `--s1` ... `--s6` din
// business.css; ordinea nu se schimba, ea e cea care tine feliile vecine
// distincte inclusiv pentru cine vede altfel culorile.
const PALETA = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#4a3aa7', '#e87ba4'];

// Cerneala pentru text scris peste o felie deschisa la culoare.
const CERNEALA = '#0b0b0b';

// Spatiul alb dintre doua felii de tort, in grade (~2px la raza folosita).
const TORT_GOL = 1.2;

// Sistemul de coordonate al graficului cu linii. SVG-ul se intinde peste toata
// zona de desen (`preserveAspectRatio="none"`), deci astea sunt doar unitati
// interne, nu pixeli pe ecran.
const LINIE_W = 640;
const LINIE_H = 260;

// Seriile graficului financiar si slotul de culoare al fiecareia (--s1 ... --s3).
const SERII_FINANCIAR = ['vanzari' => 's1', 'cost' => 's2', 'profit' => 's3'];

// Sistemul de coordonate al graficului radar. Aici SVG-ul isi pastreaza forma
// (`preserveAspectRatio` implicit), deci panza e patrata si cercurile raman
// cercuri. Raza e mai mica decat jumatatea panzei, ca sa ramana loc de nume in
// jurul ei.
const RADAR_BOX = 380;
const RADAR_C = 190;    // centrul
const RADAR_R = 130;    // raza inelului exterior
const RADAR_ETICHETA = 156;  // raza pe care stau numele soferilor

/**
 * Punctul de pe panza radarului, la un unghi (radiani, 0 = in sus) si o raza.
 *
 * @return array [x, y] in coordonatele SVG-ului
 */
function radar_punct($unghi, $raza)
{
    return [
        round(RADAR_C + $raza * sin($unghi), 2),
        round(RADAR_C - $raza * cos($unghi), 2),
    ];
}

/** Acelasi punct, dar in procente din panza, pentru etichetele HTML. */
function radar_procent($unghi, $raza)
{
    list($x, $y) = radar_punct($unghi, $raza);

    return [round($x / RADAR_BOX * 100, 3), round($y / RADAR_BOX * 100, 3)];
}

// Raportul Power BI aratat la sfarsitul paginii.
//
// `PBI_EMBED` e adresa de incorporare (`reportEmbed`), nu adresa la care se
// ajunge din browser: pe aceea Power BI refuza sa o afiseze intr-un iframe.
// Asa cum e acum, raportul cere ca vizitatorul sa fie logat in Power BI si sa
// aiba drept pe raport (`autoAuth=true` porneste singur autentificarea).
//
// Daca vrei sa se vada fara cont, in Power BI: File -> Embed report ->
// Publish to web (public). De acolo iese o adresa de forma
// `https://app.powerbi.com/view?r=...` - pusa aici, raportul se incarca pentru
// oricine deschide pagina. (Atentie: "publish to web" face raportul public pe
// internet pentru oricine are linkul.)
const PBI_EMBED = 'https://app.powerbi.com/reportEmbed'
    . '?reportId=36274dde-60cd-4eee-b0c2-5b3e4c8f10de'
    . '&pageName=681dfc24182e60045ad1'
    . '&autoAuth=true';

// Adresa normala a raportului, pentru butonul de deschidere intr-o fila noua.
const PBI_LINK = 'https://app.powerbi.com/groups/me/reports/'
    . '36274dde-60cd-4eee-b0c2-5b3e4c8f10de/681dfc24182e60045ad1?experience=power-bi';

// Numele citibile ale tipurilor de drum, ca in rute.php.
const TIPURI_STRADA = [
    'autostrada' => 'Autostrada',
    'dn' => 'Drum national (DN)',
    'drum judetean' => 'Drum judetean',
    'drum comunal' => 'Drum comunal',
];

/**
 * Geometria unei benzi de hover din graficul cu linii: banda acopera jumatatea
 * de dinainte si jumatatea de dupa punctul `i`, iar prima si ultima sunt doar
 * jumatati. Intoarce stiluri gata de pus in atributul `style`, ca tooltipul de
 * la capete sa nu iasa din card.
 *
 * @return array ['banda' => string, 'linie' => string, 'tip' => string]
 */
function banda($i, $n)
{
    $pas = 100 / ($n - 1);
    $jumatate = round($pas / 2, 3);

    if ($i === 0) {
        return [
            'banda' => 'left: 0; width: ' . $jumatate . '%',
            'linie' => 'left: 0',
            'tip' => 'left: 0; transform: none',
        ];
    }

    if ($i === $n - 1) {
        return [
            'banda' => 'left: ' . round(100 - $pas / 2, 3) . '%; width: ' . $jumatate . '%',
            'linie' => 'left: 100%',
            'tip' => 'left: auto; right: 0; transform: none',
        ];
    }

    return [
        'banda' => 'left: ' . round($i * $pas - $pas / 2, 3) . '%; width: ' . round($pas, 3) . '%',
        'linie' => 'left: 50%',
        'tip' => '',
    ];
}

// --- Datele rapoartelor ---

$repo = new BusinessRepository($pdo);

$vanzari = $repo->vanzariPeZi();
$cheltuieli = $repo->cheltuieliPeZi();
$orase = $repo->comenziPeOras();
$soferi = $repo->activitateSoferi();
$trasee = $repo->utilizareTrasee();
$curse = $repo->curseSoferi();

// Zilele raportate, imbinate din cele doua interogari. Zilele fara livrari se
// completeaza cu zero: pe o axa de timp o zi lipsa ar lipi doua zile care nu
// sunt vecine si ar deforma evolutia.
$zile = [];

foreach ($vanzari as $v) {
    $zile[$v['zi']] = [
        'vanzari' => (float) $v['total'],
        'expedieri' => (int) $v['expedieri'],
        'produse' => 0.0,
        'carburant' => 0.0,
    ];
}

foreach ($cheltuieli as $c) {
    if (!isset($zile[$c['zi']])) {
        $zile[$c['zi']] = ['vanzari' => 0.0, 'expedieri' => 0, 'produse' => 0.0, 'carburant' => 0.0];
    }

    $zile[$c['zi']]['produse'] = (float) $c['cost_produse'];
    $zile[$c['zi']]['carburant'] = (float) $c['cost_carburant'];
}

if ($zile) {
    $chei = array_keys($zile);
    $cursor = new DateTime(min($chei));
    $ultima = new DateTime(max($chei));

    while ($cursor <= $ultima) {
        $zi = $cursor->format('Y-m-d');

        if (!isset($zile[$zi])) {
            $zile[$zi] = ['vanzari' => 0.0, 'expedieri' => 0, 'produse' => 0.0, 'carburant' => 0.0];
        }

        $cursor->modify('+1 day');
    }

    ksort($zile);
}

// Totalurile si maximele celor trei rapoarte pe zile.
$totalVanzari = 0;
$totalProduse = 0;
$totalCarburant = 0;
$maxVanzari = 0;
$maxCheltuiala = 0;

foreach ($zile as $z) {
    $totalVanzari += $z['vanzari'];
    $totalProduse += $z['produse'];
    $totalCarburant += $z['carburant'];
    $maxVanzari = max($maxVanzari, $z['vanzari']);
    $maxCheltuiala = max($maxCheltuiala, $z['produse'], $z['carburant']);
}

// 1. Vanzari pe zi.
$scaraVanzari = scara_y($maxVanzari);

// 2. Cheltuieli pe zi (bara cea mai inalta dintre cele doua serii da scara).
$scaraCheltuieli = scara_y($maxCheltuiala);

// 3. Comenzi pe oras -> feliile tortului: unghiuri, pozitia etichetei si
// culoarea ei, calculate aici ca sablonul sa ramana curat.
$totalComenzi = 0;
foreach ($orase as $o) {
    $totalComenzi += (int) $o['comenzi'];
}

// Tortul are atatea culori cate are paleta si nu le repeta: daca sunt mai multe
// orase, cele mai mici (lista vine ordonata descrescator) se aduna intr-o
// singura felie, ca doua orase sa nu ajunga cu aceeasi culoare.
if (count($orase) > count(PALETA)) {
    $principale = array_slice($orase, 0, count(PALETA) - 1);
    $restul = array_slice($orase, count(PALETA) - 1);

    $suma = 0;
    foreach ($restul as $r) {
        $suma += (int) $r['comenzi'];
    }

    $principale[] = ['oras' => 'Alte ' . count($restul) . ' orase', 'comenzi' => $suma];
    $orase = $principale;
}

$felii = [];
$stopuri = [];
$unghi = 0.0;

foreach ($orase as $i => $o) {
    $culoare = PALETA[$i];
    $marime = $totalComenzi > 0 ? (int) $o['comenzi'] / $totalComenzi * 360 : 0;

    // Feliile foarte subtiri n-au loc de spatiu alb pe ambele parti.
    $gol = $marime > 3 * TORT_GOL ? TORT_GOL : 0;
    $start = $unghi + $gol / 2;
    $sfarsit = $unghi + $marime - $gol / 2;

    $stopuri[] = $culoare . ' ' . round($start, 2) . 'deg ' . round($sfarsit, 2) . 'deg';
    if ($gol > 0) {
        $stopuri[] = 'var(--surface) ' . round($sfarsit, 2) . 'deg ' . round($unghi + $marime, 2) . 'deg';
    }

    // Eticheta sta la 62% din raza, pe bisectoarea feliei (0deg = ora 12).
    $mijloc = deg2rad($unghi + $marime / 2);

    $felii[] = [
        'oras' => $o['oras'],
        'comenzi' => (int) $o['comenzi'],
        'procent' => $totalComenzi > 0 ? (int) $o['comenzi'] / $totalComenzi * 100 : 0,
        'culoare' => $culoare,
        'cerneala' => cerneala($culoare),
        'x' => round(50 + 31 * sin($mijloc), 2),
        'y' => round(50 - 31 * cos($mijloc), 2),
        'incape' => $marime >= 22,   // sub ~22deg eticheta din felie devine inghesuita
    ];

    $unghi += $marime;
}

$tort = $stopuri ? 'conic-gradient(' . implode(', ', $stopuri) . ')' : '';

// 4. Business financiar: vanzari, cost si profit pe fiecare zi, pana in prezent.
$financiar = [];
$totalProfit = 0;
$minFinanciar = 0;
$maxFinanciar = 0;

foreach ($zile as $zi => $z) {
    $cost = $z['produse'] + $z['carburant'];
    $profit = $z['vanzari'] - $cost;
    $totalProfit += $profit;
    $minFinanciar = min($minFinanciar, $profit, $cost, $z['vanzari']);
    $maxFinanciar = max($maxFinanciar, $profit, $cost, $z['vanzari']);

    $financiar[] = ['zi' => $zi, 'vanzari' => $z['vanzari'], 'cost' => $cost, 'profit' => $profit];
}

$scaraFinanciar = scara_interval($minFinanciar, $maxFinanciar);
$nZile = count($financiar);

// Punctele fiecarei serii, in coordonatele SVG-ului. Cu o singura zi nu se
// poate desena o linie, deci graficul se arata de la doua zile in sus.
$linii = array_fill_keys(array_keys(SERII_FINANCIAR), []);
$puncte = $linii;

if ($nZile > 1) {
    $interval = $scaraFinanciar['max'] - $scaraFinanciar['min'];

    foreach ($financiar as $i => $f) {
        $x = round($i * LINIE_W / ($nZile - 1), 2);

        foreach (SERII_FINANCIAR as $serie => $slot) {
            $y = round(LINIE_H * ($scaraFinanciar['max'] - $f[$serie]) / $interval, 2);
            $linii[$serie][] = $x . ',' . $y;
            $puncte[$serie][] = ['x' => $x, 'y' => $y];
        }
    }

    // Linia lui zero: cade fix pe o gradatie, fiindca scara e construita asa.
    $yZero = round(LINIE_H * $scaraFinanciar['max'] / $interval, 2);
}

// 5. Activitatea soferilor. Cele trei masuri au unitati diferite (km, minute,
// litri), deci nu pot sta pe aceeasi axa: fiecare are coloana ei, cu scara ei,
// dusa de maximul coloanei. Randurile pastreaza aceeasi ordine in toate trei,
// ca sa se poata compara soferii de la un panou la altul.
$maxSofer = ['km' => 0, 'minute' => 0, 'litri' => 0];
$totalSofer = ['expedieri' => 0, 'km' => 0, 'minute' => 0, 'litri' => 0, 'cost' => 0];

foreach ($soferi as $s) {
    foreach (['km', 'minute', 'litri'] as $masura) {
        $maxSofer[$masura] = max($maxSofer[$masura], (float) $s[$masura]);
        $totalSofer[$masura] += (float) $s[$masura];
    }

    $totalSofer['expedieri'] += (int) $s['expedieri'];
    $totalSofer['cost'] += (float) $s['cost_carburant'];
}

// Titlul, culoarea si totalul fiecarei coloane. Carburantul ramane pe --s2, ca
// in graficul de cheltuieli: aceeasi masura, aceeasi culoare in tot dashboardul.
$coloaneSoferi = [
    'km' => ['titlu' => 'Kilometri', 'slot' => 's1', 'total' => numar($totalSofer['km']) . ' km'],
    'minute' => ['titlu' => 'Timp de condus', 'slot' => 's3', 'total' => ore($totalSofer['minute'])],
    'litri' => ['titlu' => 'Carburant', 'slot' => 's2', 'total' => numar($totalSofer['litri']) . ' L'],
];

// 6. Utilizarea traseelor. Lista vine ordonata descrescator dupa curse, deci
// primul rand e cel mai folosit si ultimul e cel mai putin folosit. Extremele nu
// se coloreaza diferit (culoarea ar ajunge sa poarte rangul, nu traseul): ele
// primesc o eticheta scrisa, care se vede si alb-negru.
$maxCurse = 0;
$minCurse = 0;
$totalCurse = 0;
$totalKmTrasee = 0;
$traseeNefolosite = 0;

if ($trasee) {
    $maxCurse = (int) $trasee[0]['curse'];
    $minCurse = (int) $trasee[count($trasee) - 1]['curse'];

    foreach ($trasee as $t) {
        $totalCurse += (int) $t['curse'];
        $totalKmTrasee += (int) $t['km'];

        if ((int) $t['curse'] === 0) {
            $traseeNefolosite++;
        }
    }
}

// Etichetele extremelor. Cand toate traseele au acelasi numar de curse nu exista
// nici cel mai folosit, nici cel mai putin folosit, deci nu se marcheaza nimic.
$etichetaMin = $minCurse === 0 ? 'Nefolosit' : 'Cel mai putin folosit';
$areExtreme = $maxCurse > $minCurse;

// 7. Cursele soferilor. Numele soferului se scrie o singura data pe grup, ca
// randurile aceluiasi sofer sa se citeasca impreuna; lista vine deja grupata.
$totalKmCurse = 0;
$totalMinCurse = 0;
$soferAnterior = null;

foreach ($curse as $i => $c) {
    $totalKmCurse += (int) $c['Distanta_km'];
    $totalMinCurse += (int) $c['Durata_min'];
    $curse[$i]['primul'] = $c['Nume'] !== $soferAnterior;
    $soferAnterior = $c['Nume'];
}

// 8. Radarul profitului pe sofer. O singura serie (profitul), o axa pentru
// fiecare sofer. Scara merge de la o valoare rotunda sub cel mai slab rezultat
// pana la zero sau peste, deci inelul care inseamna "pe zero" e mereu pe grafic:
// cu cat coltul poligonului e mai spre margine, cu atat soferul a mers mai bine.
$soferiProfit = $repo->profitSoferi();
$nSoferi = count($soferiProfit);

$radarInele = [];
$radarSpite = [];
$radarPuncte = [];
$radarEtichete = [];
$radarGradatii = [];
$radarSerie = '';
$radarZero = null;
$scaraRadar = ['min' => 0, 'max' => 0, 'gradatii' => []];
$celMaiBun = $soferiProfit ? $soferiProfit[0] : null;
$totalProfitSoferi = 0;

foreach ($soferiProfit as $s) {
    $totalProfitSoferi += $s['profit'];
}

// Sub trei axe nu se poate inchide un poligon, deci nu exista radar.
if ($nSoferi >= 3) {
    // Cinci diviziuni, nu patru ca la restul graficelor: pe un radar forma
    // poligonului e mesajul, deci merita o scara mai stransa, care sa-l duca
    // aproape de marginea panzei.
    $profituri = array_column($soferiProfit, 'profit');
    $scaraRadar = scara_interval(min($profituri), max($profituri), 5);
    $intervalRadar = $scaraRadar['max'] - $scaraRadar['min'];

    /** Raza pe care cade o valoare de profit. */
    $razaProfit = function ($valoare) use ($scaraRadar, $intervalRadar) {
        return RADAR_R * ($valoare - $scaraRadar['min']) / $intervalRadar;
    };

    // Inelele (panza paianjenului) si gradatiile scrise langa axa de sus.
    foreach ($scaraRadar['gradatii'] as $g) {
        $raza = $razaProfit($g);

        // Gradatia din centru n-are poligon, dar isi pastreaza eticheta.
        if ($raza > 0) {
            $colturi = [];
            for ($i = 0; $i < $nSoferi; $i++) {
                list($x, $y) = radar_punct($i * 2 * M_PI / $nSoferi, $raza);
                $colturi[] = $x . ',' . $y;
            }

            $inel = ['puncte' => implode(' ', $colturi), 'zero' => abs($g) < 0.001];
            if ($inel['zero']) {
                $radarZero = $inel['puncte'];
            } else {
                $radarInele[] = $inel;
            }
        }

        $radarGradatii[] = [
            'valoare' => $g,
            'sus' => round((RADAR_C - $raza) / RADAR_BOX * 100, 3),
        ];
    }

    // Spitele, punctele seriei si numele din jurul panzei.
    $colturiSerie = [];

    foreach ($soferiProfit as $i => $s) {
        $unghi = $i * 2 * M_PI / $nSoferi;

        list($xs, $ys) = radar_punct($unghi, RADAR_R);
        $radarSpite[] = ['x' => $xs, 'y' => $ys];

        list($xp, $yp) = radar_punct($unghi, $razaProfit($s['profit']));
        $colturiSerie[] = $xp . ',' . $yp;
        $radarPuncte[] = ['x' => $xp, 'y' => $yp, 'sofer' => $s];

        list($xe, $ye) = radar_procent($unghi, RADAR_ETICHETA);
        $radarEtichete[] = ['x' => $xe, 'y' => $ye, 'sofer' => $s, 'loc' => $i + 1];
    }

    $radarSerie = implode(' ', $colturiSerie);
}

$navLinks = [
    'clienti' => ['clienti.php', 'Clienti'],
    'comenzi' => ['comenzi.php', 'Comenzi'],
    'expedieri' => ['expedieri.php', 'Expedieri'],
    'soferi' => ['soferi.php', 'Soferi'],
    'rute' => ['rute.php', 'Rute'],
    'catalog' => ['catalog_produse.php', 'Catalog de produse'],
    'produse' => ['produse.php', 'Control de stocks'],
    'business' => ['business.php', 'Business'],
];

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LIVRA - Business Intelligence si analiza de date</title>
  <link rel="stylesheet" href="css/app.css?v=<?= filemtime(__DIR__ . '/css/app.css') ?>">
  <link rel="stylesheet" href="css/business.css?v=<?= filemtime(__DIR__ . '/css/business.css') ?>">
  <?php require_once __DIR__ . '/_analytics.php'; ?>
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a class="logo" href="../../index.php">LIVRA</a>
    <span class="header__subtitle">Business Intelligence si analiza de date</span>
    <nav class="nav">
      <?php foreach ($navLinks as $key => $link): ?>
        <a class="nav__link<?= $key === 'business' ? ' nav__link--active' : '' ?>" href="<?= h($link[0]) ?>"><?= h($link[1]) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php if ($adminConectat = admin_logat()): ?>
      <span class="header__user">
        <strong><?= h($adminConectat['Nume']) ?></strong>
        <a class="header__logout" href="admin_login.php?logout=1">Iesi</a>
      </span>
    <?php endif; ?>
  </div>
</header>

<main class="container">
  <h1 class="dash__title">Business Intelligence si analiza de date</h1>
  <p class="dash__lead">
    Rapoartele de bani se calculeaza numai pe expedierile livrate, in ziua livrarii efective.
    O zi fara livrari apare cu zero, ca sirul zilelor sa fie neintrerupt.
  </p>

  <div class="kpi">
    <div class="kpi__item">
      <span class="kpi__label">Vanzari livrate</span>
      <span class="kpi__value"><?= h(lei($totalVanzari)) ?></span>
    </div>
    <div class="kpi__item">
      <span class="kpi__label">Cheltuieli produse</span>
      <span class="kpi__value"><?= h(lei($totalProduse)) ?></span>
    </div>
    <div class="kpi__item">
      <span class="kpi__label">Cheltuieli carburant</span>
      <span class="kpi__value"><?= h(lei($totalCarburant)) ?></span>
    </div>
    <div class="kpi__item">
      <span class="kpi__label">Profit</span>
      <span class="kpi__value <?= $totalProfit < 0 ? 'kpi__value--minus' : 'kpi__value--plus' ?>"><?= h(lei($totalProfit)) ?></span>
    </div>
    <div class="kpi__item">
      <span class="kpi__label">Comenzi</span>
      <span class="kpi__value"><?= (int) $totalComenzi ?></span>
    </div>
  </div>

  <!-- Rapoartele stau cate doua pe rand, fiecare pe jumatate de pagina. -->
  <div class="dash__grid">

  <!-- 1. Evolutia vanzarilor pe zi -->
  <section class="card dash__card">
    <div class="dash__head">
      <h2 class="card__title">Evolutia vanzarilor pe zi</h2>
      <p class="card__desc">Valoarea expedierilor livrate, in lei, pe ziua livrarii.</p>
    </div>

    <?php if (!$zile): ?>
      <p class="empty">Nu exista expedieri livrate.</p>
    <?php else: ?>
      <div class="chart">
        <div class="chart__yaxis">
          <?php foreach ($scaraVanzari['gradatii'] as $i => $g): ?>
            <span class="chart__tick" style="bottom: <?= round(100 - $i * 100 / (count($scaraVanzari['gradatii']) - 1), 2) ?>%"><?= h(lei_scurt($g)) ?></span>
          <?php endforeach; ?>
        </div>

        <div class="chart__plot">
          <div class="chart__cols">
            <?php foreach ($zile as $zi => $z): ?>
              <div class="chart__col">
                <div class="<?= bara_clasa($z['vanzari'], 's1') ?>" style="height: <?= inaltime($z['vanzari'], $scaraVanzari['max']) ?>%">
                  <span class="chart__value"><?= h(lei_scurt($z['vanzari'])) ?></span>
                  <span class="chart__tip"><?= h(zi_lunga($zi)) ?>: <?= h(lei($z['vanzari'])) ?> din <?= (int) $z['expedieri'] ?> expedieri</span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="chart__xaxis">
          <?php foreach ($zile as $zi => $z): ?>
            <span class="chart__xlabel"><?= h(zi_scurta($zi)) ?></span>
          <?php endforeach; ?>
        </div>

      </div>

      <details class="chart__data">
        <summary class="chart__summary">Vezi cifrele</summary>
        <table class="table">
          <thead>
            <tr><th>Ziua livrarii</th><th>Expedieri</th><th>Vanzari</th></tr>
          </thead>
          <tbody>
            <?php foreach ($zile as $zi => $z): ?>
              <tr>
                <td class="cell--nowrap"><?= h(zi_lunga($zi)) ?></td>
                <td class="cell--number"><?= (int) $z['expedieri'] ?></td>
                <td class="cell--number"><?= h(lei($z['vanzari'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td>Total</td><td></td><td class="cell--number"><?= h(lei($totalVanzari)) ?></td></tr>
          </tfoot>
        </table>
      </details>
    <?php endif; ?>
  </section>

  <!-- 2. Cheltuieli produse + carburant pe zi -->
  <section class="card dash__card">
    <div class="dash__head">
      <h2 class="card__title">Cheltuielile de produse si carburant pe zi</h2>
      <p class="card__desc">Costul de achizitie al produselor livrate si costul carburantului, in lei, pe ziua livrarii.</p>
    </div>

    <?php if (!$zile): ?>
      <p class="empty">Nu exista expedieri livrate.</p>
    <?php else: ?>
      <div class="legend">
        <span class="legend__item"><span class="swatch swatch--s1"></span>Produse</span>
        <span class="legend__item"><span class="swatch swatch--s2"></span>Carburant</span>
      </div>

      <div class="chart">
        <div class="chart__yaxis">
          <?php foreach ($scaraCheltuieli['gradatii'] as $i => $g): ?>
            <span class="chart__tick" style="bottom: <?= round(100 - $i * 100 / (count($scaraCheltuieli['gradatii']) - 1), 2) ?>%"><?= h(lei_scurt($g)) ?></span>
          <?php endforeach; ?>
        </div>

        <div class="chart__plot">
          <div class="chart__cols">
            <?php foreach ($zile as $zi => $z): ?>
              <div class="chart__col">
                <div class="<?= bara_clasa($z['produse'], 's1') ?>" style="height: <?= inaltime($z['produse'], $scaraCheltuieli['max']) ?>%">
                  <span class="chart__tip"><?= h(zi_lunga($zi)) ?> - produse: <?= h(lei($z['produse'])) ?></span>
                </div>
                <div class="<?= bara_clasa($z['carburant'], 's2') ?>" style="height: <?= inaltime($z['carburant'], $scaraCheltuieli['max']) ?>%">
                  <span class="chart__tip"><?= h(zi_lunga($zi)) ?> - carburant: <?= h(lei($z['carburant'])) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="chart__xaxis">
          <?php foreach ($zile as $zi => $z): ?>
            <span class="chart__xlabel"><?= h(zi_scurta($zi)) ?></span>
          <?php endforeach; ?>
        </div>

      </div>

      <details class="chart__data">
        <summary class="chart__summary">Vezi cifrele</summary>
        <table class="table">
          <thead>
            <tr><th>Ziua livrarii</th><th>Produse</th><th>Carburant</th><th>Total</th></tr>
          </thead>
          <tbody>
            <?php foreach ($zile as $zi => $z): ?>
              <tr>
                <td class="cell--nowrap"><?= h(zi_lunga($zi)) ?></td>
                <td class="cell--number"><?= h(lei($z['produse'])) ?></td>
                <td class="cell--number"><?= h(lei($z['carburant'])) ?></td>
                <td class="cell--number"><?= h(lei($z['produse'] + $z['carburant'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              <td class="cell--number"><?= h(lei($totalProduse)) ?></td>
              <td class="cell--number"><?= h(lei($totalCarburant)) ?></td>
              <td class="cell--number"><?= h(lei($totalProduse + $totalCarburant)) ?></td>
            </tr>
          </tfoot>
        </table>
      </details>
    <?php endif; ?>
  </section>

  <!-- 3. Comenzi pe oras -->
  <section class="card dash__card">
    <div class="dash__head">
      <h2 class="card__title">Numarul de comenzi pe oras</h2>
      <p class="card__desc">Toate comenzile plasate, dupa orasul clientului.</p>
    </div>

    <?php if (!$felii || $totalComenzi === 0): ?>
      <p class="empty">Nu exista comenzi.</p>
    <?php else: ?>
      <div class="pie">
        <div class="pie__chart" style="background: <?= h($tort) ?>">
          <?php foreach ($felii as $f): ?>
            <?php if ($f['incape']): ?>
              <span class="pie__label"
                    style="left: <?= $f['x'] ?>%; top: <?= $f['y'] ?>%; color: <?= h($f['cerneala']) ?>"><?= (int) $f['comenzi'] ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>

        <ul class="pie__legend">
          <?php foreach ($felii as $f): ?>
            <li class="pie__legend-item">
              <span class="swatch" style="background: <?= h($f['culoare']) ?>"></span>
              <span class="pie__oras"><?= h($f['oras']) ?></span>
              <span class="pie__cifra"><?= (int) $f['comenzi'] ?> comenzi &middot; <?= h(number_format($f['procent'], 1, ',', '.')) ?>%</span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <details class="chart__data">
        <summary class="chart__summary">Vezi cifrele</summary>
        <table class="table">
          <thead>
            <tr><th>Oras</th><th>Comenzi</th><th>Pondere</th></tr>
          </thead>
          <tbody>
            <?php foreach ($felii as $f): ?>
              <tr>
                <td><?= h($f['oras']) ?></td>
                <td class="cell--number"><?= (int) $f['comenzi'] ?></td>
                <td class="cell--number"><?= h(number_format($f['procent'], 1, ',', '.')) ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td>Total</td><td class="cell--number"><?= (int) $totalComenzi ?></td><td class="cell--number">100,0%</td></tr>
          </tfoot>
        </table>
      </details>
    <?php endif; ?>
  </section>

  <!-- 4. Business financiar: vanzari, cost si profit pe zi -->
  <section class="card dash__card">
    <div class="dash__head">
      <h2 class="card__title">Business financiar</h2>
      <p class="card__desc">Vanzarile, costul total si profitul pe fiecare zi, pana in prezent. Profitul = vanzari - cost.</p>
    </div>

    <?php if ($nZile < 2): ?>
      <p class="empty">E nevoie de cel putin doua zile cu livrari ca sa se poata desena evolutia.</p>
    <?php else: ?>
      <div class="legend">
        <span class="legend__item"><span class="swatch swatch--s1"></span>Vanzari <span class="legend__total"><?= h(lei($totalVanzari)) ?></span></span>
        <span class="legend__item"><span class="swatch swatch--s2"></span>Cost <span class="legend__total"><?= h(lei($totalProduse + $totalCarburant)) ?></span></span>
        <span class="legend__item"><span class="swatch swatch--s3"></span>Profit <span class="legend__total"><?= h(lei($totalProfit)) ?></span></span>
      </div>

      <div class="chart chart--linie">
        <div class="chart__yaxis">
          <?php foreach ($scaraFinanciar['gradatii'] as $i => $g): ?>
            <span class="chart__tick" style="bottom: <?= round(100 - $i * 100 / (count($scaraFinanciar['gradatii']) - 1), 2) ?>%"><?= h(lei_scurt($g)) ?></span>
          <?php endforeach; ?>
        </div>

        <div class="chart__plot" style="--pas: <?= round(100 / (count($scaraFinanciar['gradatii']) - 1), 3) ?>%">
          <svg class="linie" viewBox="0 0 <?= LINIE_W ?> <?= LINIE_H ?>" preserveAspectRatio="none"
               role="img" aria-label="Evolutia zilnica a vanzarilor, costului si profitului">
            <line class="linie__zero" x1="0" y1="<?= $yZero ?>" x2="<?= LINIE_W ?>" y2="<?= $yZero ?>"></line>

            <?php foreach (SERII_FINANCIAR as $serie => $slot): ?>
              <polyline class="linie__serie linie__serie--<?= $slot ?>" points="<?= h(implode(' ', $linii[$serie])) ?>"></polyline>
            <?php endforeach; ?>
          </svg>

          <!-- Punctele stau in HTML, nu in SVG: SVG-ul e intins pe toata zona
               de desen, deci un cerc din el s-ar turti odata cu el. -->
          <div class="linie__puncte">
            <?php foreach (SERII_FINANCIAR as $serie => $slot): ?>
              <?php foreach ($puncte[$serie] as $p): ?>
                <span class="linie__punct linie__punct--<?= $slot ?>"
                      style="left: <?= round($p['x'] / LINIE_W * 100, 3) ?>%; top: <?= round($p['y'] / LINIE_H * 100, 3) ?>%"></span>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </div>

          <!-- Benzi de hover: una pentru fiecare zi, cu reper vertical si tooltip. -->
          <div class="crosshair">
            <?php foreach ($financiar as $i => $f): ?>
              <?php $b = banda($i, $nZile); ?>
              <div class="crosshair__banda" style="<?= h($b['banda']) ?>">
                <span class="crosshair__linie" style="<?= h($b['linie']) ?>"></span>
                <span class="chart__tip chart__tip--bloc" style="<?= h($b['tip']) ?>">
                  <strong><?= h(zi_lunga($f['zi'])) ?></strong>
                  <span>Vanzari: <?= h(lei($f['vanzari'])) ?></span>
                  <span>Cost: <?= h(lei($f['cost'])) ?></span>
                  <span>Profit: <?= h(lei($f['profit'])) ?></span>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="chart__xaxis chart__xaxis--puncte">
          <?php foreach ($financiar as $i => $f): ?>
            <span class="chart__xlabel chart__xlabel--punct" style="left: <?= round($i * 100 / ($nZile - 1), 3) ?>%"><?= h(zi_scurta($f['zi'])) ?></span>
          <?php endforeach; ?>
        </div>
      </div>

      <details class="chart__data">
        <summary class="chart__summary">Vezi cifrele</summary>
        <table class="table">
          <thead>
            <tr><th>Ziua livrarii</th><th>Vanzari</th><th>Cost</th><th>Profit</th></tr>
          </thead>
          <tbody>
            <?php foreach ($financiar as $f): ?>
              <tr>
                <td class="cell--nowrap"><?= h(zi_lunga($f['zi'])) ?></td>
                <td class="cell--number"><?= h(lei($f['vanzari'])) ?></td>
                <td class="cell--number"><?= h(lei($f['cost'])) ?></td>
                <td class="cell--number <?= $f['profit'] < 0 ? 'cell--minus' : '' ?>"><?= h(lei($f['profit'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              <td class="cell--number"><?= h(lei($totalVanzari)) ?></td>
              <td class="cell--number"><?= h(lei($totalProduse + $totalCarburant)) ?></td>
              <td class="cell--number <?= $totalProfit < 0 ? 'cell--minus' : '' ?>"><?= h(lei($totalProfit)) ?></td>
            </tr>
          </tfoot>
        </table>
      </details>
    <?php endif; ?>
  </section>

  <!-- 5. Activitatea soferilor: km, timp de condus si carburant -->
  <section class="card dash__card dash__card--lat">
    <div class="dash__head">
      <h2 class="card__title">Activitatea soferilor</h2>
      <p class="card__desc">
        Kilometrii, timpul de condus si carburantul consumat de fiecare sofer, insumati pe toate expedierile lui
        (si cele in tranzit: drumul e facut si carburantul e ars din clipa plecarii). Cele trei masuri au unitati
        diferite, deci fiecare are coloana si scara ei; soferii stau in aceeasi ordine in toate trei, dupa kilometri.
      </p>
    </div>

    <?php if (!$soferi): ?>
      <p class="empty">Nu exista soferi.</p>
    <?php else: ?>
      <div class="rank">
        <div class="rank__head">
          <span class="rank__nume"></span>
          <?php foreach ($coloaneSoferi as $c): ?>
            <span class="rank__col">
              <span class="swatch swatch--<?= h($c['slot']) ?>"></span>
              <?= h($c['titlu']) ?>
              <span class="legend__total"><?= h($c['total']) ?></span>
            </span>
          <?php endforeach; ?>
        </div>

        <?php foreach ($soferi as $s): ?>
          <div class="rank__row">
            <span class="rank__nume"><?= h($s['Nume']) ?></span>

            <?php foreach ($coloaneSoferi as $masura => $c): ?>
              <div class="rank__cell" data-eticheta="<?= h($c['titlu']) ?>">
                <div class="rank__track">
                  <div class="rank__bar rank__bar--<?= h($c['slot']) ?><?= (float) $s[$masura] > 0 ? ' rank__bar--minim' : '' ?>"
                       style="width: <?= lungime($s[$masura], $maxSofer[$masura]) ?>%">
                    <span class="chart__tip chart__tip--bloc">
                      <strong><?= h($s['Nume']) ?></strong>
                      <span><?= (int) $s['expedieri'] ?> expedieri &middot; <?= h(numar($s['km'])) ?> km</span>
                      <span>Condus: <?= h(ore($s['minute'])) ?></span>
                      <span>Carburant: <?= h(numar($s['litri'])) ?> L &middot; <?= h(lei($s['cost_carburant'])) ?></span>
                    </span>
                  </div>
                </div>
                <span class="rank__value"><?= h(masura_scurta($masura, $s)) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <p class="rank__nota">
        Litrii se calculeaza din kilometri, cu consumul mediu de <?= (int) OptimizareRuteService::CONSUM_L_100KM ?> L/100 km
        al dubei de curier; costul in lei e cel inregistrat pe expediere.
      </p>

      <details class="chart__data">
        <summary class="chart__summary">Vezi cifrele</summary>
        <table class="table">
          <thead>
            <tr><th>Sofer</th><th>Oras de baza</th><th>Expedieri</th><th>Kilometri</th><th>Timp de condus</th><th>Carburant</th><th>Cost carburant</th></tr>
          </thead>
          <tbody>
            <?php foreach ($soferi as $s): ?>
              <tr>
                <td><?= h($s['Nume']) ?></td>
                <td><?= h($s['Oras_baza']) ?></td>
                <td class="cell--number"><?= (int) $s['expedieri'] ?></td>
                <td class="cell--number"><?= h(numar($s['km'])) ?> km</td>
                <td class="cell--number cell--nowrap"><?= h(ore($s['minute'])) ?></td>
                <td class="cell--number"><?= h(numar($s['litri'])) ?> L</td>
                <td class="cell--number"><?= h(lei($s['cost_carburant'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              <td></td>
              <td class="cell--number"><?= (int) $totalSofer['expedieri'] ?></td>
              <td class="cell--number"><?= h(numar($totalSofer['km'])) ?> km</td>
              <td class="cell--number cell--nowrap"><?= h(ore($totalSofer['minute'])) ?></td>
              <td class="cell--number"><?= h(numar($totalSofer['litri'])) ?> L</td>
              <td class="cell--number"><?= h(lei($totalSofer['cost'])) ?></td>
            </tr>
          </tfoot>
        </table>
      </details>
    <?php endif; ?>
  </section>

  <!-- 6. Cat de folosit e fiecare traseu -->
  <section class="card dash__card dash__card--lat">
    <div class="dash__head">
      <h2 class="card__title">Cat de folosit e fiecare traseu</h2>
      <p class="card__desc">
        Numarul de curse plecate pe fiecare traseu, de la cel mai folosit la cel mai putin folosit.
        Intra toate expedierile, nu doar cele livrate: drumul e facut si daca coletul s-a intors.
        Traseele pe care n-a plecat nimeni raman in lista, cu bara goala.
      </p>
    </div>

    <?php if (!$trasee): ?>
      <p class="empty">Nu exista trasee definite.</p>
    <?php else: ?>
      <details class="pliant">
        <summary class="pliant__buton">
          <span class="pliant__sageata" aria-hidden="true">&rsaquo;</span>
          <span class="pliant__inchis">Arata traseele</span>
          <span class="pliant__deschis">Ascunde traseele</span>
          <span class="pliant__hint"><?= count($trasee) ?> trasee &middot; <?= (int) $totalCurse ?> curse</span>
        </summary>

        <div class="pliant__continut">
      <div class="legend">
        <span class="legend__item"><span class="swatch swatch--s1"></span>Curse pe traseu</span>
        <span class="legend__item">Trasee <span class="legend__total"><?= count($trasee) ?></span></span>
        <span class="legend__item">Curse <span class="legend__total"><?= (int) $totalCurse ?></span></span>
        <span class="legend__item">Nefolosite <span class="legend__total"><?= (int) $traseeNefolosite ?></span></span>
      </div>

      <div class="rank rank--singur">
        <?php foreach ($trasee as $t): ?>
          <div class="rank__row">
            <span class="rank__nume rank__nume--traseu">
              <?= h(traseu($t)) ?>
              <?php if ($areExtreme && (int) $t['curse'] === $maxCurse): ?>
                <span class="chip chip--max">Cel mai folosit</span>
              <?php elseif ($areExtreme && (int) $t['curse'] === $minCurse): ?>
                <span class="chip chip--min"><?= h($etichetaMin) ?></span>
              <?php endif; ?>
            </span>

            <div class="rank__cell" data-eticheta="Curse">
              <div class="rank__track">
                <div class="rank__bar rank__bar--s1<?= (int) $t['curse'] > 0 ? ' rank__bar--minim' : '' ?>"
                     style="width: <?= lungime($t['curse'], $maxCurse) ?>%">
                  <span class="chart__tip chart__tip--bloc">
                    <strong><?= h(traseu($t)) ?></strong>
                    <span><?= (int) $t['curse'] ?> curse &middot; <?= h(numar($t['km'])) ?> km facuti</span>
                    <span>Traseu: <?= h(numar($t['Distanta_km'])) ?> km &middot; <?= h(ore($t['Durata_min'])) ?></span>
                    <span><?= h(TIPURI_STRADA[$t['tip_strada']] ?? $t['tip_strada']) ?></span>
                  </span>
                </div>
              </div>
              <span class="rank__value"><?= (int) $t['curse'] ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <details class="chart__data">
        <summary class="chart__summary">Vezi cifrele</summary>
        <table class="table">
          <thead>
            <tr><th>Traseu</th><th>Tip drum</th><th>Distanta</th><th>Curse</th><th>Km facuti</th></tr>
          </thead>
          <tbody>
            <?php foreach ($trasee as $t): ?>
              <tr>
                <td class="cell--nowrap"><?= h(traseu($t)) ?></td>
                <td><span class="tag"><?= h(TIPURI_STRADA[$t['tip_strada']] ?? $t['tip_strada']) ?></span></td>
                <td class="cell--number"><?= h(numar($t['Distanta_km'])) ?> km</td>
                <td class="cell--number"><?= (int) $t['curse'] ?></td>
                <td class="cell--number"><?= h(numar($t['km'])) ?> km</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              <td></td>
              <td></td>
              <td class="cell--number"><?= (int) $totalCurse ?></td>
              <td class="cell--number"><?= h(numar($totalKmTrasee)) ?> km</td>
            </tr>
          </tfoot>
        </table>
      </details><!-- /Vezi cifrele -->
        </div>
      </details><!-- /Arata traseele -->
    <?php endif; ?>
  </section>

  <!-- 7. Cursele soferilor: cine, pe ce traseu, cati km -->
  <section class="card dash__card dash__card--lat">
    <div class="dash__head">
      <h2 class="card__title">Cursele soferilor</h2>
      <p class="card__desc">
        Fiecare expediere cu soferul care a condus-o, traseul si kilometrii traseului.
        Randurile sunt grupate pe sofer si asezate cronologic.
      </p>
    </div>

    <?php if (!$curse): ?>
      <p class="empty">Nu exista expedieri repartizate unui sofer.</p>
    <?php else: ?>
      <details class="pliant">
        <summary class="pliant__buton">
          <span class="pliant__sageata" aria-hidden="true">&rsaquo;</span>
          <span class="pliant__inchis">Arata cursele</span>
          <span class="pliant__deschis">Ascunde cursele</span>
          <span class="pliant__hint"><?= count($curse) ?> curse</span>
        </summary>

        <div class="pliant__continut pliant__continut--tabel">
        <table class="table">
          <thead>
            <tr><th>Sofer</th><th>Traseu</th><th>Tip drum</th><th>Kilometri</th><th>Timp de condus</th><th>AWB</th><th>Expediat</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($curse as $c): ?>
              <tr<?= $c['primul'] ? ' class="row--grup"' : '' ?>>
                <td class="cell--nowrap"><?= $c['primul'] ? h($c['Nume']) : '' ?></td>
                <td class="cell--nowrap"><?= h(traseu($c)) ?></td>
                <td><span class="tag"><?= h(TIPURI_STRADA[$c['tip_strada']] ?? $c['tip_strada']) ?></span></td>
                <td class="cell--number"><?= h(numar($c['Distanta_km'])) ?> km</td>
                <td class="cell--number cell--nowrap"><?= h(ore($c['Durata_min'])) ?></td>
                <td class="cell--nowrap"><?= h($c['awb']) ?></td>
                <td class="cell--nowrap"><?= h(data_ora($c['Data_expediere'])) ?></td>
                <td><span class="tag <?= h(clasa_status($c['Status_expediere'])) ?>"><?= h($c['Status_expediere']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              <td class="cell--nowrap"><?= count($curse) ?> curse</td>
              <td></td>
              <td class="cell--number"><?= h(numar($totalKmCurse)) ?> km</td>
              <td class="cell--number cell--nowrap"><?= h(ore($totalMinCurse)) ?></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
        </div>
      </details>
    <?php endif; ?>
  </section>

  <!-- 8. Radar: profitul adus de fiecare sofer -->
  <section class="card dash__card dash__card--lat">
    <div class="dash__head">
      <h2 class="card__title">Cine a fost cel mai profitabil sofer</h2>
      <p class="card__desc">
        Profitul adus de fiecare sofer pe traseele lui: cat a vandut minus marfa si carburant,
        pe expedierile livrate. O axa pe sofer; cu cat coltul e mai spre marginea panzei,
        cu atat rezultatul e mai bun.
        <?php if ($radarZero !== null): ?>
          Inelul ingrosat e linia lui zero: ce cade in interiorul lui inseamna pierdere.
        <?php else: ?>
          Centrul panzei e <?= h(lei_scurt($scaraRadar['min'])) ?> lei.
        <?php endif; ?>
      </p>
    </div>

    <?php if ($nSoferi < 3): ?>
      <p class="empty">E nevoie de cel putin trei soferi ca sa se poata desena un radar.</p>
    <?php else: ?>
      <p class="radar__concluzie">
        <?php if ($celMaiBun['profit'] > 0): ?>
          Cel mai profitabil: <strong><?= h($celMaiBun['Nume']) ?></strong>,
          cu <strong class="cell--plus"><?= h(lei($celMaiBun['profit'])) ?></strong> profit
          din <?= (int) $celMaiBun['livrari'] ?> livrari.
        <?php else: ?>
          Niciun sofer nu iese pe plus. Cel mai bun rezultat:
          <strong><?= h($celMaiBun['Nume']) ?></strong>, cu cea mai mica pierdere -
          <strong class="cell--minus"><?= h(lei($celMaiBun['profit'])) ?></strong>
          din <?= (int) $celMaiBun['livrari'] ?> livrari.
        <?php endif; ?>
      </p>

      <div class="radar">
        <div class="radar__panza">
          <svg class="radar__svg" viewBox="0 0 <?= RADAR_BOX ?> <?= RADAR_BOX ?>"
               role="img" aria-label="Profitul fiecarui sofer, pe cate o axa">
            <?php foreach ($radarInele as $inel): ?>
              <polygon class="radar__inel" points="<?= h($inel['puncte']) ?>"></polygon>
            <?php endforeach; ?>

            <?php foreach ($radarSpite as $spita): ?>
              <line class="radar__spita" x1="<?= RADAR_C ?>" y1="<?= RADAR_C ?>"
                    x2="<?= $spita['x'] ?>" y2="<?= $spita['y'] ?>"></line>
            <?php endforeach; ?>

            <?php if ($radarZero !== null): ?>
              <polygon class="radar__zero" points="<?= h($radarZero) ?>"></polygon>
            <?php endif; ?>

            <polygon class="radar__serie" points="<?= h($radarSerie) ?>"></polygon>

            <?php foreach ($radarPuncte as $p): ?>
              <circle class="radar__punct" cx="<?= $p['x'] ?>" cy="<?= $p['y'] ?>" r="5">
                <title><?= h($p['sofer']['Nume'] . ': ' . lei($p['sofer']['profit'])) ?></title>
              </circle>
            <?php endforeach; ?>
          </svg>

          <?php foreach ($radarGradatii as $g): ?>
            <span class="radar__gradatie" style="top: <?= $g['sus'] ?>%"><?= h(lei_scurt($g['valoare'])) ?></span>
          <?php endforeach; ?>

          <?php foreach ($radarEtichete as $e): ?>
            <span class="radar__nume" style="left: <?= $e['x'] ?>%; top: <?= $e['y'] ?>%">
              <?= h($e['sofer']['Nume']) ?>
              <span class="radar__cifra <?= $e['sofer']['profit'] < 0 ? 'cell--minus' : 'cell--plus' ?>"><?= h(lei($e['sofer']['profit'])) ?></span>
            </span>
          <?php endforeach; ?>
        </div>

        <ol class="radar__clasament">
          <?php foreach ($soferiProfit as $i => $s): ?>
            <li class="radar__loc<?= $i === 0 ? ' radar__loc--primul' : '' ?>">
              <span class="radar__rang"><?= $i + 1 ?></span>
              <span class="radar__sofer">
                <?= h($s['Nume']) ?>
                <span class="radar__baza"><?= h($s['Oras_baza']) ?> &middot; <?= (int) $s['livrari'] ?> livrari</span>
              </span>
              <span class="radar__profit <?= $s['profit'] < 0 ? 'cell--minus' : 'cell--plus' ?>"><?= h(lei($s['profit'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>

      <details class="chart__data">
        <summary class="chart__summary">Vezi cifrele</summary>
        <table class="table">
          <thead>
            <tr><th>Sofer</th><th>Baza</th><th>Livrari</th><th>Vanzari</th><th>Marfa</th><th>Carburant</th><th>Profit</th></tr>
          </thead>
          <tbody>
            <?php foreach ($soferiProfit as $s): ?>
              <tr>
                <td class="cell--nowrap"><?= h($s['Nume']) ?></td>
                <td class="cell--nowrap"><?= h($s['Oras_baza']) ?></td>
                <td class="cell--number"><?= (int) $s['livrari'] ?></td>
                <td class="cell--number"><?= h(lei($s['vanzari'])) ?></td>
                <td class="cell--number"><?= h(lei($s['cost_produse'])) ?></td>
                <td class="cell--number"><?= h(lei($s['cost_carburant'])) ?></td>
                <td class="cell--number <?= $s['profit'] < 0 ? 'cell--minus' : '' ?>"><?= h(lei($s['profit'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              <td></td>
              <td class="cell--number"><?= (int) array_sum(array_column($soferiProfit, 'livrari')) ?></td>
              <td class="cell--number"><?= h(lei(array_sum(array_column($soferiProfit, 'vanzari')))) ?></td>
              <td class="cell--number"><?= h(lei(array_sum(array_column($soferiProfit, 'cost_produse')))) ?></td>
              <td class="cell--number"><?= h(lei(array_sum(array_column($soferiProfit, 'cost_carburant')))) ?></td>
              <td class="cell--number <?= $totalProfitSoferi < 0 ? 'cell--minus' : '' ?>"><?= h(lei($totalProfitSoferi)) ?></td>
            </tr>
          </tfoot>
        </table>
      </details>
    <?php endif; ?>
  </section>

  <!-- 9. Raportul Power BI publicat, adus in pagina -->
  <section class="card dash__card dash__card--lat">
    <div class="dash__head">
      <h2 class="card__title">Raport Power BI</h2>
      <p class="card__desc">
        Raportul publicat in Power BI, adus direct in pagina. Se incarca de la Microsoft,
        deci cere sa fii logat in Power BI cu un cont care are drept pe raport.
      </p>
    </div>

    <div class="pbi">
      <iframe class="pbi__frame" src="<?= h(PBI_EMBED) ?>"
              title="Raport Power BI" loading="lazy" allowfullscreen></iframe>
    </div>

    <p class="pbi__nota">
      Daca in locul raportului ramane un chenar gol sau o cerere de autentificare,
      <a href="<?= h(PBI_LINK) ?>" target="_blank" rel="noopener">deschide-l direct in Power BI</a>.
    </p>
  </section>

  </div>
</main>

</body>
</html>
