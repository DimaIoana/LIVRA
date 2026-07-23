<?php

/**
 * Vremea pe orase, din API-ul public ANM / meteoromania.ro.
 *
 * Datele se actualizeaza orar, asa ca raspunsul se pune in cache pe disc
 * (cache/meteo.json) si se reia la ~30 min. Daca API-ul nu raspunde, se
 * foloseste cache-ul vechi; daca nici acesta nu exista, nu se aplica nicio
 * ajustare (degradare gratioasa).
 *
 * Reguli de ajustare a timpului (vezi docs/algoritm_optimizare_rute.md):
 *   - ninsoare      => +60 min
 *   - ploaie        => +30 min
 *   - fara / necunoscut => +0 min
 *
 * Statiile au nume cu majuscule fara diacritice (ex: "CLUJ-NAPOCA",
 * "BRASOV GHIMBAV", "BUCURESTI BANEASA"), deci maparea se face pe prefix.
 */
class MeteoService
{
    const API_URL = 'https://www.meteoromania.ro/wp-json/meteoapi/v2/starea-vremii';
    const CACHE_TTL = 1800; // 30 minute

    const AJUSTARE_NINSOARE = 60;
    const AJUSTARE_PLOAIE = 30;

    private $cacheFile;
    private $statii = null; // nume normalizat => properties

    public function __construct($cacheDir = null)
    {
        $cacheDir = $cacheDir !== null ? $cacheDir : __DIR__ . '/../../cache';
        $this->cacheFile = rtrim($cacheDir, '/\\') . DIRECTORY_SEPARATOR . 'meteo.json';
    }

    /** MAJUSCULE fara diacritice, pentru potrivirea numelor de oras/statie. */
    private static function norm($s)
    {
        $s = mb_strtoupper(trim((string) $s), 'UTF-8');

        return strtr($s, [
            'Ș' => 'S', 'Ş' => 'S', 'Ț' => 'T', 'Ţ' => 'T',
            'Ă' => 'A', 'Â' => 'A', 'Î' => 'I',
        ]);
    }

    /** litere mici fara diacritice, pentru analiza fenomenului meteo. */
    private static function normText($s)
    {
        $s = mb_strtolower(trim((string) $s), 'UTF-8');

        return strtr($s, [
            'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
            'ă' => 'a', 'â' => 'a', 'î' => 'i',
        ]);
    }

    /** Harta nume statie normalizat => properties (incarcata o singura data). */
    private function statii()
    {
        if ($this->statii !== null) {
            return $this->statii;
        }

        $this->statii = [];
        foreach ($this->incarcaFeatures() as $f) {
            $p = $f['properties'] ?? null;
            if (is_array($p) && isset($p['nume'])) {
                $this->statii[self::norm($p['nume'])] = $p;
            }
        }

        return $this->statii;
    }

    /** Features din cache (daca e proaspat) sau din API; fallback pe cache vechi. */
    private function incarcaFeatures()
    {
        if (is_file($this->cacheFile) && (time() - filemtime($this->cacheFile) < self::CACHE_TTL)) {
            $j = json_decode((string) file_get_contents($this->cacheFile), true);
            if (isset($j['features'])) {
                return $j['features'];
            }
        }

        $body = $this->descarca();
        if ($body !== null) {
            $j = json_decode($body, true);
            if (isset($j['features'])) {
                @mkdir(dirname($this->cacheFile), 0777, true);
                @file_put_contents($this->cacheFile, $body);
                return $j['features'];
            }
        }

        // API indisponibil: foloseste cache-ul vechi daca exista.
        if (is_file($this->cacheFile)) {
            $j = json_decode((string) file_get_contents($this->cacheFile), true);
            if (isset($j['features'])) {
                return $j['features'];
            }
        }

        return [];
    }

    /** Descarca JSON-ul din API. Intoarce body sau null la eroare. */
    private function descarca()
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'PRIMUL/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($body !== false && $code === 200) ? $body : null;
    }

    /** Statia potrivita unui oras (exact sau pe prefix; prefera Baneasa la Bucuresti). */
    private function statiePentru($oras)
    {
        $n = self::norm($oras);
        $map = $this->statii();

        if (isset($map[$n])) {
            return $map[$n];
        }

        $candidat = null;
        foreach ($map as $nume => $p) {
            if (strpos($nume, $n) === 0) {
                if (strpos($nume, 'BANEASA') !== false) {
                    return $p; // preferinta pentru Bucuresti
                }
                if ($candidat === null) {
                    $candidat = $p;
                }
            }
        }

        return $candidat;
    }

    /** True daca fenomenul indica ninsoare (sau strat de zapada masurat). */
    public static function areNinsoare(array $p)
    {
        $fen = self::normText($p['fenomen_e'] ?? '');
        if (preg_match('/ninso|ninge|lapovi|viscol/', $fen)) {
            return true;
        }

        $z = trim((string) ($p['zapada'] ?? ''));
        if ($z !== '' && self::normText($z) !== 'indisponibil') {
            $num = (float) str_replace(',', '.', $z);
            if ($num > 0) {
                return true;
            }
        }

        return false;
    }

    /** True daca fenomenul indica ploaie / precipitatii lichide. */
    public static function arePloaie(array $p)
    {
        $fen = self::normText($p['fenomen_e'] ?? '');

        return (bool) preg_match('/ploaie|aversa|averse|burnita|bura|precipitat/', $fen);
    }

    /**
     * Vremea si ajustarea pentru un oras.
     *
     * @return array gasit, zapada, ploaie, ajustare (min), text, statie
     */
    public function vremeOras($oras)
    {
        $p = $this->statiePentru($oras);
        if ($p === null) {
            return [
                'gasit' => false, 'zapada' => false, 'ploaie' => false,
                'ajustare' => 0, 'text' => 'vreme indisponibila', 'statie' => null,
            ];
        }

        $ninsoare = self::areNinsoare($p);
        $ploaie = !$ninsoare && self::arePloaie($p);

        if ($ninsoare) {
            $ajustare = self::AJUSTARE_NINSOARE;
            $text = 'ninsoare';
        } elseif ($ploaie) {
            $ajustare = self::AJUSTARE_PLOAIE;
            $text = 'ploaie';
        } else {
            $ajustare = 0;
            $text = 'fara precipitatii';
        }

        return [
            'gasit' => true, 'zapada' => $ninsoare, 'ploaie' => $ploaie,
            'ajustare' => $ajustare, 'text' => $text, 'statie' => $p['nume'] ?? null,
        ];
    }

    /**
     * Ajustarea de vreme pentru o ruta: cea mai grea vreme dintre orasul de
     * origine (depozit) si cel de destinatie (client).
     *
     * @return array ajustare (min), text explicativ, origine, destinatie
     */
    public function ajustareRuta($orasOrigine, $orasDestinatie)
    {
        $o = $this->vremeOras($orasOrigine);
        $d = $this->vremeOras($orasDestinatie);

        // Endpoint-ul cu vremea cea mai grea da ajustarea.
        $rau = $o['ajustare'] >= $d['ajustare'] ? $o : $d;
        $orasRau = $o['ajustare'] >= $d['ajustare'] ? $orasOrigine : $orasDestinatie;

        if ($rau['ajustare'] > 0) {
            $text = $rau['text'] . ' in ' . $orasRau;
        } else {
            $text = 'fara precipitatii';
        }

        return [
            'ajustare' => $rau['ajustare'],
            'text' => $text,
            'origine' => $o,
            'destinatie' => $d,
        ];
    }
}
