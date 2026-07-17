<?php

require_once __DIR__ . '/BaseRepository.php';

/**
 * Acces la date pentru tabela `soferi`.
 */
class SoferRepository extends BaseRepository
{
    protected function table()
    {
        return 'soferi';
    }

    protected function primaryKey()
    {
        return 'SoferID';
    }

    protected function columns()
    {
        return ['Nume', 'Telefon', 'Oras_baza', 'Data_angajare'];
    }

    protected function sortableColumns()
    {
        return ['SoferID', 'Nume', 'Telefon', 'Oras_baza', 'Data_angajare'];
    }

    protected function searchableColumns()
    {
        return ['Nume', 'Oras_baza'];
    }

    public function validate(array $input)
    {
        $errors = [];

        $nume = $this->validText($errors, $input, 'Nume', 'Numele', 50);
        // Fara validare de format, la fel ca la clienti.
        $telefon = $this->validText($errors, $input, 'Telefon', 'Telefonul', 20, false);
        $oras_baza = $this->validText($errors, $input, 'Oras_baza', 'Orasul de baza', 50);

        $data_angajare = trim((string) ($input['Data_angajare'] ?? ''));
        if ($data_angajare === '') {
            $data_angajare = date('Y-m-d');
        } else {
            $data_angajare = $this->validDate($errors, $input, 'Data_angajare', 'Data angajarii');
        }

        return [
            'errors' => $errors,
            'data' => [
                'Nume' => $nume,
                'Telefon' => $telefon,
                'Oras_baza' => $oras_baza,
                'Data_angajare' => $data_angajare,
            ],
        ];
    }
}
