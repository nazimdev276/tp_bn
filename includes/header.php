<?php
// includes/header.php
// Avant l'inclusion, la page doit définir : $base (chemin vers la racine) et $titre

$titresEtudiants = [
    'Formulaire Personne', 'Formulaire Étudiant', 'Liste des personnes',
    'Modifier une personne', 'Enregistrement personne', 'Insérer Image',
    'Affichage PHP', 'Suppression d’une personne'
];

$titresEnseignants = [
    'Gestion des enseignants', 'Formulaire Enseignant', 'Liste des enseignants',
    'Modifier un enseignant', 'Enregistrement enseignant', 'Suppression d’un enseignant',
    'Liste Des Etudiants', 'Liste des étudiants par enseignant'
];

$titresModules = [
    'Gestion des modules', 'Formulaire Module', 'Liste des modules',
    'Modifier un module', 'Enregistrement module', 'Suppression d’un module'
];

$titresVoyages = [
    'Formulaire Voyage', 'Voyage', 'Gestion des voyages', 'Liste des voyages',
    'Modifier un voyage', 'Enregistrement voyage', 'Affichage PHP Voyage', 'Suppression d’un voyage',
    'Détails du voyage'
];

$titresBulletin = [
    'Bulletin de notes', 'Bulletin de Notes', 'Formulaire Bulletin de Notes',
    'Liste des notes', 'Enregistrement note', 'Suppression d’une note',
    'Modifier une note'
];

$titresTables = [
    'Sous-menu Tables',
    'Gestion des nationalités', 'Liste des nationalités', 'Enregistrement nationalité',
    'Gestion des sports', 'Liste des sports', 'Enregistrement du sport',
    'Gestion des filières', 'Formulaire Filière', 'Liste des filières', 'Enregistrement filière', 'Suppression d’une filière',
    'Gestion des moyens de transport', 'Liste des moyens de transport', 'Enregistrement transport',
    'Gestion des modèles de voiture', 'Liste des modèles de voiture', 'Enregistrement modèle de voiture'
];

$isAccueilActive     = ($titre === 'Accueil');
$isEtudiantsActive   = in_array($titre, $titresEtudiants, true);
$isEnseignantsActive = in_array($titre, $titresEnseignants, true);
$isModulesActive     = in_array($titre, $titresModules, true);
$isVoyagesActive     = in_array($titre, $titresVoyages, true);
$isBulletinActive    = in_array($titre, $titresBulletin, true);
$isTablesActive      = in_array($titre, $titresTables, true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TP01 - <?= h($titre) ?></title>
    <link rel="stylesheet" href="<?= $base ?>css/style.css">
</head>
<body>
<div id="page">
    <div class="header-brand">
        <h1>TP01 – PAW</h1>
        <span class="brand-badge">ISIL &bull; Université Djillali Liabes</span>
    </div>
    <div id="menu">
        <a href="<?= $base ?>index.php" class="<?= $isAccueilActive ? 'active' : '' ?>">Accueil</a>
        <a href="<?= $base ?>personne/formulaire.php" class="<?= $isEtudiantsActive ? 'active' : '' ?>">Étudiants</a>
        <a href="<?= $base ?>enseignant/enseignant.php" class="<?= $isEnseignantsActive ? 'active' : '' ?>">Enseignants</a>
        <a href="<?= $base ?>module/module.php" class="<?= $isModulesActive ? 'active' : '' ?>">Modules</a>
        <a href="<?= $base ?>voyage/voyage.php" class="<?= $isVoyagesActive ? 'active' : '' ?>">Voyage</a>
        <a href="<?= $base ?>bulletin/bulletin.php" class="<?= $isBulletinActive ? 'active' : '' ?>">Bulletin de Notes</a>
        <div class="nav-dropdown <?= $isTablesActive ? 'active' : '' ?>">
            <a href="<?= $base ?>tables.php" class="nav-dropdown-btn <?= $isTablesActive ? 'active' : '' ?>" id="btn-tables-menu" aria-haspopup="true" aria-expanded="false">Tables ▾</a>
            <div class="nav-dropdown-content" id="submenu-tables">
                <a href="<?= $base ?>nationalite/nationalite.php" class="<?= in_array($titre, ['Gestion des nationalités', 'Liste des nationalités', 'Enregistrement nationalité'], true) ? 'active' : '' ?>">Nationalité</a>
                <a href="<?= $base ?>sport/sport.php" class="<?= in_array($titre, ['Gestion des sports', 'Liste des sports', 'Enregistrement du sport'], true) ? 'active' : '' ?>">Sports</a>
                <a href="<?= $base ?>filiere/filiere.php" class="<?= in_array($titre, ['Gestion des filières', 'Formulaire Filière', 'Liste des filières', 'Enregistrement filière', 'Suppression d’une filière'], true) ? 'active' : '' ?>">Filières</a>
            </div>
        </div>
    </div>
    <hr>
    <h2><?= h($titre) ?></h2>
