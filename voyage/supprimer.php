<?php
// voyage/supprimer.php : suppression d'un enregistrement de voyage
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$erreur = null;
$supprime = false;
$id = $_POST['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $erreur = "La suppression doit être effectuée via une requête POST valide.";
} elseif ((!is_string($id) && !is_int($id)) || filter_var((string)$id, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 2147483647],
]) === false) {
    $erreur = "Identifiant invalide : le voyage n'a pas été supprimé.";
} else {
    try {
        $stmt = $pdo->prepare('DELETE FROM voyager WHERE id = :id');
        $stmt->execute([':id' => (int)$id]);
        if ($stmt->rowCount() === 1) {
            $supprime = true;
        } else {
            $erreur = "Aucun voyage trouvé pour cet identifiant dans la table Voyager.";
        }
    } catch (PDOException $e) {
        $erreur = "Échec de la suppression du voyage : " . $e->getMessage();
    }
}

$base = '../';
$titre = 'Suppression d’un voyage';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($supprime): ?>
    <p class="ok">Le voyage ID <?= h($id) ?> a été supprimé avec succès.</p>
<?php else: ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php endif; ?>

<p><a href="liste.php">Retour à la liste des voyages</a> | <a href="voyage.php">Formulaire voyage</a></p>

<?php require __DIR__ . '/../includes/footer.php'; ?>
