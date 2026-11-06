<?php
// voyage/supprimer.php : suppression d'un enregistrement de voyage
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$erreur = null;
$supprime = false;
$id = $_POST['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $erreur = "La suppression doit être effectuée via une requête POST valide.";
} else {
    try {
        $resultat = supprimer_par_id($pdo, 'voyager', $id);
        if ($resultat['status'] === 'deleted') {
            $supprime = true;
        } elseif ($resultat['status'] === 'invalid') {
            $erreur = "Identifiant invalide : le voyage n'a pas été supprimé.";
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
