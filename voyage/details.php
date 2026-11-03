<?php
// voyage/details.php : fiche détaillée complète pour consultation et impression (Print)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$id = $_GET['id'] ?? '';
$voyage = null;
$erreur = null;

if (!is_string($id) && !is_int($id)) {
    $erreur = "Identifiant invalide.";
} else {
    $idValide = filter_var((string)$id, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);
    if ($idValide === false) {
        $erreur = "Identifiant invalide.";
    } else {
        $stmt = $pdo->prepare('SELECT v.*, n.libelle AS nationalite_libelle 
                               FROM voyager v 
                               LEFT JOIN nationalite n ON n.code = v.nationalite_code 
                               WHERE v.id = :id');
        $stmt->execute([':id' => $idValide]);
        $voyage = $stmt->fetch() ?: null;
        if (!$voyage) {
            $erreur = "Aucun voyage trouvé pour l'identifiant " . h($idValide) . ".";
        }
    }
}

$base  = '../';
$titre = 'Détails du voyage';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($erreur): ?>
    <p class="erreur"><?= h($erreur) ?></p>
    <p><a href="liste.php">Retour à la liste</a> | <a href="voyage.php">Formulaire voyage</a></p>
<?php else: ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h3 style="margin: 0; color: var(--text-heading);">Fiche Complète : Étudiant & Voyage #<?= h($voyage['id']) ?></h3>
            <span style="color: var(--text-muted); font-size: 13.5px;">Toutes les informations enregistrées dans la table Voyager</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" onclick="window.print()" style="background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">🖨️ Imprimer la fiche (Print)</button>
            <a href="voyage.php?id=<?= h($voyage['id']) ?>" style="display: inline-block; padding: 8px 14px; background: white; border: 1px solid var(--border-medium); border-radius: var(--radius-md); text-decoration: none;">Modifier</a>
            <a href="liste.php" style="display: inline-block; padding: 8px 14px; background: white; border: 1px solid var(--border-medium); border-radius: var(--radius-md); text-decoration: none;">Retour à la liste</a>
        </div>
    </div>

    <table class="liste" style="width: 100%; margin-bottom: 24px;">
        <tr><th colspan="2" style="background: var(--primary); color: white; text-align: left;">1. Identification de l'Étudiant</th></tr>
        <tr><td style="width: 250px;"><strong>Identifiant Voyage (ID)</strong></td><td><?= h($voyage['id']) ?></td></tr>
        <tr><td><strong>ID Étudiant rattaché</strong></td><td><?= h($voyage['etudiant_id'] ?? '—') ?></td></tr>
        <tr><td><strong>Numéro</strong></td><td><?= h($voyage['numero']) ?></td></tr>
        <tr><td><strong>Civilité</strong></td><td><?= h($voyage['civilite']) ?></td></tr>
        <tr><td><strong>Nom / Prénom</strong></td><td><strong><?= h($voyage['nom_prenom']) ?></strong></td></tr>
        <tr><td><strong>Filière</strong></td><td><?= h($voyage['filiere'] ?: '—') ?></td></tr>
        <tr><td><strong>Adresse</strong></td><td><?= h($voyage['adresse']) ?></td></tr>
        <tr><td><strong>Code postal / Localité</strong></td><td><?= h($voyage['code_postal']) ?> <?= h($voyage['localite']) ?></td></tr>
        <tr><td><strong>Pays</strong></td><td><?= h($voyage['pays']) ?></td></tr>
        <tr><td><strong>Nationalité</strong></td><td><?= h($voyage['nationalite_libelle'] ?? ($voyage['nationalite_code'] ?: '—')) ?></td></tr>

        <tr><th colspan="2" style="background: #6b21a8; color: white; text-align: left;">2. Les 3 Fonctionnalités Uniques du Voyage</th></tr>
        <tr style="background: #faf5ff;">
            <td><strong style="color: #5b21b6;">Méthodes de voyage (Travel methods)</strong></td>
            <td><strong style="color: #5b21b6;"><?= h($voyage['travel_methodes'] ?: '—') ?></strong></td>
        </tr>
        <tr style="background: #faf5ff;">
            <td><strong style="color: #5b21b6;">Accompagnement (Travel with)</strong></td>
            <td><strong style="color: #5b21b6;"><?= h($voyage['travel_with'] ?: '—') ?></strong></td>
        </tr>
        <tr style="background: #faf5ff;">
            <td><strong style="color: #5b21b6;">Description (Commentaires)</strong></td>
            <td><div style="white-space: pre-wrap; font-size: 13.5px;"><?= h($voyage['description'] ?: '—') ?></div></td>
        </tr>

        <tr><th colspan="2" style="background: var(--bg-alt); color: var(--text-heading); text-align: left;">3. Informations Complémentaires Étudiant</th></tr>
        <tr><td><strong>Transport habituel</strong></td><td><?= h($voyage['transport'] ?: '—') ?></td></tr>
        <tr><td><strong>Sport</strong></td><td><?= h($voyage['sport'] ?: '—') ?></td></tr>
        <tr><td><strong>Modèle(s) de voiture</strong></td><td><?= h($voyage['modele_voiture'] ?: '—') ?></td></tr>
        <tr><td><strong>Groupe sanguin</strong></td><td><?= h($voyage['groupe_sanguin'] ?: '—') ?></td></tr>
        <tr><td><strong>Plateforme(s)</strong></td><td><?= h($voyage['plateformes'] ?: '—') ?></td></tr>
        <tr><td><strong>Application(s)</strong></td><td><?= h($voyage['applications'] ?: '—') ?></td></tr>
        <tr><td><strong>Module(s)</strong></td><td><?= h($voyage['modules'] ?: '—') ?></td></tr>
        <tr><td><strong>Activité(s)</strong></td><td><?= h($voyage['activites'] ?: '—') ?></td></tr>
        <?php if (!empty($voyage['photo'])): ?>
            <tr>
                <td><strong>Photo</strong></td>
                <td>
                    <img src="../uploads/<?= h($voyage['photo']) ?>" alt="Photo de l'étudiant" style="max-height: 120px; border-radius: var(--radius-sm); border: 1px solid var(--border-medium);">
                </td>
            </tr>
        <?php endif; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
