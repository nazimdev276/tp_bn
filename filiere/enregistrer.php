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
        if ($action === 'modifier') {
            if ($idValide === null) {
                $erreurs[] = "Veuillez préciser l'identifiant (ID) de la filière à modifier.";
            } else {
                $stmt = $pdo->prepare('UPDATE filiere SET nom = :nom WHERE id = :id');
                $stmt->execute([':nom' => $nom, ':id' => $idValide]);
                if ($stmt->rowCount() > 0) {
                    $succes = true;
                    $messageSucces = "Filière ID {$idValide} modifiée avec succès.";
                } else {
                    $check = $pdo->prepare('SELECT COUNT(*) FROM filiere WHERE id = :id');
                    $check->execute([':id' => $idValide]);
                    if ($check->fetchColumn() > 0) {
                        $succes = true;
                        $messageSucces = "Aucune modification apportée (valeur identique).";
                    } else {
                        $erreurs[] = "Aucune filière trouvée avec l'ID {$idValide}.";
                    }
                }
            }
        } else {
            // Ajouter / Enregistrer
            if ($idValide !== null) {
                $check = $pdo->prepare('SELECT COUNT(*) FROM filiere WHERE id = :id');
                $check->execute([':id' => $idValide]);
                if ($check->fetchColumn() > 0) {
                    $stmt = $pdo->prepare('UPDATE filiere SET nom = :nom WHERE id = :id');
                    $stmt->execute([':nom' => $nom, ':id' => $idValide]);
                    $messageSucces = "Filière ID {$idValide} mise à jour avec succès.";
                } else {
                    $stmt = $pdo->prepare('INSERT INTO filiere (id, nom) VALUES (:id, :nom)');
                    $stmt->execute([':id' => $idValide, ':nom' => $nom]);
                    $messageSucces = "Filière ajoutée avec succès (ID {$idValide}).";
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO filiere (nom) VALUES (:nom)');
                $stmt->execute([':nom' => $nom]);
                $nouvelId = (int)$pdo->lastInsertId();
                $messageSucces = "Filière ajoutée avec succès (ID {$nouvelId}).";
            }
            $succes = true;
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
