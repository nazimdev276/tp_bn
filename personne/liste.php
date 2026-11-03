<?php
// personne/liste.php : bouton "Affichage Liste" - affiche et recherche les personnes enregistrées
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$termeRecherche = trim((string)($_GET['q'] ?? $_GET['id'] ?? ''));
$erreurRecherche = false;
$personnes = [];

$sql = 'SELECT p.*, n.libelle AS nationalite
        FROM personne p
        LEFT JOIN nationalite n ON n.code = p.nationalite_code';

$params = [];
if ($termeRecherche !== '') {
    $idValide = filter_var($termeRecherche, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);

    if ($idValide !== false) {
        $sql .= ' WHERE p.id = :id OR p.nom_prenom LIKE :nom';
        $params[':id'] = (int)$termeRecherche;
        $params[':nom'] = '%' . $termeRecherche . '%';
    } else {
        $sql .= ' WHERE p.nom_prenom LIKE :nom';
        $params[':nom'] = '%' . $termeRecherche . '%';
    }
}

$sql .= ' ORDER BY p.id';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $personnes = $stmt->fetchAll();
} catch (PDOException $e) {
    $erreurRecherche = true;
}

$base  = '../';
$titre = 'Liste des personnes';
require __DIR__ . '/../includes/header.php';
?>

<form method="get" action="liste.php" class="form-recherche">
    <label for="search-input">Rechercher une personne :</label>
    <input type="text" name="q" id="search-input" placeholder="ID ou Nom / Prénom..." value="<?= h($termeRecherche) ?>">
    <button type="submit">Rechercher</button>
    <?php if ($termeRecherche !== ''): ?>
        <a href="liste.php" style="margin-left: 10px;">Réinitialiser (voir tout)</a>
    <?php endif; ?>
</form>

<?php if ($erreurRecherche): ?>
    <p class="erreur">Une erreur est survenue lors de la recherche.</p>
<?php elseif (!$personnes && $termeRecherche !== ''): ?>
    <p>Aucune personne trouvée pour la recherche « <?= h($termeRecherche) ?> ».</p>
<?php elseif (!$personnes): ?>
    <p>Aucune personne enregistrée.</p>
<?php else: ?>
    <?php if ($termeRecherche !== ''): ?>
        <p class="ok"><?= count($personnes) ?> personne(s) trouvée(s) pour « <?= h($termeRecherche) ?> » :</p>
    <?php endif; ?>
    <table class="liste">
        <tr>
            <th>ID</th><th>N°</th><th>Civ.</th><th>Nom / Prénom</th><th>Filière</th><th>Adresse</th><th>CP / Localité</th>
            <th>Pays</th><th>Plateformes</th><th>Applications</th><th>Transport</th><th>Sport</th><th>Voiture</th>
            <th>Sang</th><th>Modules</th><th>Activités</th><th>Nationalité</th><th>Photo</th><th>Action</th>
        </tr>
        <?php foreach ($personnes as $p): ?>
            <tr>
                <td><?= h($p['id']) ?></td>
                <td><?= h($p['numero']) ?></td>
                <td><?= h($p['civilite']) ?></td>
                <td><?= h($p['nom_prenom']) ?></td>
                <td><?= h($p['filiere'] ?? '') ?></td>
                <td><?= h($p['adresse']) ?></td>
                <td><?= h($p['code_postal']) ?> <?= h($p['localite']) ?></td>
                <td><?= h($p['pays']) ?></td>
                <td><?= h($p['plateformes']) ?></td>
                <td><?= h($p['applications']) ?></td>
                <td><?= h($p['transport']) ?></td>
                <td><?= h($p['sport']) ?></td>
                <td><?= h($p['modele_voiture'] ?? '') ?></td>
                <td><?= h($p['groupe_sanguin'] ?? '') ?></td>
                <td><?= h($p['modules'] ?? '') ?></td>
                <td><?= h($p['activites']) ?></td>
                <td><?= h($p['nationalite']) ?></td>
                <td>
                    <?php if ($p['photo']): ?>
                        <img src="../uploads/<?= h($p['photo']) ?>" alt="photo">
                    <?php endif; ?>
                </td>
                <td>
                    <a href="modifier.php?id=<?= h($p['id']) ?>">Modifier</a>
                    <a href="../bulletin/bulletin.php?id=<?= h($p['id']) ?>">Bulletin</a>
                    <form method="post" action="supprimer.php" onsubmit="return confirm('Supprimer définitivement la personne <?= h($p['nom_prenom']) ?> (ID <?= h($p['id']) ?>) ?');">
                        <input type="hidden" name="id" value="<?= h($p['id']) ?>">
                        <button type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><a href="formulaire.php">Nouvelle personne</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
