<?php
// bulletin/enregistrer.php : insère ou met à jour une note dans MySQL
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bulletin.php');
    exit;
}

$action     = $_POST['action'] ?? ($_GET['action'] ?? 'enregistrer');
$etudiantId = filter_var($_POST['etudiant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$moduleId   = filter_var($_POST['module_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$noteId     = filter_var($_POST['note_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$noteRaw    = trim((string)($_POST['note'] ?? ''));

// Remplacer virgule par point pour les décimales (ex: 14,5 -> 14.5)
$noteNormalized = str_replace(',', '.', $noteRaw);

$erreurs = [];
$etudiant = null;
$module = null;
$noteVal = null;

// 1. Validation de l'étudiant
if ($etudiantId === false || $etudiantId === null) {
    $erreurs[] = "Étudiant non spécifié. Veuillez d'abord rechercher et sélectionner un étudiant valide.";
} else {
    $stmt = $pdo->prepare('SELECT id, numero, nom_prenom, filiere FROM personne WHERE id = :id');
    $stmt->execute([':id' => $etudiantId]);
    $etudiant = $stmt->fetch() ?: null;
    if (!$etudiant) {
        $erreurs[] = "L'étudiant spécifié (ID {$etudiantId}) est introuvable dans la base de données.";
    }
}

// 2. Validation du module
if ($moduleId === false || $moduleId === null) {
    $erreurs[] = "Veuillez sélectionner un module d'enseignement valide.";
} else {
    $stmt = $pdo->prepare('SELECT id, code_module, designation, coefficient FROM module WHERE id = :id');
    $stmt->execute([':id' => $moduleId]);
    $module = $stmt->fetch() ?: null;
    if (!$module) {
        $erreurs[] = "Le module sélectionné (ID {$moduleId}) est introuvable dans la base de données.";
    }
}

// 3. Validation de la note
if ($noteRaw === '') {
    $erreurs[] = "La note est obligatoire.";
} elseif (!is_numeric($noteNormalized)) {
    $erreurs[] = "La note doit être une valeur numérique (ex: 14.50 ou 18).";
} else {
    $noteVal = (float)$noteNormalized;
    if ($noteVal < 0 || $noteVal > 20) {
        $erreurs[] = "La note doit être comprise entre 0 et 20 (reçu : {$noteRaw}).";
    }
}

$succes = false;
$messageSucces = '';

if (!$erreurs && $etudiant && $module && $noteVal !== null) {
    try {
        // Vérifier si une note existe déjà pour ce couple (étudiant, module)
        $stmtCheck = $pdo->prepare('SELECT id, note FROM note WHERE etudiant_id = :eid AND module_id = :mid');
        $stmtCheck->execute([
            ':eid' => $etudiant['id'],
            ':mid' => $module['id'],
        ]);
        $noteExistante = $stmtCheck->fetch() ?: null;

        if ($action === 'modifier') {
            // Modification de la note
            if (!$noteExistante) {
                $erreurs[] = "Aucune note existante n'a été trouvée pour cet étudiant dans le module « " . $module['designation'] . " ». Utilisez le bouton « Enregistrer » pour créer une nouvelle note.";
            } else {
                $stmtUpdate = $pdo->prepare('
                    UPDATE note
                    SET note = :note
                    WHERE id = :id
                ');
                $stmtUpdate->execute([
                    ':note' => $noteVal,
                    ':id'   => $noteExistante['id'],
                ]);

                $succes = true;
                $noteFormattee = number_format($noteVal, 2, ',', ' ');
                $messageSucces = "Note mise à jour avec succès : {$noteFormattee} / 20 pour l'étudiant " . $etudiant['nom_prenom'] . " dans le module " . $module['designation'] . " (" . $module['code_module'] . ").";
            }
        } else {
            // Enregistrement d'une nouvelle note
            if ($noteExistante) {
                // Règle : Empêcher les doublons pour le même étudiant et même module
                $noteActuelle = number_format((float)$noteExistante['note'], 2, ',', ' ');
                $erreurs[] = "Une note existe déjà pour cet étudiant dans le module « " . $module['designation'] . " » (Note actuelle : {$noteActuelle} / 20). Pour modifier cette note, veuillez cliquer sur le bouton « Modifier ».";
            } else {
                $stmtInsert = $pdo->prepare('
                    INSERT INTO note (etudiant_id, module_id, note)
                    VALUES (:eid, :mid, :note)
                ');
                $stmtInsert->execute([
                    ':eid'  => $etudiant['id'],
                    ':mid'  => $module['id'],
                    ':note' => $noteVal,
                ]);

                $succes = true;
                $noteFormattee = number_format($noteVal, 2, ',', ' ');
                $messageSucces = "Note enregistrée avec succès : {$noteFormattee} / 20 pour l'étudiant " . $etudiant['nom_prenom'] . " dans le module " . $module['designation'] . " (" . $module['code_module'] . ").";
            }
        }
    } catch (PDOException $e) {
        $erreurs[] = "Erreur de base de données : " . $e->getMessage();
    }
}

$base  = '../';
$titre = 'Enregistrement note';
require __DIR__ . '/../includes/header.php';

if ($erreurs) {
    foreach ($erreurs as $e) {
        echo '<p class="erreur">' . h($e) . '</p>';
    }
    $urlRetour = 'bulletin.php';
    if ($etudiantId) {
        $urlRetour .= '?id=' . urlencode((string)$etudiantId);
        if ($moduleId) {
            $urlRetour .= '&module_id=' . urlencode((string)$moduleId);
        }
    }
    echo '<p><a href="' . h($urlRetour) . '">Retour au formulaire du bulletin</a> | <a href="bulletin.php">Nouveau bulletin</a></p>';
} else {
    echo '<p class="ok">' . h($messageSucces) . '</p>';
    $urlFiche = 'bulletin.php?id=' . urlencode((string)$etudiant['id']);
    echo '<p><a href="' . h($urlFiche) . '">Consulter le relevé de l\'étudiant</a> | <a href="bulletin.php">Saisir une autre note</a> | <a href="liste.php">Affichage Liste</a></p>';
}

require __DIR__ . '/../includes/footer.php';
