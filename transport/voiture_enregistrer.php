<?php
// transport/voiture_enregistrer.php : enregistre un modèle de voiture dans la table `modele_voiture`
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: voiture.php');
    exit;
}

$libelle = $_POST['libelle'] ?? '';
$libelle = is_string($libelle) ? trim($libelle) : '';
$erreurs = [];

if ($libelle === '' || mb_strlen($libelle) > 50) {
    $erreurs[] = "Le modèle de voiture est obligatoire (50 caractères maximum).";
}

if (!$erreurs) {
    try {
        $stmt = $pdo->prepare('INSERT INTO modele_voiture (libelle) VALUES (:libelle)');
        $stmt->execute([':libelle' => $libelle]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $erreurs[] = "Ce modèle de voiture existe déjà.";
        } else {
            $erreurs[] = "Erreur de base de données.";
        }
    }
}

$base = '../';
$titre = 'Enregistrement modèle de voiture';
require __DIR__ . '/../includes/header.php';
if ($erreurs) {
    foreach ($erreurs as $erreur) {
        echo '<p class="erreur">' . h($erreur) . '</p>';
    }
    echo '<p><a href="voiture.php">Retour</a></p>';
} else {
    echo '<p class="ok">Modèle de voiture enregistré : ' . h($libelle) . '</p>';
    echo '<p><a href="voiture.php">Ajouter un autre</a> | <a href="voiture_liste.php">Affichage Liste</a></p>';
}
require __DIR__ . '/../includes/footer.php';
