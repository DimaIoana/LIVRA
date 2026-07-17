<?php

require_once __DIR__ . '/_crud.php';

crud_update(new ExpediereRepository($pdo), 'expedieri', 'ExpediereID');
