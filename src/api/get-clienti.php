<?php

require_once __DIR__ . '/_crud.php';

crud_list(new ClientRepository($pdo), 'clienti');
