<?php
// filiere/etudiants.php : étudiants d'une filière et leurs résultats
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

assurer_schema_note($pdo);

$modeRecherche = ($_GET['mode'] ?? 'nom') === 'id' ? 'id' : 'nom';
$termeRecherche = trim((string)($_GET['filiere'] ?? ''));
$nomFiliere = '';
$filiere = null;
$etudiants = [];
$erreur = null;

if ($termeRecherche !== '') {
    try {
        if ($modeRecherche === 'id') {
            $idFiliere = filter_var($termeRecherche, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $stmtFiliere = $pdo->prepare('SELECT nom FROM filiere WHERE id = :id');
            $stmtFiliere->execute([':id' => $idFiliere !== false ? $idFiliere : 0]);
        } else {
            $stmtFiliere = $pdo->prepare('SELECT nom FROM filiere WHERE nom = :nom');
            $stmtFiliere->execute([':nom' => $termeRecherche]);
        }
        $filiere = $stmtFiliere->fetchColumn() ?: null;
        $nomFiliere = $filiere ?: $termeRecherche;

        if ($filiere) {
            $stmtEtudiants = $pdo->prepare('
                SELECT p.id, p.numero, p.civilite, p.nom_prenom, p.filiere, p.photo,
                       n.note, m.designation AS module, m.coefficient
                FROM personne p
                LEFT JOIN note n ON n.etudiant_id = p.id
                LEFT JOIN module m ON m.id = n.module_id
                WHERE p.filiere = :filiere
                ORDER BY p.nom_prenom ASC, m.designation ASC
            ');
            $stmtEtudiants->execute([':filiere' => $filiere]);

            foreach ($stmtEtudiants->fetchAll() as $row) {
                $id = (int)$row['id'];
                if (!isset($etudiants[$id])) {
                    $etudiants[$id] = [
                        'id' => $id,
                        'numero' => $row['numero'],
                        'civilite' => $row['civilite'],
                        'nom_prenom' => $row['nom_prenom'],
                        'filiere' => $row['filiere'],
                        'photo' => $row['photo'],
                        'total_coefficients' => 0,
                        'total_points' => 0.0,
                        'note_min' => null,
                        'modules_min' => [],
                        'note_max' => null,
                        'modules_max' => [],
                    ];
                }

                if ($row['note'] === null) {
                    continue;
                }

                $note = (float)$row['note'];
                $coefficient = (int)$row['coefficient'];
                $etudiants[$id]['total_coefficients'] += $coefficient;
                $etudiants[$id]['total_points'] += $note * $coefficient;

                if ($etudiants[$id]['note_min'] === null || $note < $etudiants[$id]['note_min']) {
                    $etudiants[$id]['note_min'] = $note;
                    $etudiants[$id]['modules_min'] = [$row['module']];
                } elseif ($note === $etudiants[$id]['note_min']) {
                    $etudiants[$id]['modules_min'][] = $row['module'];
                }

                if ($etudiants[$id]['note_max'] === null || $note > $etudiants[$id]['note_max']) {
                    $etudiants[$id]['note_max'] = $note;
                    $etudiants[$id]['modules_max'] = [$row['module']];
                } elseif ($note === $etudiants[$id]['note_max']) {
                    $etudiants[$id]['modules_max'][] = $row['module'];
                }
            }
        }
    } catch (PDOException $e) {
        $erreur = "Erreur lors du chargement des étudiants et de leurs notes.";
    }
}

$base = '../';
$titre = 'Étudiants par filière';
require __DIR__ . '/../includes/header.php';
?>

<form method="get" action="etudiants.php" class="form-recherche">
    <label for="mode-recherche">Rechercher la filière par :</label>
    <select name="mode" id="mode-recherche">
        <option value="nom" <?= $modeRecherche === 'nom' ? 'selected' : '' ?>>Nom</option>
        <option value="id" <?= $modeRecherche === 'id' ? 'selected' : '' ?>>ID</option>
    </select>
    <input type="text" name="filiere" id="filiere" value="<?= h($termeRecherche) ?>" placeholder="<?= $modeRecherche === 'id' ? 'ex: 3' : 'ex: 3ISIL' ?>" required>
    <button type="submit">Rechercher les étudiants</button>
    <?php if ($termeRecherche !== ''): ?>
        <a href="etudiants.php">Réinitialiser</a>
    <?php endif; ?>
</form>

<?php if ($erreur): ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php elseif ($termeRecherche !== '' && !$filiere): ?>
    <p class="erreur">Aucune filière trouvée pour « <?= h($termeRecherche) ?> ».</p>
<?php elseif ($filiere): ?>
    <h3>Étudiants de la filière <?= h($filiere) ?></h3>
    <?php if (!$etudiants): ?>
        <p>Aucun étudiant trouvé dans cette filière.</p>
    <?php else: ?>
        <table class="liste">
            <thead>
                <tr>
                    <th>N° étudiant</th>
                    <th>Étudiant</th>
                    <th>Filière</th>
                    <th>Note minimale et module(s)</th>
                    <th>Note maximale et module(s)</th>
                    <th>Moyenne générale</th>
                    <th>Résultat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($etudiants as $etudiant):
                    $moyenne = $etudiant['total_coefficients'] > 0
                        ? $etudiant['total_points'] / $etudiant['total_coefficients']
                        : null;
                ?>
                    <tr>
                        <td><?= h($etudiant['numero']) ?></td>
                        <td>
                            <?php if ($etudiant['photo'] && file_exists(__DIR__ . '/../uploads/' . $etudiant['photo'])): ?>
                                <img src="<?= $base ?>uploads/<?= h($etudiant['photo']) ?>" alt="" loading="lazy">
                            <?php endif; ?>
                            <?= h($etudiant['nom_prenom']) ?>
                        </td>
                        <td><?= h($etudiant['filiere']) ?></td>
                        <?php if ($etudiant['note_min'] !== null): ?>
                            <td>
                                <?= number_format($etudiant['note_min'], 2, ',', ' ') ?> / 20
                                — <?= h(implode(', ', $etudiant['modules_min'])) ?>
                            </td>
                            <td>
                                <?= number_format($etudiant['note_max'], 2, ',', ' ') ?> / 20
                                — <?= h(implode(', ', $etudiant['modules_max'])) ?>
                            </td>
                        <?php else: ?>
                            <td colspan="2">Aucune note</td>
                        <?php endif; ?>
                        <td><?= $moyenne !== null ? number_format($moyenne, 2, ',', ' ') . ' / 20' : '—' ?></td>
                        <td>
                            <?php if ($moyenne === null): ?>
                                Non évalué
                            <?php else: ?>
                                <strong style="color: <?= $moyenne >= 10 ? '#15803d' : '#b91c1c' ?>;">
                                    <?= $moyenne >= 10 ? 'Admis(e)' : 'Ajourné(e)' ?>
                                </strong>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>

<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
