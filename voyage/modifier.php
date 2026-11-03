<?php
// voyage/modifier.php : redirige vers voyage.php?id=...
$id = $_GET['id'] ?? '';
header('Location: voyage.php?id=' . urlencode((string)$id));
exit;
