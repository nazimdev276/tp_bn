<?php
// bulletin/liste.php : liste complète des notes enregistrées
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

assurer_schema_note($pdo);

$termeRecherche = trim((string)($_GET['q'] ?? $_GET['id'] ?? ''));
$erreurRecherche = false;
$notes = [];

$sql = '
    SELECT n.id AS note_id, n.note, n.date_saisie,
           p.id AS etudiant_id, p.numero, p.civilite, p.nom_prenom, p.filiere, p.photo,
           nat.libelle AS nationalite,
           m.id AS module_id, m.code_module, m.designation, m.coefficient
    FROM note n
    JOIN personne p ON p.id = n.etudiant_id
    JOIN module m ON m.id = n.module_id
    LEFT JOIN nationalite nat ON nat.code = n.nationalite_code
';

$params = [];
if ($termeRecherche !== '') {
    $idValide = filter_var($termeRecherche, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idValide !== false) {
        $sql .= ' WHERE n.id = :id OR p.id = :pid OR p.numero = :pnum OR p.nom_prenom LIKE :nom OR m.code_module LIKE :mod OR m.designation LIKE :des OR nat.libelle LIKE :nat';
        $params[':id']   = $idValide;
        $params[':pid']  = $idValide;
        $params[':pnum'] = $termeRecherche;
        $params[':nom']  = '%' . $termeRecherche . '%';
        $params[':mod']  = '%' . $termeRecherche . '%';
        $params[':des']  = '%' . $termeRecherche . '%';
        $params[':nat']  = '%' . $termeRecherche . '%';
    } else {
        $sql .= ' WHERE p.numero = :pnum OR p.nom_prenom LIKE :nom OR m.code_module LIKE :mod OR m.designation LIKE :des OR nat.libelle LIKE :nat';
        $params[':pnum'] = $termeRecherche;
        $params[':nom']  = '%' . $termeRecherche . '%';
        $params[':mod']  = '%' . $termeRecherche . '%';
        $params[':des']  = '%' . $termeRecherche . '%';
        $params[':nat']  = '%' . $termeRecherche . '%';
    }
}

$sql .= ' ORDER BY p.nom_prenom ASC, m.designation ASC';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notes = $stmt->fetchAll();
} catch (PDOException $e) {
    $erreurRecherche = true;
}

$base  = '../';
$titre = 'Liste des notes';
require __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
    <form method="get" action="liste.php" class="form-recherche" style="margin: 0;">
        <label for="search-input">Rechercher une note :</label>
        <input type="text" name="q" id="search-input" placeholder="Étudiant, N°, module, nationalité..." value="<?= h($termeRecherche) ?>">
        <button type="submit">Rechercher</button>
        <?php if ($termeRecherche !== ''): ?>
            <a href="liste.php" style="margin-left: 10px;">Réinitialiser</a>
        <?php endif; ?>
    </form>
    <div>
        <a href="bulletin.php" class="btn-link" style="display: inline-block; padding: 7px 14px; background: var(--primary); color: white; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">+ Saisir une note</a>
        <button type="button" onclick="window.print()" style="margin-left: 8px;">🖨️ Imprimer</button>
    </div>
</div>

<?php if ($erreurRecherche): ?>
    <p class="erreur">Une erreur est survenue lors du chargement des notes.</p>
<?php elseif (!$notes && $termeRecherche !== ''): ?>
    <p>Aucune note trouvée pour « <?= h($termeRecherche) ?> ».</p>
<?php elseif (!$notes): ?>
    <p>Aucune note enregistrée pour le moment. <a href="bulletin.php">Enregistrer la première note</a>.</p>
<?php else: ?>
    <?php if ($termeRecherche !== ''): ?>
        <p class="ok"><?= count($notes) ?> note(s) trouvée(s) pour « <?= h($termeRecherche) ?> » :</p>
    <?php endif; ?>

    <table class="liste">
        <thead>
            <tr>
                <th>Num_Etudiant</th>
                <th>Filière</th>
                <th>Nom_module</th>
                <th>code_module</th>
                <th>coefficient</th>
                <th>Nationalité</th>
                <th>Note</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notes as $n):
                $noteVal = (float)$n['note'];
            ?>
                <tr>
                    <td><?= h($n['numero']) ?></td>
                    <td><?= h($n['filiere'] ?: '—') ?></td>
                    <td><?= h($n['designation']) ?></td>
                    <td><?= h($n['code_module']) ?></td>
                    <td><?= h($n['coefficient']) ?></td>
                    <td><?= h($n['nationalite'] ?? '—') ?></td>
                    <td>
                        <strong style="color: <?= $noteVal >= 10 ? '#15803d' : '#b91c1c' ?>; font-size: 14px;">
                            <?= number_format($noteVal, 2, ',', ' ') ?>
                        </strong>
                    </td>
                    <td>
                        <a href="bulletin.php?id=<?= h($n['etudiant_id']) ?>&module_id=<?= h($n['module_id']) ?>&note_id=<?= h($n['note_id']) ?>">Modifier</a>
                        &nbsp;|&nbsp;
                        <a href="afficher_bulletin.php?numero=<?= h(urlencode((string)$n['numero'])) ?>">Bulletin</a>
                        &nbsp;|&nbsp;
                        <form method="post" action="supprimer.php" style="display: inline;" onsubmit="return confirm('Supprimer définitivement la note de <?= h($n['nom_prenom']) ?> en <?= h($n['designation']) ?> ?');">
                            <input type="hidden" name="id" value="<?= h($n['note_id']) ?>">
                            <input type="hidden" name="etudiant_id" value="<?= h($n['etudiant_id']) ?>">
                            <input type="hidden" name="module_id" value="<?= h($n['module_id']) ?>">
                            <button type="submit" style="background: none; border: none; color: #dc2626; padding: 0; cursor: pointer; text-decoration: underline; font-size: inherit;">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
