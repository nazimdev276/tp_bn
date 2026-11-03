<?php
// enseignant/enregistrer.php : insère ou met à jour un enseignant dans MySQL
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: enseignant.php');
    exit;
}

$action         = $_POST['action'] ?? ($_GET['action'] ?? 'enregistrer');
$idRaw          = trim((string)($_POST['id'] ?? ''));
$numero         = trim((string)($_POST['numero'] ?? ''));
$civilite       = trim((string)($_POST['civilite'] ?? ''));
$nomPrenom      = trim((string)($_POST['nom_prenom'] ?? ''));
$adresse        = trim((string)($_POST['adresse'] ?? ''));
$dateNaissance  = trim((string)($_POST['date_naissance'] ?? ''));
$lieuNaissance  = trim((string)($_POST['lieu_naissance'] ?? ''));
$pays           = trim((string)($_POST['pays'] ?? ''));
$grade          = trim((string)($_POST['grade'] ?? ''));
$specialite     = trim((string)($_POST['specialite'] ?? ''));
$filiere        = trim((string)($_POST['filiere'] ?? ''));

$modulesPost = $_POST['modules'] ?? [];
$modulesArray = [];
if (is_array($modulesPost)) {
    foreach ($modulesPost as $m) {
        $cleanM = trim((string)$m);
        if ($cleanM !== '') {
            $modulesArray[] = $cleanM;
        }
    }
} elseif (is_string($modulesPost) && trim($modulesPost) !== '') {
    $modulesArray = array_filter(array_map('trim', explode(',', $modulesPost)));
}
$modulesStr = implode(', ', $modulesArray);

$erreurs = [];
$idValide = null;

if ($idRaw !== '') {
    $idValide = filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idValide === false) {
        $erreurs[] = "L'identifiant doit être un nombre entier positif.";
    }
}

if (!preg_match('/^[0-9]{1,10}$/', $numero)) {
    $erreurs[] = "Numéro invalide (1 à 10 chiffres).";
}
if (!in_array($civilite, $CIVILITES, true)) {
    $erreurs[] = "Civilité invalide.";
}
if ($nomPrenom === '' || mb_strlen($nomPrenom) > 80) {
    $erreurs[] = "Nom / Prénom obligatoire (80 caractères maximum).";
}
if ($filiere === '' || mb_strlen($filiere) > 50) {
    $erreurs[] = "Filière obligatoire (50 caractères maximum).";
}
if ($adresse === '' || mb_strlen($adresse) > 150) {
    $erreurs[] = "Adresse obligatoire (150 caractères maximum).";
}
if ($dateNaissance === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateNaissance)) {
    $erreurs[] = "Date de naissance invalide (format AAAA-MM-JJ).";
}
if ($lieuNaissance === '' || mb_strlen($lieuNaissance) > 60) {
    $erreurs[] = "Lieu de naissance obligatoire (60 caractères maximum).";
}
if (!in_array($pays, $PAYS, true)) {
    $erreurs[] = "Pays sélectionné invalide.";
}
if (!in_array($grade, $GRADES, true)) {
    $erreurs[] = "Grade sélectionné invalide.";
}
if ($specialite === '' || mb_strlen($specialite) > 80) {
    $erreurs[] = "Spécialité obligatoire (80 caractères maximum).";
}

$succes = false;
$messageSucces = '';

