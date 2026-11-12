<?php
// filiere/statistiques.php : redirige vers choix_statistiques.php
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$redirectUrl = 'choix_statistiques.php' . ($queryString !== '' ? '?' . $queryString : '');
header('Location: ' . $redirectUrl, true, 302);
exit;
