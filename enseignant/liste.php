<?php
// enseignant/liste.php : affiche et recherche les enseignants
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$q = trim((string)($_GET['q'] ?? ''));
$modeRecherche = in_array($_GET['mode'] ?? 'all', ['all', 'id', 'numero'], true) ? ($_GET['mode'] ?? 'all') : 'all';
$enseignants = [];
$erreur = null;

try {
    if ($q !== '') {
        if ($modeRecherche === 'id') {
            $idVal = filter_var($q, FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare('SELECT * FROM enseignant WHERE id = :id ORDER BY id');
            $stmt->execute([':id' => $idVal !== false ? $idVal : 0]);
        } elseif ($modeRecherche === 'numero') {
            $stmt = $pdo->prepare('SELECT * FROM enseignant WHERE numero LIKE :q ORDER BY id');
            $stmt->execute([':q' => '%' . $q . '%']);
        } else {
            $stmt = $pdo->prepare(
                'SELECT * FROM enseignant
                 WHERE id = :id OR numero LIKE :q OR nom_prenom LIKE :q OR specialite LIKE :q OR grade LIKE :q OR filiere LIKE :q OR modules LIKE :q
                 ORDER BY id'
            );
            $idVal = filter_var($q, FILTER_VALIDATE_INT);
            $stmt->execute([
                ':id' => $idVal !== false ? $idVal : 0,
                ':q'  => '%' . $q . '%',
            ]);
        }
    } else {
        $stmt = $pdo->query('SELECT * FROM enseignant ORDER BY id');
    }
    $enseignants = $stmt->fetchAll();
} catch (PDOException $e) {
    $erreur = "Erreur lors du chargement des enseignants : " . $e->getMessage();
}

$base = '../';
$titre = 'Liste des enseignants';
require __DIR__ . '/../includes/header.php';
?>

<form method="get" action="liste.php" class="form-recherche">
    <label for="mode-recherche">Rechercher un enseignant :</label>
    <select name="mode" id="mode-recherche">
        <option value="all" <?= $modeRecherche === 'all' ? 'selected' : '' ?>>Tous les champs</option>
        <option value="id" <?= $modeRecherche === 'id' ? 'selected' : '' ?>>ID</option>
        <option value="numero" <?= $modeRecherche === 'numero' ? 'selected' : '' ?>>Numéro</option>
    </select>
    <input type="text" name="q" id="search-enseignant" placeholder="ID, nom, numéro, filière, module, spécialité..." value="<?= h($q) ?>">
    <button type="submit">Rechercher</button>
    <?php if ($q !== ''): ?>
        <a href="liste.php" style="margin-left: 10px;">Réinitialiser (voir tout)</a>
    <?php endif; ?>
</form>

<?php if ($erreur): ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php elseif (!$enseignants && $q !== ''): ?>
    <p>Aucun enseignant trouvé pour la recherche « <?= h($q) ?> ».</p>
<?php elseif (!$enseignants): ?>
    <p>Aucun enseignant enregistré.</p>
<?php else: ?>
    <?php if ($q !== ''): ?>
        <p class="ok"><?= count($enseignants) ?> enseignant(s) trouvé(s) :</p>
    <?php endif; ?>
    <table class="liste">
        <tr>
            <th>ID</th>
            <th>N°</th>
            <th>Civilité</th>
            <th>Nom / Prénom</th>
            <th>Filière</th>
            <th>Modules</th>
            <th>Adresse</th>
            <th>Date naiss.</th>
            <th>Lieu naiss.</th>
            <th>Pays</th>
            <th>Grade</th>
            <th>Spécialité</th>
            <th style="min-width: 220px;">Actions</th>
        </tr>
        <?php foreach ($enseignants as $e): ?>
            <?php
                $modulesList = !empty($e['modules'])
                    ? lire_liste_texte($e['modules'])
                    : [];
            ?>
            <tr>
                <td><?= h($e['id']) ?></td>
                <td><?= h($e['numero']) ?></td>
                <td><?= h($e['civilite']) ?></td>
                <td><strong><?= h($e['nom_prenom']) ?></strong></td>
                <td>
                    <?php if (!empty($e['filiere'])): ?>
                        <span class="brand-badge" style="background:#f0fdf4; color:#166534; border-color:#86efac; font-weight:600;"><?= h($e['filiere']) ?></span>
                    <?php else: ?>
                        <span style="color:var(--text-light);">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($modulesList)): ?>
                        <?php foreach ($modulesList as $mod): ?>
                            <span class="brand-badge" style="background:#eef2ff; color:#4f46e5; border-color:#c7d2fe; margin-right:3px; font-size:11px;"><?= h($mod) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color:var(--text-light);">—</span>
                    <?php endif; ?>
                </td>
                <td><?= h($e['adresse']) ?></td>
                <td><?= h($e['date_naissance']) ?></td>
                <td><?= h($e['lieu_naissance']) ?></td>
                <td><?= h($e['pays']) ?></td>
                <td><span class="brand-badge" style="background:#f8fafc; color:#334155; border-color:#cbd5e1;"><?= h($e['grade']) ?></span></td>
                <td><?= h($e['specialite']) ?></td>
                <td style="white-space: nowrap;">
                    <a href="enseignant.php?id=<?= h($e['id']) ?>">Modifier</a>
                    <a href="etudiants.php?id=<?= h($e['id']) ?>" class="btn-action" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;" title="Voir les étudiants de ses classes">Liste Des Etudiants</a>
                    <form method="post" action="supprimer.php" style="display:inline;" onsubmit="return confirm('Supprimer l’enseignant <?= h($e['nom_prenom']) ?> (ID <?= h($e['id']) ?>) ?');">
                        <input type="hidden" name="id" value="<?= h($e['id']) ?>">
                        <button type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<p style="margin-top: 20px;">
    <a href="enseignant.php">Ajouter un enseignant</a> |
    <a href="etudiants.php">Liste Des Etudiants par Enseignant</a>
</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
