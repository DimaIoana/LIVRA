<?php

require_once __DIR__ . '/_crud.php';

crud_delete(
    new SoferRepository($pdo),
    'soferi',
    'SoferID',
    'Soferul nu poate fi sters: are expedieri inregistrate. Sterge intai expedierile lui.'
);
