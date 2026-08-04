<?php

/**
 * Pretul carburantului in Romania, din API-ul public PretCarburant.ro.
 *
 * Folosim pretul mediu national la motorina standard (endpoint /preturi/minime,
 * fara rate limit, cache 5 min la sursa). Il punem si noi in cache pe disc
 * (cache/carburant.json) pe cateva ore, fiindca pretul se schimba lent. Daca
 * API-ul nu raspunde, se foloseste cache-ul vechi sau un pret implicit.
 *
 * Atribuire (cerinta licentei CC-BY 4.0): "Sursa: PretCarburant.ro
 * (https://pretcarburant.ro)".
 */
class CarburantService
{
    const API_URL = 'https://pretcarburant.ro/api/v1/preturi/minime';
    const CACHE_TTL = 21600;        // 6 ore
    const PRET_IMPLICIT = 10.0;     // lei/L, fallback daca API-ul nu raspunde
    const ATRIBUIRE = 'Sursa: PretCarburant.ro (https://pretcarburant.ro)';

    private $cacheFile;
    private $pret = null; // memoizare in cadrul cererii

    public function __construct($cacheDir = null)
    {
        $cacheDir = $cacheDir !== null ? $cacheDir : __DIR__ . '/../../cache';
        $this->cacheFile = rtrim($cacheDir, '/\\') . DIRECTORY_SEPARATOR . 'carburant.json';
    }

    /** Pretul motorinei standard (lei/L), media nationala. */
    public function pretMotorina()
    {
        if ($this->pret !== null) {
            return $this->pret;
        }

        $data = $this->incarca();
        $pret = $data['preturi']['motorina_standard']['mediu'] ?? null;

        $this->pret = ($pret !== null && (float) $pret > 0) ? (float) $pret : self::PRET_IMPLICIT;

        return $this->pret;
    }

    /** JSON din cache (daca e proaspat) sau din API; fallback pe cache vechi. */
    private function incarca()
    {
        if (is_file($this->cacheFile) && (time() - filemtime($this->cacheFile) < self::CACHE_TTL)) {
            $j = json_decode((string) file_get_contents($this->cacheFile), true);
            if (isset($j['preturi'])) {
                return $j;
            }
        }

        $body = $this->descarca();
        if ($body !== null) {
            $j = json_decode($body, true);
            if (isset($j['preturi'])) {
                @mkdir(dirname($this->cacheFile), 0777, true);
                @file_put_contents($this->cacheFile, $body);
                return $j;
            }
        }

        if (is_file($this->cacheFile)) {
            $j = json_decode((string) file_get_contents($this->cacheFile), true);
            if (isset($j['preturi'])) {
                return $j;
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
            CURLOPT_USERAGENT => 'anthropic-ai LIVRA/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($body !== false && $code === 200) ? $body : null;
    }
}
