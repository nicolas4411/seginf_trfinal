<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'segurosim';
$port = 3306;

$mysqli = new mysqli($host, $username, $password, $database, $port);

if ($mysqli->connect_error) {
    die("Erro na conexão: " . $mysqli->connect_error);
}
?>
