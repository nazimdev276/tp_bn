<?php
// filiere/filiere.php : formulaire de gestion des filières (ID et nom)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$idRecherche = trim((string)($_GET['id'] ?? ''));
$filiere = null;
$messageRecherche = null;

if ($idRecherche !== '') {
    $idValide = filter_var($idRecherche, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idValide !== false) {
        $stmt = $pdo->prepare('SELECT * FROM filiere WHERE id = :id');
        $stmt->execute([':id' => $idValide]);
        $filiere = $stmt->fetch() ?: null;
        if ($filiere) {
            $messageRecherche = "Filière trouvée (ID {$idValide}) : vous pouvez la modifier ou la supprimer.";
        } else {
            $messageRecherche = "Aucune filière avec l'ID {$idValide}. Remplissez le nom pour la créer avec cet ID.";
        }
    }
}

$idCourant = $filiere['id'] ?? $idRecherche;
$nomCourant = $filiere['nom'] ?? '';

$base = '../';
$titre = 'Gestion des filières';
require __DIR__ . '/../includes/header.php';
?>

<form method="get" action="filiere.php" class="form-recherche">
    <label for="recherche-id">Rechercher par ID :</label>
    <input type="number" name="id" id="recherche-id" min="1" value="<?= h($idRecherche) ?>" placeholder="ex: 1">
    <button type="submit">Rechercher</button>
    <?php if ($idRecherche !== ''): ?>
        <a href="filiere.php" style="margin-left: 10px;">Réinitialiser</a>
    <?php endif; ?>
</form>

<p>
    <a href="statistiques.php" class="btn-link" style="display:inline-block; padding:9px 18px; background:var(--primary); color:#062b29; border-radius:var(--radius-md); font-weight:600; text-decoration:none;">
        Voir les statistiques de réussite
    </a>
</p>

<?php if ($messageRecherche): ?>
    <p class="<?= $filiere ? 'ok' : '' ?>"><?= h($messageRecherche) ?></p>
<?php endif; ?>

<form id="form-filiere" method="post" action="enregistrer.php">
    <table class="formulaire">
        <tr>
            <td>ID :</td>
            <td>
                <input type="number" name="id" id="filiere-id" min="1" size="10" placeholder="Auto ou spécifier" value="<?= h($idCourant) ?>">
                <span class="petit" style="color: var(--text-muted); font-size: 12px; margin-left: 8px;">(Laisser vide pour auto-incrémentation lors de l'ajout)</span>
            </td>
        </tr>
        <tr>
            <td>Nom de la filière :</td>
            <td>
                <input type="text" name="nom" id="filiere-nom" maxlength="50" size="30" placeholder="ex: 3ISIL" value="<?= h($nomCourant) ?>" required>
            </td>
        </tr>
    </table>

    <button type="submit" name="action" value="ajouter">Ajouter</button>
    <button type="submit" name="action" value="modifier" formaction="enregistrer.php?action=modifier">Modifier</button>
    <button type="submit" name="action" value="supprimer" formaction="supprimer.php" formnovalidate onclick="return confirm('Supprimer cette filière ?');">Supprimer</button>
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
    <?php if ($nomCourant !== ''): ?>
        <a href="etudiants.php?filiere=<?= h(urlencode($nomCourant)) ?>" class="btn-link" style="display:inline-block; padding:9px 18px; margin:4px; background:var(--primary); color:#062b29; border-radius:var(--radius-md); font-weight:600; text-decoration:none;">Rechercher les étudiants</a>
    <?php endif; ?>
    <button type="reset">Réinitialiser</button>
</form>

<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
