<?php

require_once __DIR__ . '/_crud.php';

crud_delete(new ExpediereRepository($pdo), 'expedieri', 'ExpediereID');
