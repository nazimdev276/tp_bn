<?php
// enseignant/supprimer.php : supprime un enseignant
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$erreur = null;
$supprime = false;
$id = $_POST['id'] ?? ($_GET['id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $erreur = "La suppression doit être effectuée via le formulaire.";
} elseif ((!is_string($id) && !is_int($id)) || filter_var((string)$id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    $erreur = "Identifiant invalide : l'enseignant n'a pas été supprimé.";
} else {
    try {
        $stmt = $pdo->prepare('DELETE FROM enseignant WHERE id = :id');
        $stmt->execute([':id' => (int)$id]);
        if ($stmt->rowCount() === 1) {
            $supprime = true;
        } else {
            $erreur = "Aucun enseignant trouvé avec cet identifiant.";
        }
    } catch (PDOException $e) {
        $erreur = "Échec de la suppression de l'enseignant.";
    }
}

$base = '../';
$titre = 'Suppression d’un enseignant';
require __DIR__ . '/../includes/header.php';

if ($supprime) {
    echo '<p class="ok">L\'enseignant ID ' . h($id) . ' a été supprimé.</p>';
} else {
    echo '<p class="erreur">' . h($erreur) . '</p>';
}
echo '<p><a href="liste.php">Retour à la liste</a> | <a href="enseignant.php">Formulaire Enseignant</a></p>';
require __DIR__ . '/../includes/footer.php';
