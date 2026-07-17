<?php

require_once __DIR__ . '/BaseRepository.php';

/**
 * Acces la date pentru tabela `clienti`.
 */
class ClientRepository extends BaseRepository
{
    const TIPURI = ['Persoana fizica', 'Persoana juridica'];

    protected function table()
    {
        return 'clienti';
    }

    protected function primaryKey()
    {
        return 'ClientID';
    }

    protected function columns()
    {
        return ['Nume', 'Tip', 'Email', 'Telefon', 'Oras', 'Data_inregistrare'];
    }

    protected function sortableColumns()
    {
        return ['ClientID', 'Nume', 'Tip', 'Email', 'Telefon', 'Oras', 'Data_inregistrare'];
    }

    protected function searchableColumns()
    {
        return ['Nume', 'Email', 'Oras'];
    }

    public function validate(array $input)
    {
        $errors = [];

        $nume = $this->validText($errors, $input, 'Nume', 'Numele', 50);
        $tip = $this->validEnum($errors, $input, 'Tip', 'Tipul', self::TIPURI);

        $email = trim((string) ($input['Email'] ?? ''));
        if ($email === '') {
            $errors[] = 'Emailul este obligatoriu.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Emailul nu are un format valid.';
        } elseif (mb_strlen($email) > 150) {
            $errors[] = 'Emailul poate avea maxim 150 de caractere.';
        }

        // Fara validare de format - telefonul se salveaza asa cum e scris.
        $telefon = $this->validText($errors, $input, 'Telefon', 'Telefonul', 20, false);

        $oras = $this->validText($errors, $input, 'Oras', 'Orasul', 30);

        $data_inregistrare = trim((string) ($input['Data_inregistrare'] ?? ''));
        if ($data_inregistrare === '') {
            $data_inregistrare = date('Y-m-d');
        } else {
            $data_inregistrare = $this->validDate($errors, $input, 'Data_inregistrare', 'Data inregistrarii');
        }

        return [
            'errors' => $errors,
            'data' => [
                'Nume' => $nume,
                'Tip' => $tip,
                'Email' => $email,
                'Telefon' => $telefon,
                'Oras' => $oras,
                'Data_inregistrare' => $data_inregistrare,
            ],
        ];
    }
}
