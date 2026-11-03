<?php
// voyage/enregistrer.php : validation et enregistrement d'un voyage dans MySQL (table voyager)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: voyage.php');
    exit;
}

// 1) Récupération des champs principaux
$idFormulaire    = $_POST['id'] ?? '';
$etudiantIdForm  = $_POST['etudiant_id'] ?? '';
$numero          = trim($_POST['numero'] ?? '');
$civilite        = $_POST['civilite'] ?? '';
$nomPrenom       = trim($_POST['nom_prenom'] ?? '');
$filiere         = trim($_POST['filiere'] ?? '');
$adresse         = trim($_POST['adresse'] ?? '');
$pays            = $_POST['pays'] ?? '';

// Les 3 options uniques de voyage
$travelMethodes  = post_liste('travel_methodes', $TRAVEL_METHODES);
$travelWith      = trim($_POST['travel_with'] ?? '');
$description     = trim($_POST['description'] ?? '');

// Champs étudiants complémentaires (masqués par défaut)
$codePostal      = trim($_POST['code_postal'] ?? '');
$localite        = trim($_POST['localite'] ?? '');
$natCode         = trim($_POST['nationalite'] ?? '');
$transport       = trim($_POST['transport'] ?? '');
$sport           = trim($_POST['sport'] ?? '');
$groupeSanguin   = trim($_POST['groupe_sanguin'] ?? '');
$photoActuelle   = trim($_POST['photo_actuelle'] ?? '');

$plateformes     = post_liste('plateformes', $PLATEFORMES);
$applications    = post_liste('applications', $APPLICATIONS);
$activites       = post_liste('activites', $ACTIVITES);
$modules         = post_liste('modules', $MODULES);

$stmt = $pdo->prepare('SELECT libelle FROM moyen_transport');
$stmt->execute();
$transports = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT libelle FROM sport');
$stmt->execute();
$sports = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT libelle FROM modele_voiture');
$stmt->execute();
$modelesVoiture = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: $MODELES_VOITURE;
$modelesVoituresPost = post_liste('modele_voiture', $modelesVoiture);

$idVoyage        = null;
$etudiantId      = null;
$voyageExistant  = null;
$succes          = false;
$erreurs         = [];

// Vérification de l'identifiant voyage
if ($idFormulaire !== '') {
    if ((!is_string($idFormulaire) && !is_int($idFormulaire)) || filter_var((string)$idFormulaire, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]) === false) {
        $erreurs[] = "Identifiant de voyage invalide.";
    } else {
        $idVoyage = (int)$idFormulaire;
        $stmt = $pdo->prepare('SELECT id, photo FROM voyager WHERE id = :id');
        $stmt->execute([':id' => $idVoyage]);
        $voyageExistant = $stmt->fetch() ?: null;
    }
}

// Vérification de l'identifiant étudiant associé
if ($etudiantIdForm !== '') {
    if (filter_var((string)$etudiantIdForm, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]) !== false) {
        $etudiantId = (int)$etudiantIdForm;
    }
}

// 2) Validation des champs
if (!preg_match('/^[0-9]{1,10}$/', $numero)) {
    $erreurs[] = "Numéro invalide (chiffres uniquement).";
}
if (!in_array($civilite, $CIVILITES, true)) {
    $erreurs[] = "Civilité invalide.";
}
if ($nomPrenom === '' || mb_strlen($nomPrenom) > 80) {
    $erreurs[] = "Nom / Prénom obligatoire (80 caractères max).";
}
if ($adresse === '' || mb_strlen($adresse) > 150) {
    $erreurs[] = "Adresse obligatoire (150 caractères max).";
}
if (!in_array($pays, $PAYS, true)) {
    $erreurs[] = "Pays invalide.";
}
if ($codePostal !== '' && !preg_match('/^[0-9]{4,10}$/', $codePostal)) {
    $erreurs[] = "Code postal invalide (4 à 10 chiffres).";
}
if ($localite !== '' && mb_strlen($localite) > 60) {
    $erreurs[] = "Localité trop longue (60 caractères max).";
}
if ($transport !== '' && !in_array($transport, $transports, true)) {
    $erreurs[] = "Moyen de transport habituel invalide.";
}
if ($sport !== '' && !in_array($sport, $sports, true)) {
    $erreurs[] = "Sport invalide.";
}
if ($groupeSanguin !== '' && !in_array($groupeSanguin, $GROUPES_SANGUINS, true)) {
    $erreurs[] = "Groupe sanguin invalide.";
}
if ($natCode !== '') {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM nationalite WHERE code = :code');
    $stmt->execute([':code' => $natCode]);
    if ($stmt->fetchColumn() == 0) {
        $erreurs[] = "Nationalité invalide.";
    }
} else {
    $natCode = null;
}

// Validation de Travel With (optionnel ou parmi les valeurs autorisées)
if ($travelWith !== '' && !in_array($travelWith, $TRAVEL_WITH, true)) {
    $erreurs[] = "Option d'accompagnement (Travel with) invalide.";
}

