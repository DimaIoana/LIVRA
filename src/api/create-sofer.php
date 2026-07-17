<?php

require_once __DIR__ . '/_crud.php';

crud_create(new SoferRepository($pdo), 'soferi');
