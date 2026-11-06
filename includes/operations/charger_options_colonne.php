<?php

function charger_options_colonne(
    PDO $pdo,
    string $table,
    string $colonne,
    string $ordre,
    array $valeursParDefaut = []
): array {
    foreach ([$table, $colonne, $ordre] as $identifiant) {
        if (!preg_match('/^[a-z_]+$/', $identifiant)) {
            throw new InvalidArgumentException('Identifiant de table invalide.');
        }
    }

    $stmt = $pdo->query("SELECT `$colonne` FROM `$table` ORDER BY `$ordre`");
    $valeurs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $valeurs ?: $valeursParDefaut;
}
