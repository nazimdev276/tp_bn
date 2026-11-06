<?php
// filiere/enregistrer.php : insère ou met à jour une filière dans MySQL
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: filiere.php');
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? 'enregistrer');
$idRaw  = trim((string)($_POST['id'] ?? ''));
$nom    = trim((string)($_POST['nom'] ?? ''));

$erreurs = [];
$idValide = null;

if ($idRaw !== '') {
    $idValide = filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idValide === false) {
        $erreurs[] = "L'identifiant doit être un entier positif.";
    }
}

if ($nom === '' || mb_strlen($nom) > 50) {
    $erreurs[] = "Le nom de la filière est obligatoire (50 caractères maximum).";
}

$succes = false;
$messageSucces = '';

if (!$erreurs) {
    try {
        $resultat = enregistrer_avec_id($pdo, 'filiere', ['nom' => $nom], $idValide, $action === 'modifier');

        if ($resultat['status'] === 'missing_id') {
            $erreurs[] = "Veuillez préciser l'identifiant (ID) de la filière à modifier.";
        } elseif ($resultat['status'] === 'not_found') {
            $erreurs[] = "Aucune filière trouvée avec l'ID {$idValide}.";
        } elseif ($resultat['status'] === 'unchanged') {
            $succes = true;
            $messageSucces = "Aucune modification apportée (valeur identique).";
        } else {
            $succes = true;
            if ($resultat['status'] === 'updated') {
                $messageSucces = $action === 'modifier'
                    ? "Filière ID {$idValide} modifiée avec succès."
                    : "Filière ID {$idValide} mise à jour avec succès.";
            } else {
                $messageSucces = "Filière ajoutée avec succès (ID {$resultat['id']}).";
            }
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $erreurs[] = "Ce nom de filière ou cet ID existe déjà.";
        } else {
            $erreurs[] = "Erreur de base de données : " . $e->getMessage();
        }
    }
}

$base = '../';
$titre = 'Enregistrement filière';
require __DIR__ . '/../includes/header.php';

if ($erreurs) {
    foreach ($erreurs as $e) {
        echo '<p class="erreur">' . h($e) . '</p>';
    }
    echo '<p><a href="javascript:history.back()">Retour au formulaire</a> | <a href="filiere.php">Nouvelle filière</a></p>';
} else {
    echo '<p class="ok">' . h($messageSucces) . '</p>';
    echo '<p><a href="filiere.php">Ajouter une autre</a> | <a href="liste.php">Affichage Liste</a></p>';
}

require __DIR__ . '/../includes/footer.php';
