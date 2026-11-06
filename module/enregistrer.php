<?php
// module/enregistrer.php : insère ou met à jour un module dans MySQL
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: module.php');
    exit;
}

$action        = $_POST['action'] ?? ($_GET['action'] ?? 'enregistrer');
$idRaw         = trim((string)($_POST['id'] ?? ''));
$codeModule    = strtoupper(trim((string)($_POST['code_module'] ?? '')));
$designation   = trim((string)($_POST['designation'] ?? ''));
$coefficient   = filter_var($_POST['coefficient'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]);
$volumeHoraire = filter_var($_POST['volume_horaire'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
$filiere       = trim((string)($_POST['filiere'] ?? ''));

$erreurs = [];
$idValide = null;

if ($idRaw !== '') {
    $idValide = filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idValide === false) {
        $erreurs[] = "L'identifiant doit être un nombre entier positif.";
    }
}

if ($codeModule === '' || mb_strlen($codeModule) > 20) {
    $erreurs[] = "Le code module est obligatoire (20 caractères maximum).";
}
if ($designation === '' || mb_strlen($designation) > 100) {
    $erreurs[] = "La désignation est obligatoire (100 caractères maximum).";
}
if ($coefficient === false) {
    $erreurs[] = "Le coefficient doit être un entier positif (ex: 1, 2, 3...).";
}
if ($volumeHoraire === false) {
    $erreurs[] = "Le volume horaire doit être un entier positif en heures (ex: 45, 60...).";
}

// Vérification filière
$filieresExistantes = charger_options_colonne($pdo, 'filiere', 'nom', 'id', $FILIERES);
if ($filiere === '' || !in_array($filiere, $filieresExistantes, true)) {
    $erreurs[] = "Veuillez sélectionner une filière valide.";
}

$succes = false;
$messageSucces = '';

if (!$erreurs) {
    try {
        $resultat = enregistrer_avec_id($pdo, 'module', [
            'code_module' => $codeModule,
            'designation' => $designation,
            'coefficient' => $coefficient,
            'volume_horaire' => $volumeHoraire,
            'filiere' => $filiere,
        ], $idValide, $action === 'modifier');

        if ($resultat['status'] === 'missing_id') {
            $erreurs[] = "Veuillez préciser l'identifiant (ID) du module à modifier.";
        } elseif ($resultat['status'] === 'not_found') {
            $erreurs[] = "Aucun module trouvé pour l'ID {$idValide}.";
        } elseif ($resultat['status'] === 'unchanged') {
            $succes = true;
            $messageSucces = "Aucune modification apportée (valeurs identiques).";
        } else {
            $succes = true;
            if ($resultat['status'] === 'updated') {
                $messageSucces = $action === 'modifier'
                    ? "Module ID {$idValide} modifié avec succès."
                    : "Module ID {$idValide} mis à jour avec succès.";
            } else {
                $messageSucces = "Module ajouté avec succès (ID {$resultat['id']}).";
            }
        }
    } catch (PDOException $e) {
        $erreurs[] = "Erreur de base de données : " . $e->getMessage();
    }
}

$base = '../';
$titre = 'Enregistrement module';
require __DIR__ . '/../includes/header.php';

if ($erreurs) {
    foreach ($erreurs as $e) {
        echo '<p class="erreur">' . h($e) . '</p>';
    }
    echo '<p><a href="javascript:history.back()">Retour au formulaire</a> | <a href="module.php">Nouveau module</a></p>';
} else {
    echo '<p class="ok">' . h($messageSucces) . '</p>';
    echo '<p><a href="module.php">Ajouter un autre</a> | <a href="liste.php">Affichage Liste</a></p>';
}

require __DIR__ . '/../includes/footer.php';
