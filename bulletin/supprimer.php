<?php
// bulletin/supprimer.php : supprime une note de la base de données
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bulletin.php');
    exit;
}

$noteId     = filter_var($_POST['id'] ?? $_POST['note_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$etudiantId = filter_var($_POST['etudiant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$moduleId   = filter_var($_POST['module_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$erreurs = [];
$succes = false;
$messageSucces = '';
$note = null;

try {
    // 1. Rechercher la note à supprimer
    if ($noteId) {
        $stmt = $pdo->prepare('
            SELECT n.id, n.note, n.etudiant_id, n.module_id,
                   p.nom_prenom, m.designation, m.code_module
            FROM note n
            JOIN personne p ON p.id = n.etudiant_id
            JOIN module m ON m.id = n.module_id
            WHERE n.id = :id
        ');
        $stmt->execute([':id' => $noteId]);
        $note = $stmt->fetch();
    } elseif ($etudiantId && $moduleId) {
        $stmt = $pdo->prepare('
            SELECT n.id, n.note, n.etudiant_id, n.module_id,
                   p.nom_prenom, m.designation, m.code_module
            FROM note n
            JOIN personne p ON p.id = n.etudiant_id
            JOIN module m ON m.id = n.module_id
            WHERE n.etudiant_id = :eid AND n.module_id = :mid
        ');
        $stmt->execute([':eid' => $etudiantId, ':mid' => $moduleId]);
        $note = $stmt->fetch();
    } else {
        $erreurs[] = "Paramètres manquants : aucun identifiant de note ou couple (étudiant, module) fourni pour la suppression.";
    }

    if (!$note && !$erreurs) {
        $erreurs[] = "Impossible de supprimer : la note demandée est introuvable dans la base de données ou a déjà été supprimée.";
    } elseif ($note) {
        $stmtDelete = $pdo->prepare('DELETE FROM note WHERE id = :id');
        $stmtDelete->execute([':id' => $note['id']]);

        $succes = true;
        $noteFormattee = number_format((float)$note['note'], 2, ',', ' ');
        $messageSucces = "La note de {$noteFormattee} / 20 pour l'étudiant " . $note['nom_prenom'] . " en " . $note['designation'] . " (" . $note['code_module'] . ") a été supprimée avec succès.";
    }
} catch (PDOException $e) {
    $erreurs[] = "Erreur de base de données : " . $e->getMessage();
}

$base  = '../';
$titre = 'Suppression d’une note';
require __DIR__ . '/../includes/header.php';

if ($erreurs) {
    foreach ($erreurs as $e) {
        echo '<p class="erreur">' . h($e) . '</p>';
    }
    $urlRetour = 'bulletin.php';
    if ($etudiantId) {
        $urlRetour .= '?id=' . urlencode((string)$etudiantId);
    }
    echo '<p><a href="' . h($urlRetour) . '">Retour au formulaire</a> | <a href="liste.php">Affichage Liste</a></p>';
} else {
    echo '<p class="ok">' . h($messageSucces) . '</p>';
    $urlFiche = 'bulletin.php';
    if ($note && !empty($note['etudiant_id'])) {
        $urlFiche .= '?id=' . urlencode((string)$note['etudiant_id']);
    }
    echo '<p><a href="' . h($urlFiche) . '">Retour au relevé de l\'étudiant</a> | <a href="bulletin.php">Nouveau bulletin</a> | <a href="liste.php">Affichage Liste</a></p>';
}

require __DIR__ . '/../includes/footer.php';
