<?php

require_once __DIR__ . '/BaseRepository.php';

/**
 * Acces la date pentru tabela `rute`.
 */
class RutaRepository extends BaseRepository
{
    protected function table()
    {
        return 'rute';
    }

    protected function primaryKey()
    {
        return 'RutaID';
    }

    protected function columns()
    {
        return ['Oras_origine', 'Oras_destinatie', 'Distanta_km', 'Durata_min', 'viteza', 'tip_strada'];
    }

    protected function sortableColumns()
    {
        return ['RutaID', 'Oras_origine', 'Oras_destinatie', 'Distanta_km', 'Durata_min', 'viteza', 'tip_strada'];
    }

    protected function searchableColumns()
    {
        return ['Oras_origine', 'Oras_destinatie'];
    }

    public function validate(array $input)
    {
        $errors = [];

        $origine = $this->validText($errors, $input, 'Oras_origine', 'Depozitul', 50);
        $destinatie = $this->validText($errors, $input, 'Oras_destinatie', 'Destinatia client', 50);
        $distanta = $this->validInt($errors, $input, 'Distanta_km', 'Distanta', 1);
        $durata = $this->validInt($errors, $input, 'Durata_min', 'Timpul de condus', 1);
        $viteza = $this->validInt($errors, $input, 'viteza', 'Viteza', 1);
        $tipStrada = $this->validEnum($errors, $input, 'tip_strada', 'Tipul de strada',
            ['autostrada', 'dn', 'drum judetean', 'drum comunal']);

        // O ruta care incepe si se termina in acelasi oras nu are sens.
        if ($origine !== '' && mb_strtolower($origine) === mb_strtolower($destinatie)) {
            $errors[] = 'Destinatia client trebuie sa fie diferita de depozit.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'Oras_origine' => $origine,
                'Oras_destinatie' => $destinatie,
                'Distanta_km' => $distanta,
                'Durata_min' => $durata,
                'viteza' => $viteza,
                'tip_strada' => $tipStrada,
            ],
        ];
    }
}
