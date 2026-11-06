<?php

function supprimer_par_id(PDO $pdo, string $table, $id): array
{
    if (!preg_match('/^[a-z_]+$/', $table)) {
        throw new InvalidArgumentException('Nom de table invalide.');
    }

    if ((!is_string($id) && !is_int($id)) || filter_var((string)$id, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]) === false) {
        return ['status' => 'invalid', 'id' => null];
    }

    $id = (int)$id;
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = :id");
    $stmt->execute([':id' => $id]);

    return [
        'status' => $stmt->rowCount() === 1 ? 'deleted' : 'missing',
        'id' => $id,
    ];
}