if (!$erreurs) {
    try {
        if ($action === 'modifier') {
            if ($idValide === null) {
                $erreurs[] = "Veuillez préciser l'identifiant (ID) de l'enseignant à modifier.";
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE enseignant
                     SET numero = :numero, civilite = :civilite, nom_prenom = :nom_prenom,
                         adresse = :adresse, date_naissance = :date_naissance,
                         lieu_naissance = :lieu_naissance, pays = :pays, grade = :grade,
                         specialite = :specialite, filiere = :filiere, modules = :modules
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':numero'         => $numero,
                    ':civilite'       => $civilite,
                    ':nom_prenom'     => $nomPrenom,
                    ':adresse'        => $adresse,
                    ':date_naissance' => $dateNaissance,
                    ':lieu_naissance' => $lieuNaissance,
                    ':pays'           => $pays,
                    ':grade'          => $grade,
                    ':specialite'     => $specialite,
                    ':filiere'        => $filiere,
                    ':modules'        => $modulesStr,
                    ':id'             => $idValide,
                ]);
                if ($stmt->rowCount() > 0) {
                    $succes = true;
                    $messageSucces = "Enseignant ID {$idValide} modifié avec succès.";
                } else {
                    $check = $pdo->prepare('SELECT COUNT(*) FROM enseignant WHERE id = :id');
                    $check->execute([':id' => $idValide]);
                    if ($check->fetchColumn() > 0) {
                        $succes = true;
                        $messageSucces = "Aucune modification apportée (valeurs identiques).";
                    } else {
                        $erreurs[] = "Aucun enseignant trouvé pour l'ID {$idValide}.";
                    }
                }
            }
        } else {
            // Ajouter / Enregistrer
            if ($idValide !== null) {
                $check = $pdo->prepare('SELECT COUNT(*) FROM enseignant WHERE id = :id');
                $check->execute([':id' => $idValide]);
                if ($check->fetchColumn() > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE enseignant
                         SET numero = :numero, civilite = :civilite, nom_prenom = :nom_prenom,
                             adresse = :adresse, date_naissance = :date_naissance,
                             lieu_naissance = :lieu_naissance, pays = :pays, grade = :grade,
                             specialite = :specialite, filiere = :filiere, modules = :modules
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        ':numero'         => $numero,
                        ':civilite'       => $civilite,
                        ':nom_prenom'     => $nomPrenom,
                        ':adresse'        => $adresse,
                        ':date_naissance' => $dateNaissance,
                        ':lieu_naissance' => $lieuNaissance,
                        ':pays'           => $pays,
                        ':grade'          => $grade,
                        ':specialite'     => $specialite,
                        ':filiere'        => $filiere,
                        ':modules'        => $modulesStr,
                        ':id'             => $idValide,
                    ]);
                    $messageSucces = "Enseignant ID {$idValide} mis à jour avec succès.";
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO enseignant
                         (id, numero, civilite, nom_prenom, adresse, date_naissance, lieu_naissance, pays, grade, specialite, filiere, modules)
                         VALUES
                         (:id, :numero, :civilite, :nom_prenom, :adresse, :date_naissance, :lieu_naissance, :pays, :grade, :specialite, :filiere, :modules)'
                    );
                    $stmt->execute([
                        ':id'             => $idValide,
                        ':numero'         => $numero,
                        ':civilite'       => $civilite,
                        ':nom_prenom'     => $nomPrenom,
                        ':adresse'        => $adresse,
                        ':date_naissance' => $dateNaissance,
                        ':lieu_naissance' => $lieuNaissance,
                        ':pays'           => $pays,
                        ':grade'          => $grade,
                        ':specialite'     => $specialite,
                        ':filiere'        => $filiere,
                        ':modules'        => $modulesStr,
                    ]);
                    $messageSucces = "Enseignant ajouté avec succès (ID {$idValide}).";
                }
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO enseignant
                     (numero, civilite, nom_prenom, adresse, date_naissance, lieu_naissance, pays, grade, specialite, filiere, modules)
                     VALUES
                     (:numero, :civilite, :nom_prenom, :adresse, :date_naissance, :lieu_naissance, :pays, :grade, :specialite, :filiere, :modules)'
                );
                $stmt->execute([
                    ':numero'         => $numero,
                    ':civilite'       => $civilite,
                    ':nom_prenom'     => $nomPrenom,
                    ':adresse'        => $adresse,
                    ':date_naissance' => $dateNaissance,
                    ':lieu_naissance' => $lieuNaissance,
                    ':pays'           => $pays,
                    ':grade'          => $grade,
                    ':specialite'     => $specialite,
                    ':filiere'        => $filiere,
                    ':modules'        => $modulesStr,
                ]);
                $nouvelId = (int)$pdo->lastInsertId();
                $messageSucces = "Enseignant ajouté avec succès (ID {$nouvelId}).";
            }
            $succes = true;
        }
    } catch (PDOException $e) {
        $erreurs[] = "Erreur de base de données : " . $e->getMessage();
    }
}

$base = '../';
$titre = 'Enregistrement enseignant';
require __DIR__ . '/../includes/header.php';

if ($erreurs) {
    foreach ($erreurs as $e) {
        echo '<p class="erreur">' . h($e) . '</p>';
    }
    echo '<p><a href="javascript:history.back()">Retour au formulaire</a> | <a href="enseignant.php">Nouveau formulaire</a></p>';
} else {
    echo '<p class="ok">' . h($messageSucces) . '</p>';
    echo '<p><a href="enseignant.php">Ajouter un autre</a> | <a href="liste.php">Affichage Liste</a> | <a href="etudiants.php">Liste Des Etudiants</a></p>';
}

require __DIR__ . '/../includes/footer.php';
