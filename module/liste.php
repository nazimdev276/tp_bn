<?php
// module/liste.php : affiche et recherche les modules
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$q = trim((string)($_GET['q'] ?? ''));
$modeRecherche = in_array($_GET['mode'] ?? 'all', ['all', 'id', 'code'], true) ? ($_GET['mode'] ?? 'all') : 'all';
$modules = [];
$erreur = null;

try {
    if ($q !== '') {
        if ($modeRecherche === 'id') {
            $idVal = filter_var($q, FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare('SELECT * FROM module WHERE id = :id ORDER BY id');
            $stmt->execute([':id' => $idVal !== false ? $idVal : 0]);
        } elseif ($modeRecherche === 'code') {
            $stmt = $pdo->prepare('SELECT * FROM module WHERE code_module LIKE :q ORDER BY id');
            $stmt->execute([':q' => '%' . $q . '%']);
        } else {
            $stmt = $pdo->prepare(
                'SELECT * FROM module
                 WHERE id = :id OR code_module LIKE :q OR designation LIKE :q OR filiere LIKE :q
                 ORDER BY id'
            );
            $idVal = filter_var($q, FILTER_VALIDATE_INT);
            $stmt->execute([
                ':id' => $idVal !== false ? $idVal : 0,
                ':q'  => '%' . $q . '%',
            ]);
        }
    } else {
        $stmt = $pdo->query('SELECT * FROM module ORDER BY id');
    }
    $modules = $stmt->fetchAll();
} catch (PDOException $e) {
    $erreur = "Erreur lors du chargement des modules.";
}

$base = '../';
$titre = 'Liste des modules';
require __DIR__ . '/../includes/header.php';
?>

<form method="get" action="liste.php" class="form-recherche">
    <label for="mode-recherche">Rechercher un module :</label>
    <select name="mode" id="mode-recherche">
        <option value="all" <?= $modeRecherche === 'all' ? 'selected' : '' ?>>Tous les champs</option>
        <option value="id" <?= $modeRecherche === 'id' ? 'selected' : '' ?>>ID</option>
        <option value="code" <?= $modeRecherche === 'code' ? 'selected' : '' ?>>Code module</option>
    </select>
    <input type="text" name="q" id="search-module" placeholder="ID, code, désignation, filière..." value="<?= h($q) ?>">
    <button type="submit">Rechercher</button>
    <?php if ($q !== ''): ?>
        <a href="liste.php" style="margin-left: 10px;">Réinitialiser (voir tout)</a>
    <?php endif; ?>
</form>

<?php if ($erreur): ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php elseif (!$modules && $q !== ''): ?>
    <p>Aucun module trouvé pour la recherche « <?= h($q) ?> ».</p>
<?php elseif (!$modules): ?>
    <p>Aucun module enregistré.</p>
<?php else: ?>
    <?php if ($q !== ''): ?>
        <p class="ok"><?= count($modules) ?> module(s) trouvé(s) :</p>
    <?php endif; ?>
    <table class="liste">
        <tr>
            <th>ID</th>
            <th>Code module</th>
            <th>Désignation</th>
            <th>Coefficient</th>
            <th>Volume horaire</th>
            <th>Filière</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($modules as $m): ?>
            <tr>
                <td><?= h($m['id']) ?></td>
                <td><strong style="color:var(--primary);"><?= h($m['code_module']) ?></strong></td>
                <td><?= h($m['designation']) ?></td>
                <td><?= h($m['coefficient']) ?></td>
                <td><?= h($m['volume_horaire']) ?> h</td>
                <td><span class="brand-badge" style="background:#eef2ff; color:#4f46e5; border-color:#c7d2fe;"><?= h($m['filiere']) ?></span></td>
                <td>
                    <a href="module.php?id=<?= h($m['id']) ?>">Modifier</a>
                    <form method="post" action="supprimer.php" style="display:inline;" onsubmit="return confirm('Supprimer le module <?= h($m['designation']) ?> (ID <?= h($m['id']) ?>) ?');">
                        <input type="hidden" name="id" value="<?= h($m['id']) ?>">
                        <button type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<p><a href="module.php">Ajouter un module</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
