<?php
require __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();
$dotenv->required(['ERPK_EMAIL', 'ERPK_PASSWORD']);
