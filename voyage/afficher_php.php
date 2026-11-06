<?php
// voyage/afficher_php.php : bouton "Affichage PHP" du formulaire Voyage
// Affiche l'ensemble des données reçues (données étudiantes + 3 fonctionnalités Voyage) sans enregistrer.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

$base  = '../';
$titre = 'Affichage PHP Voyage';
require __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<p>Aucune donnée reçue. <a href="voyage.php">Retour au formulaire voyage</a></p>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$travelMethodes = post_liste('travel_methodes', $TRAVEL_METHODES);
$travelWith     = $_POST['travel_with'] ?? '';
$description    = $_POST['description'] ?? '';

$plateformes   = post_liste('plateformes', $PLATEFORMES);
$applications  = post_liste('applications', $APPLICATIONS);
$activites     = post_liste('activites', $ACTIVITES);
$modules       = post_liste('modules', $MODULES);
$groupeSanguin = is_string($_POST['groupe_sanguin'] ?? null) ? trim($_POST['groupe_sanguin']) : '';
$modeleVoiture = is_array($_POST['modele_voiture'] ?? null) ? ecrire_liste_texte($_POST['modele_voiture']) : (is_string($_POST['modele_voiture'] ?? null) ? trim($_POST['modele_voiture']) : '');

$transport = $_POST['transport'] ?? '';
$sport     = $_POST['sport'] ?? '';

// Libellé de la nationalité
$libelleNat = '';
if (!empty($_POST['nationalite'])) {
    $stmt = $pdo->prepare('SELECT libelle FROM nationalite WHERE code = :code');
    $stmt->execute([':code' => $_POST['nationalite']]);
    $ligne = $stmt->fetch();
    if ($ligne) {
        $libelleNat = $ligne['libelle'];
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <p class="ok" style="margin: 0;">Données reçues du formulaire Voyage (Affichage PHP uniquement, non sauvegardé).</p>
    <button type="button" onclick="window.print()">🖨️ Imprimer la fiche (Print)</button>
</div>

<table class="liste">
    <tr><th colspan="2" style="background: var(--primary); color: white; text-align: left;">Informations Principales</th></tr>
    <tr><td style="width: 250px;">Numéro</td><td><?= h($_POST['numero'] ?? '') ?></td></tr>
    <tr><td>Civilité</td><td><?= h($_POST['civilite'] ?? '') ?></td></tr>
    <tr><td>Nom / Prénom</td><td><strong><?= h($_POST['nom_prenom'] ?? '') ?></strong></td></tr>
    <tr><td>Filière</td><td><?= h($_POST['filiere'] ?? '') ?></td></tr>
    <tr><td>Adresse</td><td><?= h($_POST['adresse'] ?? '') ?></td></tr>
    <tr><td>Pays</td><td><?= h($_POST['pays'] ?? '') ?></td></tr>

    <tr><th colspan="2" style="background: #6b21a8; color: white; text-align: left;">Les 3 Fonctionnalités Spécifiques de Voyage</th></tr>
    <tr style="background: #faf5ff;">
        <td><strong>Travel methodes (Méthodes de voyage)</strong></td>
        <td><strong><?= h(ecrire_liste_texte($travelMethodes) ?: '—') ?></strong></td>
    </tr>
    <tr style="background: #faf5ff;">
        <td><strong>Travel with (Accompagnement)</strong></td>
        <td><strong><?= h($travelWith ?: '—') ?></strong></td>
    </tr>
    <tr style="background: #faf5ff;">
        <td><strong>Description</strong></td>
        <td><div style="white-space: pre-wrap;"><?= h($description ?: '—') ?></div></td>
    </tr>

    <tr><th colspan="2" style="background: var(--bg-alt); text-align: left; color: var(--text-heading);">Informations Complémentaires de l'Étudiant</th></tr>
    <tr><td>No postal / Localité</td><td><?= h($_POST['code_postal'] ?? '') ?> <?= h($_POST['localite'] ?? '') ?></td></tr>
    <tr><td>Nationalité</td><td><?= h($libelleNat) ?><?= !empty($_POST['nationalite']) ? ' (' . h($_POST['nationalite']) . ')' : '—' ?></td></tr>
    <tr><td>Transport habituel</td><td><?= h($transport ?: '—') ?></td></tr>
    <tr><td>Sport</td><td><?= h($sport ?: '—') ?></td></tr>
    <tr><td>Modèle de voiture</td><td><?= h($modeleVoiture ?: '—') ?></td></tr>
    <tr><td>Groupe sanguin</td><td><?= h($groupeSanguin ?: '—') ?></td></tr>
    <tr><td>Plateforme(s)</td><td><?= h(ecrire_liste_texte($plateformes) ?: '—') ?></td></tr>
    <tr><td>Application(s)</td><td><?= h(ecrire_liste_texte($applications) ?: '—') ?></td></tr>
    <tr><td>Module(s)</td><td><?= h(ecrire_liste_texte($modules) ?: '—') ?></td></tr>
    <tr><td>Activité(s)</td><td><?= h(ecrire_liste_texte($activites) ?: '—') ?></td></tr>
</table>

<p style="margin-top: 16px;">
    <a href="javascript:history.back()">Retour au formulaire</a> | 
    <a href="voyage.php">Nouveau voyage</a> | 
    <a href="liste.php">Affichage Liste</a>
</p>

<?php require __DIR__ . '/../includes/footer.php'; ?>
