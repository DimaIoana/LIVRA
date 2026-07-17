<?php

require_once __DIR__ . '/_bootstrap.php';

/**
 * Handlere generice pentru endpoint-urile CRUD. Fiecare fisier de endpoint
 * doar alege repository-ul si apeleaza functia potrivita.
 *
 * Erorile de validare ajung la utilizator (le poate corecta); cele interne
 * ajung doar in log - vezi json_server_error().
 */

function crud_list(BaseRepository $repo, $entitate)
{
    require_method('GET');

    try {
        json_ok($repo->getAll(
            (string) ($_GET['search'] ?? ''),
            (string) ($_GET['sort'] ?? ''),
            (string) ($_GET['dir'] ?? 'desc')
        ));
    } catch (Throwable $e) {
        json_server_error('citire ' . $entitate, $e);
    }
}

function crud_create(BaseRepository $repo, $entitate)
{
    require_method('POST');

    try {
        $result = $repo->validate(json_input());

        if ($result['errors']) {
            json_error(implode(' ', $result['errors']));
        }

        json_ok($repo->getById($repo->create($result['data'])));
    } catch (Throwable $e) {
        json_server_error('adaugare ' . $entitate, $e);
    }
}

function crud_update(BaseRepository $repo, $entitate, $pk)
{
    require_method('POST');

    try {
        $input = json_input();
        $id = require_id($input[$pk] ?? null);

        $result = $repo->validate($input);

        if ($result['errors']) {
            json_error(implode(' ', $result['errors']));
        }

        if (!$repo->update($id, $result['data'])) {
            json_error('Inregistrarea nu exista.', 404);
        }

        json_ok($repo->getById($id));
    } catch (Throwable $e) {
        json_server_error('modificare ' . $entitate, $e);
    }
}

/**
 * @param string|null $mesaj_legatura Mesaj prietenos cand stergerea e blocata
 *                                    de o cheie straina (ex: are expedieri).
 */
function crud_delete(BaseRepository $repo, $entitate, $pk, $mesaj_legatura = null)
{
    require_method('POST');

    try {
        $id = require_id(json_input()[$pk] ?? null);

        if (!$repo->delete($id)) {
            json_error('Inregistrarea nu exista.', 404);
        }

        json_ok([$pk => $id]);
    } catch (PDOException $e) {
        // 23000 = incalcare de constrangere. Aici inseamna ca exista expedieri
        // care refera inregistrarea, deci e o eroare pe care userul o poate corecta.
        if ($e->getCode() === '23000' && $mesaj_legatura !== null) {
            json_error($mesaj_legatura, 409);
        }

        json_server_error('stergere ' . $entitate, $e);
    } catch (Throwable $e) {
        json_server_error('stergere ' . $entitate, $e);
    }
}
