<?php
// filiere/liste.php : affiche et recherche les filières
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$q = trim((string)($_GET['q'] ?? ''));
$modeRecherche = in_array($_GET['mode'] ?? 'all', ['all', 'id', 'nom'], true) ? ($_GET['mode'] ?? 'all') : 'all';
$filieres = [];
$erreur = null;

try {
    if ($q !== '') {
        if ($modeRecherche === 'id') {
            $idVal = filter_var($q, FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare('SELECT * FROM filiere WHERE id = :id ORDER BY id');
            $stmt->execute([':id' => $idVal !== false ? $idVal : 0]);
        } elseif ($modeRecherche === 'nom') {
            $stmt = $pdo->prepare('SELECT * FROM filiere WHERE nom LIKE :q ORDER BY id');
            $stmt->execute([':q' => '%' . $q . '%']);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM filiere WHERE id = :id OR nom LIKE :q ORDER BY id');
            $idVal = filter_var($q, FILTER_VALIDATE_INT);
            $stmt->execute([
                ':id' => $idVal !== false ? $idVal : 0,
                ':q'  => '%' . $q . '%',
            ]);
        }
    } else {
        $stmt = $pdo->query('SELECT * FROM filiere ORDER BY id');
    }
    $filieres = $stmt->fetchAll();
} catch (PDOException $e) {
    $erreur = "Erreur lors du chargement des filières.";
}

$base = '../';
$titre = 'Liste des filières';
require __DIR__ . '/../includes/header.php';
?>

<form method="get" action="liste.php" class="form-recherche">
    <label for="mode-recherche">Rechercher une filière :</label>
    <select name="mode" id="mode-recherche">
        <option value="all" <?= $modeRecherche === 'all' ? 'selected' : '' ?>>Tous les champs</option>
        <option value="id" <?= $modeRecherche === 'id' ? 'selected' : '' ?>>ID</option>
        <option value="nom" <?= $modeRecherche === 'nom' ? 'selected' : '' ?>>Nom de filière</option>
    </select>
    <input type="text" name="q" id="search-filiere" placeholder="ID ou nom..." value="<?= h($q) ?>">
    <button type="submit">Rechercher</button>
    <?php if ($q !== ''): ?>
        <a href="liste.php" style="margin-left: 10px;">Réinitialiser (voir tout)</a>
    <?php endif; ?>
</form>

<p>
    <a href="statistiques.php" class="btn-link" style="display:inline-block; padding:9px 18px; background:var(--primary); color:#062b29; border-radius:var(--radius-md); font-weight:600; text-decoration:none;">
        Voir les statistiques de réussite
    </a>
</p>

<?php if ($erreur): ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php elseif (!$filieres && $q !== ''): ?>
    <p>Aucune filière trouvée pour la recherche « <?= h($q) ?> ».</p>
<?php elseif (!$filieres): ?>
    <p>Aucune filière enregistrée.</p>
<?php else: ?>
    <?php if ($q !== ''): ?>
        <p class="ok"><?= count($filieres) ?> filière(s) trouvée(s) :</p>
    <?php endif; ?>
    <table class="liste">
        <tr>
            <th>ID</th>
            <th>Nom de la filière</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($filieres as $f): ?>
            <tr>
                <td><?= h($f['id']) ?></td>
                <td><strong><?= h($f['nom']) ?></strong></td>
                <td>
                    <a href="etudiants.php?filiere=<?= h(urlencode($f['nom'])) ?>">Étudiants et résultats</a>
                    <a href="filiere.php?id=<?= h($f['id']) ?>">Modifier</a>
                    <form method="post" action="supprimer.php" style="display:inline;" onsubmit="return confirm('Supprimer la filière <?= h($f['nom']) ?> (ID <?= h($f['id']) ?>) ?');">
                        <input type="hidden" name="id" value="<?= h($f['id']) ?>">
                        <button type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<p><a href="filiere.php">Ajouter une filière</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
