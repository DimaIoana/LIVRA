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
        return ['Oras_origine', 'Oras_destinatie', 'Distanta_km'];
    }

    protected function sortableColumns()
    {
        return ['RutaID', 'Oras_origine', 'Oras_destinatie', 'Distanta_km'];
    }

    protected function searchableColumns()
    {
        return ['Oras_origine', 'Oras_destinatie'];
    }

    public function validate(array $input)
    {
        $errors = [];

        $origine = $this->validText($errors, $input, 'Oras_origine', 'Orasul de origine', 50);
        $destinatie = $this->validText($errors, $input, 'Oras_destinatie', 'Orasul de destinatie', 50);
        $distanta = $this->validInt($errors, $input, 'Distanta_km', 'Distanta', 1);

        // O ruta care incepe si se termina in acelasi oras nu are sens.
        if ($origine !== '' && mb_strtolower($origine) === mb_strtolower($destinatie)) {
            $errors[] = 'Orasul de destinatie trebuie sa fie diferit de cel de origine.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'Oras_origine' => $origine,
                'Oras_destinatie' => $destinatie,
                'Distanta_km' => $distanta,
            ],
        ];
    }
}
