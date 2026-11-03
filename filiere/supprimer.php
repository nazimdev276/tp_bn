<?php
// filiere/supprimer.php : supprime une filière
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$erreur = null;
$supprime = false;
$id = $_POST['id'] ?? ($_GET['id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $erreur = "La suppression doit être demandée via le formulaire.";
} elseif ((!is_string($id) && !is_int($id)) || filter_var((string)$id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    $erreur = "Identifiant invalide : la filière n'a pas été supprimée.";
} else {
    try {
        $stmt = $pdo->prepare('DELETE FROM filiere WHERE id = :id');
        $stmt->execute([':id' => (int)$id]);
        if ($stmt->rowCount() === 1) {
            $supprime = true;
        } else {
            $erreur = "Aucune filière trouvée pour cet identifiant.";
        }
    } catch (PDOException $e) {
        $erreur = "Échec de la suppression de la filière.";
    }
}

$base = '../';
$titre = 'Suppression d’une filière';
require __DIR__ . '/../includes/header.php';

if ($supprime) {
    echo '<p class="ok">La filière ID ' . h($id) . ' a été supprimée.</p>';
} else {
    echo '<p class="erreur">' . h($erreur) . '</p>';
}
echo '<p><a href="liste.php">Retour à la liste</a> | <a href="filiere.php">Formulaire Filière</a></p>';
require __DIR__ . '/../includes/footer.php';
