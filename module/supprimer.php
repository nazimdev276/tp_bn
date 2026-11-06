<?php
// module/supprimer.php : supprime un module
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$erreur = null;
$supprime = false;
$id = $_POST['id'] ?? ($_GET['id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $erreur = "La suppression doit être effectuée via le formulaire.";
} else {
    try {
        $resultat = supprimer_par_id($pdo, 'module', $id);
        if ($resultat['status'] === 'deleted') {
            $supprime = true;
        } elseif ($resultat['status'] === 'invalid') {
            $erreur = "Identifiant invalide : le module n'a pas été supprimé.";
        } else {
            $erreur = "Aucun module trouvé avec cet identifiant.";
        }
    } catch (PDOException $e) {
        $erreur = "Échec de la suppression du module.";
    }
}

$base = '../';
$titre = 'Suppression d’un module';
require __DIR__ . '/../includes/header.php';

if ($supprime) {
    echo '<p class="ok">Le module ID ' . h($id) . ' a été supprimé.</p>';
} else {
    echo '<p class="erreur">' . h($erreur) . '</p>';
}
echo '<p><a href="liste.php">Retour à la liste</a> | <a href="module.php">Formulaire Module</a></p>';
require __DIR__ . '/../includes/footer.php';
