<?php
// bulletin/liste.php : liste complète des notes enregistrées
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

// Création de la table note si elle n'existe pas encore (idempotent)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS note (
      id          INT AUTO_INCREMENT PRIMARY KEY,
      etudiant_id INT NOT NULL,
      module_id   INT NOT NULL,
      note        DECIMAL(4, 2) NOT NULL,
      date_saisie TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      CONSTRAINT fk_note_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES personne(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
      CONSTRAINT fk_note_module
        FOREIGN KEY (module_id) REFERENCES module(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
      CONSTRAINT uk_etudiant_module UNIQUE (etudiant_id, module_id)
    ) ENGINE=InnoDB");
} catch (Exception $e) {
}

$termeRecherche = trim((string)($_GET['q'] ?? $_GET['id'] ?? ''));
$erreurRecherche = false;
$notes = [];

$sql = '
    SELECT n.id AS note_id, n.note, n.date_saisie,
           p.id AS etudiant_id, p.numero, p.civilite, p.nom_prenom, p.filiere, p.photo,
           m.id AS module_id, m.code_module, m.designation, m.coefficient
    FROM note n
    JOIN personne p ON p.id = n.etudiant_id
    JOIN module m ON m.id = n.module_id
';

$params = [];
if ($termeRecherche !== '') {
    $idValide = filter_var($termeRecherche, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idValide !== false) {
        $sql .= ' WHERE n.id = :id OR p.id = :pid OR p.numero = :pnum OR p.nom_prenom LIKE :nom OR m.code_module LIKE :mod OR m.designation LIKE :des';
        $params[':id']   = $idValide;
        $params[':pid']  = $idValide;
        $params[':pnum'] = $termeRecherche;
        $params[':nom']  = '%' . $termeRecherche . '%';
        $params[':mod']  = '%' . $termeRecherche . '%';
        $params[':des']  = '%' . $termeRecherche . '%';
    } else {
        $sql .= ' WHERE p.numero = :pnum OR p.nom_prenom LIKE :nom OR m.code_module LIKE :mod OR m.designation LIKE :des';
        $params[':pnum'] = $termeRecherche;
        $params[':nom']  = '%' . $termeRecherche . '%';
        $params[':mod']  = '%' . $termeRecherche . '%';
        $params[':des']  = '%' . $termeRecherche . '%';
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
        <input type="text" name="q" id="search-input" placeholder="Étudiant, N°, Module..." value="<?= h($termeRecherche) ?>">
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
                <th>ID</th>
                <th>Photo</th>
                <th>N°</th>
                <th>Étudiant</th>
                <th>Filière</th>
                <th>Module</th>
                <th>Code</th>
                <th>Coeff</th>
                <th>Note / 20</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notes as $n):
                $noteVal = (float)$n['note'];
            ?>
                <tr>
                    <td><?= h($n['note_id']) ?></td>
                    <td style="text-align: center; width: 45px;">
                        <?php if (!empty($n['photo']) && file_exists(__DIR__ . '/../uploads/' . $n['photo'])): ?>
                            <img src="../uploads/<?= h($n['photo']) ?>" alt="Photo" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; display: inline-block;">
                        <?php else: ?>
                            <span style="color: var(--text-muted); font-size: 11px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($n['numero']) ?></td>
                    <td>
                        <strong><?= h($n['nom_prenom']) ?></strong>
                        <br><span style="font-size: 11px; color: var(--text-muted);"><?= h($n['civilite']) ?></span>
                    </td>
                    <td><?= h($n['filiere'] ?: '—') ?></td>
                    <td><strong><?= h($n['designation']) ?></strong></td>
                    <td><?= h($n['code_module']) ?></td>
                    <td><?= h($n['coefficient']) ?></td>
                    <td>
                        <strong style="color: <?= $noteVal >= 10 ? '#15803d' : '#b91c1c' ?>; font-size: 14px;">
                            <?= number_format($noteVal, 2, ',', ' ') ?>
                        </strong>
                    </td>
                    <td style="font-size: 12px; color: var(--text-muted);"><?= h(substr($n['date_saisie'], 0, 10)) ?></td>
                    <td>
                        <a href="bulletin.php?id=<?= h($n['etudiant_id']) ?>&module_id=<?= h($n['module_id']) ?>&note_id=<?= h($n['note_id']) ?>">Modifier</a>
                        &nbsp;|&nbsp;
                        <a href="bulletin.php?id=<?= h($n['etudiant_id']) ?>">Bulletin</a>
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
