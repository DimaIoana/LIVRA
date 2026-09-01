<?php

/**
 * Google Analytics (GA4) - codul de masurare, pus in <head>-ul fiecarei pagini:
 *
 *     require_once __DIR__ . '/_analytics.php';               (din src/frontend/)
 *     require_once __DIR__ . '/src/frontend/_analytics.php';  (din radacina)
 *
 * E singurul JavaScript din aplicatie si nu atinge datele: paginile se randeaza
 * la fel ca inainte, integral pe server. Daca scriptul e blocat de browser sau
 * nu se incarca, nu se schimba nimic in ce vede utilizatorul.
 *
 * Local nu se trimite nimic (vezi analytics_activ), ca testele de pe XAMPP sa nu
 * se amestece cu vizitatorii reali. Asa fisierul poate fi identic local si pe
 * server, fara nicio linie de schimbat la fiecare urcare.
 */

if (!defined('GA_ID')) {
    /** Codul contului de Google Analytics (Admin > Fluxuri de date). */
    define('GA_ID', 'G-1LY95M3J22');
}

if (!function_exists('analytics_activ')) {
    /**
     * Raportam doar de pe gazduire. De pe localhost/127.0.0.1 nu se trimite
     * nimic: altfel fiecare test local ar aparea ca vizita in rapoarte.
     */
    function analytics_activ()
    {
        $gazda = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $gazda = explode(':', $gazda)[0];

        if ($gazda === '' || $gazda === 'localhost' || $gazda === '127.0.0.1' || $gazda === '::1') {
            return false;
        }

        // Nume de dezvoltare de tip "livra.local", "proiect.test".
        return !preg_match('/\.(local|test|localhost)$/', $gazda);
    }

    /**
     * In ce parte a aplicatiei e vizitatorul. Se citeste direct din sesiune,
     * fara admin_logat()/client_logat(): alea reinnoiesc contorul de
     * inactivitate, iar un fisier de statistica nu are ce sa umble la sesiune.
     *
     * In Google Analytics valorile astea ajung ca parametri ai evenimentului
     * page_view; ca sa apara in rapoarte trebuie inregistrate o data, din
     * Admin > Definitii personalizate > Dimensiuni personalizate, cu numele
     * exact "zona" si "autentificat".
     */
    function analytics_zona()
    {
        if (!empty($_SESSION['admin'])) {
            return 'backoffice';
        }

        if (!empty($_SESSION['client_id'])) {
            return 'magazin';
        }

        return 'public';
    }
}

if (analytics_activ()) {
    $ga_zona = analytics_zona();
    $ga_config = [
        'zona' => $ga_zona,
        'autentificat' => $ga_zona === 'public' ? 'nu' : 'da',
    ];
    ?>
<!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= rawurlencode(GA_ID) ?>"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', <?= json_encode(GA_ID) ?>, <?= json_encode($ga_config) ?>);
  </script>
<?php } ?>
