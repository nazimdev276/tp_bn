<?php

function enregistrer_avec_id(PDO $pdo, string $table, array $valeurs, ?int $id, bool $modifier): array
{
    if (!preg_match('/^[a-z_]+$/', $table) || !$valeurs) {
        throw new InvalidArgumentException('Table ou valeurs invalides.');
    }

    foreach (array_keys($valeurs) as $colonne) {
        if (!is_string($colonne) || !preg_match('/^[a-z_]+$/', $colonne)) {
            throw new InvalidArgumentException('Nom de colonne invalide.');
        }
    }

    if ($modifier && $id === null) {
        return ['status' => 'missing_id', 'id' => null];
    }

    $parametres = [];
    $colonnes = [];
    $placeholders = [];
    $assignations = [];

    foreach ($valeurs as $colonne => $valeur) {
        $placeholder = ':valeur_' . count($parametres);
        $parametres[$placeholder] = $valeur;
        $colonnes[] = '`' . $colonne . '`';
        $placeholders[] = $placeholder;
        $assignations[] = '`' . $colonne . '` = ' . $placeholder;
    }

    if ($modifier) {
        $parametres[':id'] = $id;
        $stmt = $pdo->prepare(
            "UPDATE `$table` SET " . implode(', ', $assignations) . ' WHERE id = :id'
        );
        $stmt->execute($parametres);

        if ($stmt->rowCount() > 0) {
            return ['status' => 'updated', 'id' => $id];
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return [
            'status' => (int)$stmt->fetchColumn() > 0 ? 'unchanged' : 'not_found',
            'id' => $id,
        ];
    }

    if ($id !== null) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $existe = (int)$stmt->fetchColumn() > 0;

        if ($existe) {
            $parametres[':id'] = $id;
            $stmt = $pdo->prepare(
                "UPDATE `$table` SET " . implode(', ', $assignations) . ' WHERE id = :id'
            );
            $stmt->execute($parametres);
            return ['status' => 'updated', 'id' => $id];
        }

        array_unshift($colonnes, '`id`');
        array_unshift($placeholders, ':id');
        $parametres[':id'] = $id;
        $stmt = $pdo->prepare(
            "INSERT INTO `$table` (" . implode(', ', $colonnes) . ')
             VALUES (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($parametres);
        return ['status' => 'inserted', 'id' => $id];
    }

    $stmt = $pdo->prepare(
        "INSERT INTO `$table` (" . implode(', ', $colonnes) . ')
         VALUES (' . implode(', ', $placeholders) . ')'
    );
    $stmt->execute($parametres);

    return ['status' => 'inserted', 'id' => (int)$pdo->lastInsertId()];
}
