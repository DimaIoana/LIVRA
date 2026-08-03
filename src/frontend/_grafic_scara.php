<?php

/**
 * Scarile graficelor, folosite de orice pagina care deseneaza bare sau linii
 * (business.php, laborator_rute.php). Fara stare, doar calcul.
 *
 * Se include o singura data pe pagina:
 *   require_once __DIR__ . '/_grafic_scara.php';
 */

/**
 * Scara axei Y: un maxim rotund si diviziunile lui, de sus in jos.
 * Pasul se rotunjeste in sus la 1 / 2 / 2.5 / 5 / 10 x o putere a lui 10, ca
 * gradatiile sa fie cifre citibile (0, 500, 1.000, ...).
 */
function scara_y($max, $diviziuni = 4)
{
    if ($max <= 0) {
        return ['max' => 1, 'gradatii' => [1, 0]];
    }

    $pas = pas_rotund($max, $diviziuni);

    $gradatii = [];
    for ($i = $diviziuni; $i >= 0; $i--) {
        $gradatii[] = $pas * $i;
    }

    return ['max' => $pas * $diviziuni, 'gradatii' => $gradatii];
}

/**
 * Pasul rotund al unei scari, pentru un interval si un numar de diviziuni.
 * Aceleasi trepte ca la `scara_y`: 1 / 2 / 2.5 / 5 / 10 x o putere a lui 10.
 */
function pas_rotund($interval, $diviziuni)
{
    $tinta = $interval / $diviziuni;
    $exp = pow(10, floor(log10($tinta)));

    foreach ([1, 2, 2.5, 5] as $m) {
        if ($m * $exp >= $tinta) {
            return $m * $exp;
        }
    }

    return 10 * $exp;
}

/**
 * Scara axei Y cand valorile pot fi si negative (profitul pe pierdere).
 * Capetele sunt multipli ai pasului, deci linia lui zero cade fix pe o
 * gradatie, iar zero e mereu inclus in interval.
 *
 * @return array ['min' => float, 'max' => float, 'gradatii' => float[]]
 */
function scara_interval($min, $max, $diviziuni = 4)
{
    $min = min(0, (float) $min);
    $max = max(0, (float) $max);

    if ($max - $min <= 0) {
        return ['min' => 0, 'max' => 1, 'gradatii' => [1, 0]];
    }

    $pas = pas_rotund($max - $min, $diviziuni);
    $jos = floor($min / $pas) * $pas;
    $sus = ceil($max / $pas) * $pas;

    $gradatii = [];
    for ($i = (int) round(($sus - $jos) / $pas); $i >= 0; $i--) {
        $gradatii[] = $jos + $i * $pas;
    }

    return ['min' => $jos, 'max' => $sus, 'gradatii' => $gradatii];
}

/** Inaltimea unei bare, in procente din scara. */
function inaltime($valoare, $max)
{
    if ($max <= 0) {
        return 0;
    }

    return round(max(0, (float) $valoare) / $max * 100, 2);
}
