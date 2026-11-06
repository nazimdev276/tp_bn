<?php
// bulletin/liste.php : liste complète des notes enregistrées
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

assurer_schema_note($pdo);

$termeRecherche = trim((string)($_GET['q'] ?? $_GET['id'] ?? ''));
$modeRecherche = in_array($_GET['mode'] ?? 'all', ['all', 'id', 'numero'], true) ? ($_GET['mode'] ?? 'all') : 'all';
$erreurRecherche = false;
$notes = [];
$resultatsEtudiants = [];

$sql = '
    SELECT n.id AS note_id, n.note, n.date_saisie,
           p.id AS etudiant_id, p.numero, p.civilite, p.nom_prenom, p.filiere, p.photo,
           COALESCE(n.nationalite_code, p.nationalite_code) AS nationalite_code,
           COALESCE(nat.libelle, nat_personne.libelle) AS nationalite,
           m.id AS module_id, m.code_module, m.designation, m.coefficient
    FROM note n
    JOIN personne p ON p.id = n.etudiant_id
    JOIN module m ON m.id = n.module_id
    LEFT JOIN nationalite nat ON nat.code = n.nationalite_code
    LEFT JOIN nationalite nat_personne ON nat_personne.code = p.nationalite_code
';

$params = [];
$noteMin = null;
$noteMax = null;
$modulesMin = [];
$modulesMax = [];
if ($termeRecherche !== '') {
    $idValide = filter_var($termeRecherche, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($modeRecherche === 'id') {
        $sql .= ' WHERE n.id = :id OR p.id = :pid';
        $params[':id'] = $idValide !== false ? $idValide : 0;
        $params[':pid'] = $idValide !== false ? $idValide : 0;
    } elseif ($modeRecherche === 'numero') {
        $sql .= ' WHERE p.numero = :pnum';
        $params[':pnum'] = $termeRecherche;
    } elseif ($idValide !== false) {
        $sql .= ' WHERE n.id = :id OR p.id = :pid OR p.numero = :pnum OR p.nom_prenom LIKE :nom OR m.code_module LIKE :mod OR m.designation LIKE :des OR COALESCE(nat.libelle, nat_personne.libelle) LIKE :nat';
        $params[':id']   = $idValide;
        $params[':pid']  = $idValide;
        $params[':pnum'] = $termeRecherche;
        $params[':nom']  = '%' . $termeRecherche . '%';
        $params[':mod']  = '%' . $termeRecherche . '%';
        $params[':des']  = '%' . $termeRecherche . '%';
        $params[':nat']  = '%' . $termeRecherche . '%';
    } else {
        $sql .= ' WHERE p.numero = :pnum OR p.nom_prenom LIKE :nom OR m.code_module LIKE :mod OR m.designation LIKE :des OR COALESCE(nat.libelle, nat_personne.libelle) LIKE :nat';
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
    foreach ($notes as $note) {
        $valeur = (float)$note['note'];
        if ($noteMin === null || $valeur < $noteMin) {
            $noteMin = $valeur;
            $modulesMin = [$note['designation']];
        } elseif ($valeur === $noteMin) {
            $modulesMin[] = $note['designation'];
        }
        if ($noteMax === null || $valeur > $noteMax) {
            $noteMax = $valeur;
            $modulesMax = [$note['designation']];
        } elseif ($valeur === $noteMax) {
            $modulesMax[] = $note['designation'];
        }
    }
    if ($notes) {
        $etudiantIds = array_values(array_unique(array_column($notes, 'etudiant_id')));
        $placeholders = implode(',', array_fill(0, count($etudiantIds), '?'));
        $stmtMoyennes = $pdo->prepare("
            SELECT n.etudiant_id,
                   SUM(n.note * m.coefficient) / NULLIF(SUM(m.coefficient), 0) AS moyenne
            FROM note n
            JOIN module m ON m.id = n.module_id
            WHERE n.etudiant_id IN ($placeholders)
            GROUP BY n.etudiant_id
        ");
        $stmtMoyennes->execute($etudiantIds);
        foreach ($stmtMoyennes->fetchAll() as $resultat) {
            $resultatsEtudiants[(int)$resultat['etudiant_id']] = $resultat['moyenne'];
        }
    }
} catch (PDOException $e) {
    $erreurRecherche = true;
}

$base  = '../';
$titre = 'Liste des notes';
require __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
    <form method="get" action="liste.php" class="form-recherche" style="margin: 0;">
        <label for="mode-recherche">Rechercher une note :</label>
        <select name="mode" id="mode-recherche">
            <option value="all" <?= $modeRecherche === 'all' ? 'selected' : '' ?>>Tous les champs</option>
            <option value="id" <?= $modeRecherche === 'id' ? 'selected' : '' ?>>ID note / étudiant</option>
            <option value="numero" <?= $modeRecherche === 'numero' ? 'selected' : '' ?>>Numéro étudiant</option>
        </select>
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
        <?php foreach ($notes as $note): ?>
            <?php
            $etudiantIdResultat = (int)$note['etudiant_id'];
            if (!isset($resultatsEtudiants[$etudiantIdResultat])) {
                continue;
            }
            ?>
            <p class="<?= (float)$resultatsEtudiants[$etudiantIdResultat] >= 10 ? 'ok' : 'erreur' ?>">
                <strong><?= h($note['nom_prenom']) ?> — <?= (float)$resultatsEtudiants[$etudiantIdResultat] >= 10 ? 'Admis(e)' : 'Ajourné(e)' ?></strong>
                : moyenne générale <?= number_format((float)$resultatsEtudiants[$etudiantIdResultat], 2, ',', ' ') ?> / 20
                (seuil : 10 / 20)
            </p>
            <?php unset($resultatsEtudiants[$etudiantIdResultat]); ?>
        <?php endforeach; ?>
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
                    <td>
                        <?php if (preg_match('/^[A-Za-z]{2}$/', (string)($n['nationalite_code'] ?? ''))): ?>
                            <img class="nationalite-flag" src="https://flagcdn.com/w40/<?= h(strtolower($n['nationalite_code'])) ?>.png" alt="" loading="lazy">
                        <?php endif; ?>
                        <?= h($n['nationalite'] ?? '—') ?>
                    </td>
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
    <div class="bulletin-student-info" style="display: flex; flex-wrap: wrap; gap: 24px; margin-top: 16px;">
        <p><strong>Note minimale :</strong> <?= number_format($noteMin, 2, ',', ' ') ?> / 20
            — <strong>Module(s) :</strong> <?= h(implode(', ', $modulesMin)) ?></p>
        <p><strong>Note maximale :</strong> <?= number_format($noteMax, 2, ',', ' ') ?> / 20
            — <strong>Module(s) :</strong> <?= h(implode(', ', $modulesMax)) ?></p>
    </div>
<?php endif; ?>

<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
