<?php

function loadEnv($path): void
{
    if (!file_exists($path)) {
        throw new Exception(".env file not found");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);    //Gets occupied lines

    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {    //Trims if is a comment
            continue;
        }


        [$key, $value] = array_map('trim', explode('=', $line, 2));     //Divides the line with key and value
        $_ENV[$key] = $value;       //Saves the key of the line in the Variable for the .env
    }
}
//Loads the information from the .env file
loadEnv( __DIR__ . '/.env');

//Connects to the Database
function getDbConnection(): PDO
{
    $host = $_ENV['DB_HOST'];
    $db   = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];

    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}