<?php
$kasutaja = 'd123178_arkadiko';
$serverinimi = 'd123178.mysql.zonevs.eu';
$parool = 'markiz2020!';
$andmebaas = 'd123178_akbaas';

$yhendus = new mysqli($serverinimi, $kasutaja, $parool, $andmebaas);
$yhendus->set_charset('UTF8');

if ($yhendus->connect_error) {
    die("Ühenduse viga: " . $yhendus->connect_error);
}
?>