// 3) Gestion de la photo (si envoyée)
$photo = $voyageExistant['photo'] ?? ($photoActuelle !== '' ? $photoActuelle : null);
if (!$erreurs && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    [$nouvellePhoto, $erreurImage] = enregistrer_image($_FILES['photo']);
    if ($erreurImage) {
        $erreurs[] = $erreurImage;
    } elseif ($nouvellePhoto !== null) {
        $photo = $nouvellePhoto;
    }
}

// 4) Enregistrement dans la table `voyager`
if (!$erreurs) {
    try {
        $valeurs = [
            ':numero'          => $numero,
            ':civilite'        => $civilite,
            ':nom'             => $nomPrenom,
            ':filiere'         => $filiere,
            ':adresse'         => $adresse,
            ':cp'              => $codePostal,
            ':localite'        => $localite,
            ':pays'            => $pays,
            ':plateformes'     => implode(', ', $plateformes),
            ':applications'    => implode(', ', $applications),
            ':transport'       => $transport,
            ':sport'           => $sport,
            ':activites'       => implode(', ', $activites),
            ':modules'         => implode(', ', $modules),
            ':groupe_sanguin'  => $groupeSanguin,
            ':modele_voiture'  => implode(', ', $modelesVoituresPost),
            ':nat'             => $natCode,
            ':photo'           => $photo,
            ':travel_methodes' => implode(', ', $travelMethodes),
            ':travel_with'     => $travelWith,
            ':description'     => $description !== '' ? $description : null,
            ':etudiant_id'     => $etudiantId,
        ];

        if ($idVoyage !== null && $voyageExistant) {
            $sql = 'UPDATE voyager
                    SET etudiant_id = :etudiant_id, numero = :numero, civilite = :civilite, nom_prenom = :nom, filiere = :filiere,
                        adresse = :adresse, code_postal = :cp, localite = :localite, pays = :pays,
                        plateformes = :plateformes, applications = :applications,
                        transport = :transport, sport = :sport, activites = :activites,
                        modules = :modules, groupe_sanguin = :groupe_sanguin, modele_voiture = :modele_voiture,
                        nationalite_code = :nat, photo = :photo,
                        travel_methodes = :travel_methodes, travel_with = :travel_with, description = :description
                    WHERE id = :id';
            $valeurs[':id'] = $idVoyage;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($valeurs);
        } else {
            $colonnes = $idVoyage === null ? '' : 'id, ';
            $paramId  = $idVoyage === null ? '' : ':id, ';
            $sql = 'INSERT INTO voyager
                    (' . $colonnes . 'etudiant_id, numero, civilite, nom_prenom, filiere, adresse, code_postal, localite, pays,
                     plateformes, applications, transport, sport, activites, modules, groupe_sanguin, modele_voiture, nationalite_code, photo,
                     travel_methodes, travel_with, description)
                    VALUES
                    (' . $paramId . ':etudiant_id, :numero, :civilite, :nom, :filiere, :adresse, :cp, :localite, :pays,
                     :plateformes, :applications, :transport, :sport, :activites, :modules, :groupe_sanguin, :modele_voiture, :nat, :photo,
                     :travel_methodes, :travel_with, :description)';
            if ($idVoyage !== null) {
                $valeurs[':id'] = $idVoyage;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($valeurs);
            if ($idVoyage === null) {
                $idVoyage = (int)$pdo->lastInsertId();
            }
        }
        $succes = true;
    } catch (PDOException $e) {
        $erreurs[] = "Échec de l'enregistrement dans la table Voyager : " . $e->getMessage();
    }
}

$base  = '../';
$titre = 'Enregistrement voyage';
require __DIR__ . '/../includes/header.php';

if ($erreurs) {
    foreach ($erreurs as $e) echo '<p class="erreur">' . h($e) . '</p>';
    echo '<p><a href="javascript:history.back()">Retour au formulaire</a> | <a href="voyage.php">Nouvelle recherche</a></p>';
} elseif ($succes && $voyageExistant) {
    echo '<p class="ok">Les informations du voyage ID ' . h($idVoyage) . ' ont été mises à jour avec succès.</p>';
    echo '<p><a href="voyage.php?id=' . h($idVoyage) . '">Voir la fiche complète</a> | <a href="liste.php">Affichage Liste</a></p>';
} elseif ($succes && $idVoyage !== null) {
    echo '<p class="ok">Le voyage a été enregistré avec succès (ID ' . h($idVoyage) . ').</p>';
    echo '<p><a href="voyage.php?id=' . h($idVoyage) . '">Voir la fiche complète</a> | <a href="liste.php">Affichage Liste</a></p>';
} else {
    echo '<p class="ok">Voyage enregistré avec succès pour : ' . h($nomPrenom) . '</p>';
    echo '<p><a href="voyage.php">Nouveau voyage</a> | <a href="liste.php">Affichage Liste</a></p>';
}
require __DIR__ . '/../includes/footer.php';
