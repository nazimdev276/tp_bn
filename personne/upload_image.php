<?php
// personne/upload_image.php : bouton "Insérer Image"
// Envoie l'image choisie dans le dossier uploads/ puis l'affiche.
require_once __DIR__ . '/../includes/fonctions.php';

$base  = '../';
$titre = 'Insérer Image';
require __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<p><a href="formulaire.php">Retour au formulaire</a></p>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

[$nom, $erreur] = enregistrer_image($_FILES['photo'] ?? []);

if ($erreur) {
    echo '<p class="erreur">' . h($erreur) . '</p>';
} elseif ($nom === null) {
    echo '<p class="erreur">Veuillez choisir une image.</p>';
} else {
    echo '<p class="ok">Image envoyée.</p>';
    echo '<img src="../uploads/' . h($nom) . '" style="max-width:200px; border-radius:8px; border:1px solid #cbd5e1; box-shadow:0 4px 10px rgba(0,0,0,0.08);" alt="image">';
}
?>
<p><a href="formulaire.php">Retour au formulaire</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
