<?php

require_once __DIR__ . '/_crud.php';

crud_delete(
    new ClientRepository($pdo),
    'clienti',
    'ClientID',
    'Clientul nu poate fi sters: are expedieri inregistrate. Sterge intai expedierile lui.'
);
