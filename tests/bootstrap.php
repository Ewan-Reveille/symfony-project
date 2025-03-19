<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

<<<<<<< HEAD
if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
=======
if (method_exists(Dotenv::class, 'bootEnv')) {
>>>>>>> sauvegarde
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}
