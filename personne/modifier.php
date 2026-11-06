<?php
// personne/modifier.php : modifie les informations d'une personne existante
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

$id = $_GET['id'] ?? '';
if (!is_string($id) && !is_int($id)) {
    $id = '';
}
$id = trim((string)$id);
$idValide = filter_var($id, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 2147483647],
]);

$personne = null;
$erreurs = [];
$succes = false;

if ($idValide === false) {
    $erreurs[] = "Identifiant invalide.";
} else {
    $stmt = $pdo->prepare('SELECT * FROM personne WHERE id = :id');
    $stmt->execute([':id' => $idValide]);
    $personne = $stmt->fetch();
    if (!$personne) {
        $erreurs[] = "Aucune personne trouvée pour cet identifiant.";
    }
}

$stmt = $pdo->prepare('SELECT code, libelle FROM nationalite ORDER BY libelle');
$stmt->execute();
$nationalites = $stmt->fetchAll();

$transports = charger_options_colonne($pdo, 'moyen_transport', 'libelle', 'libelle');
$sports = charger_options_colonne($pdo, 'sport', 'libelle', 'libelle');
$modelesVoiture = charger_options_colonne($pdo, 'modele_voiture', 'libelle', 'libelle', $MODELES_VOITURE);
$filieres = charger_options_colonne($pdo, 'filiere', 'nom', 'id', $FILIERES);

if ($personne && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $champPost = static function (string $nom): string {
        $valeur = $_POST[$nom] ?? '';
        return is_string($valeur) ? trim($valeur) : '';
    };

    $numero        = $champPost('numero');
    $civilite      = $champPost('civilite');
    $nomPrenom     = $champPost('nom_prenom');
    $filiere       = $champPost('filiere');
    $adresse       = $champPost('adresse');
    $codePostal    = $champPost('code_postal');
    $localite      = $champPost('localite');
    $pays          = $champPost('pays');
    $transport     = $champPost('transport');
    $sport         = $champPost('sport');
    $groupeSanguin = $champPost('groupe_sanguin');
    $natCode       = $champPost('nationalite');
    $plateformes   = post_liste('plateformes', $PLATEFORMES);
    $applications  = post_liste('applications', $APPLICATIONS);
    $activites     = post_liste('activites', $ACTIVITES);
    $modules       = post_liste('modules', $MODULES);
    $modeleVoiture = post_liste('modele_voiture', $modelesVoiture);

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
    if (!preg_match('/^[0-9]{4,10}$/', $codePostal)) {
        $erreurs[] = "Code postal invalide.";
    }
    if ($localite === '' || mb_strlen($localite) > 60) {
        $erreurs[] = "Localité obligatoire (60 caractères max).";
    }
    if (!in_array($pays, $PAYS, true)) {
        $erreurs[] = "Pays invalide.";
    }
    if (!in_array($transport, $transports, true)) {
        $erreurs[] = "Moyen de transport invalide.";
    }
    if ($sport !== '' && !in_array($sport, $sports, true)) {
        $erreurs[] = "Sport invalide.";
    }
    if ($groupeSanguin !== '' && !in_array($groupeSanguin, $GROUPES_SANGUINS, true)) {
        $erreurs[] = "Groupe sanguin invalide.";
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM nationalite WHERE code = :code');
    $stmt->execute([':code' => $natCode]);
    if ($stmt->fetchColumn() == 0) {
        $erreurs[] = "Nationalité invalide.";
    }

    $photo = $personne['photo'];
    if (!$erreurs) {
        [$nouvellePhoto, $erreurImage] = enregistrer_image($_FILES['photo'] ?? []);
        if ($erreurImage) {
            $erreurs[] = $erreurImage;
        } elseif ($nouvellePhoto !== null) {
            $photo = $nouvellePhoto;
        }
    }

    if (!$erreurs) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE personne
                 SET numero = :numero, civilite = :civilite, nom_prenom = :nom, filiere = :filiere,
                     adresse = :adresse, code_postal = :cp, localite = :localite,
                     pays = :pays, plateformes = :plateformes, applications = :applications,
                     transport = :transport, sport = :sport, activites = :activites,
                     modules = :modules, groupe_sanguin = :groupe_sanguin, modele_voiture = :modele_voiture,
                     nationalite_code = :nat, photo = :photo
                 WHERE id = :id'
            );
            $stmt->execute([
                ':numero'         => $numero,
                ':civilite'       => $civilite,
                ':nom'            => $nomPrenom,
                ':filiere'        => $filiere,
                ':adresse'        => $adresse,
                ':cp'             => $codePostal,
                ':localite'       => $localite,
                ':pays'           => $pays,
                ':plateformes'    => ecrire_liste_texte($plateformes),
                ':applications'   => ecrire_liste_texte($applications),
                ':transport'      => $transport,
                ':sport'          => $sport,
                ':activites'      => ecrire_liste_texte($activites),
                ':modules'        => ecrire_liste_texte($modules),
                ':groupe_sanguin' => $groupeSanguin,
                ':modele_voiture' => $modeleVoiture,
                ':nat'            => $natCode,
                ':photo'          => $photo,
                ':id'             => $idValide,
            ]);
            $succes = true;
            $personne = [
                'id'               => $idValide,
                'numero'           => $numero,
                'civilite'         => $civilite,
                'nom_prenom'       => $nomPrenom,
                'filiere'          => $filiere,
                'adresse'          => $adresse,
                'code_postal'      => $codePostal,
                'localite'         => $localite,
                'pays'             => $pays,
                'plateformes'      => ecrire_liste_texte($plateformes),
                'applications'     => ecrire_liste_texte($applications),
                'transport'        => $transport,
                'sport'            => $sport,
                'activites'        => ecrire_liste_texte($activites),
                'modules'          => ecrire_liste_texte($modules),
                'groupe_sanguin'   => $groupeSanguin,
                'modele_voiture'   => $modeleVoiture,
                'nationalite_code' => $natCode,
                'photo'            => $photo,
            ];
        } catch (PDOException $e) {
            $erreurs[] = "Échec de l'enregistrement des modifications.";
        }
    }

    if (!$succes) {
        $personne['numero']         = $numero;
        $personne['civilite']       = $civilite;
        $personne['nom_prenom']     = $nomPrenom;
        $personne['filiere']        = $filiere;
        $personne['adresse']        = $adresse;
        $personne['code_postal']    = $codePostal;
        $personne['localite']       = $localite;
        $personne['pays']           = $pays;
        $personne['plateformes']    = ecrire_liste_texte($plateformes);
        $personne['applications']   = ecrire_liste_texte($applications);
        $personne['transport']      = $transport;
        $personne['sport']          = $sport;
        $personne['activites']      = ecrire_liste_texte($activites);
        $personne['modules']        = ecrire_liste_texte($modules);
        $personne['groupe_sanguin'] = $groupeSanguin;
        $personne['modele_voiture'] = $modeleVoiture;
        $personne['nationalite_code'] = $natCode;
    }
}

$plateformesSelectionnees = lire_liste_texte($personne['plateformes'] ?? null);
$applicationsSelectionnees = lire_liste_texte($personne['applications'] ?? null);
$activitesSelectionnees = lire_liste_texte($personne['activites'] ?? null);
$modulesSelectionnes = lire_liste_texte($personne['modules'] ?? null);

$base = '../';
$titre = 'Modifier une personne';
require __DIR__ . '/../includes/header.php';
?>
<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php endforeach; ?>
<?php if ($succes): ?>
    <p class="ok">Les modifications de la personne ID <?= h($idValide) ?> ont été enregistrées.</p>
<?php endif; ?>
<?php if ($personne): ?>
    <form method="post" action="modifier.php?id=<?= h($idValide) ?>" enctype="multipart/form-data">
        <table class="formulaire">
            <tr><td>ID :</td><td><?= h($personne['id']) ?></td></tr>
            <tr><td>Numéro :</td><td><input type="text" name="numero" maxlength="10" value="<?= h($personne['numero']) ?>" required></td></tr>
            <tr>
                <td>Civilité :</td>
                <td><?php foreach ($CIVILITES as $civiliteOption): ?>
                    <label><input type="radio" name="civilite" value="<?= h($civiliteOption) ?>" <?= $personne['civilite'] === $civiliteOption ? 'checked' : '' ?>> <?= h($civiliteOption) ?></label>
                <?php endforeach; ?></td>
            </tr>
            <tr><td>Nom / Prénom :</td><td><input type="text" name="nom_prenom" maxlength="80" value="<?= h($personne['nom_prenom']) ?>" required></td></tr>
            <tr>
                <td>Filière :</td>
                <td>
                    <select name="filiere" id="filiere">
                        <option value="">— Sélectionner une filière —</option>
                        <?php foreach ($filieres as $f): ?>
                            <option value="<?= h($f) ?>" <?= ($personne['filiere'] ?? '') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr><td>Adresse :</td><td><input type="text" name="adresse" maxlength="150" value="<?= h($personne['adresse']) ?>" required></td></tr>
            <tr>
                <td>No postal / Localité :</td>
                <td>
                    <input type="text" name="code_postal" maxlength="10" value="<?= h($personne['code_postal']) ?>" required>
                    <input type="text" name="localite" maxlength="60" value="<?= h($personne['localite']) ?>" required>
                </td>
            </tr>
            <tr>
                <td>Pays :</td>
                <td><select name="pays"><?php foreach ($PAYS as $paysOption): ?>
                    <option value="<?= h($paysOption) ?>" <?= $personne['pays'] === $paysOption ? 'selected' : '' ?>><?= h($paysOption) ?></option>
                <?php endforeach; ?></select></td>
            </tr>
            <tr>
                <td>Plateforme(s) :</td>
                <td><?php foreach ($PLATEFORMES as $plateforme): ?>
                    <label><input type="checkbox" name="plateformes[]" value="<?= h($plateforme) ?>" <?= in_array($plateforme, $plateformesSelectionnees, true) ? 'checked' : '' ?>> <?= h($plateforme) ?></label>
                <?php endforeach; ?></td>
            </tr>
            <tr>
                <td>Application(s) :</td>
                <td><?php foreach ($APPLICATIONS as $application): ?>
                    <label><input type="checkbox" name="applications[]" value="<?= h($application) ?>" <?= in_array($application, $applicationsSelectionnees, true) ? 'checked' : '' ?>> <?= h($application) ?></label><br>
                <?php endforeach; ?></td>
            </tr>
            <tr>
                <td>Moyen de transport :</td>
                <td><select name="transport"><?php foreach ($transports as $transportOption): ?>
                    <option value="<?= h($transportOption) ?>" <?= $personne['transport'] === $transportOption ? 'selected' : '' ?>><?= h($transportOption) ?></option>
                <?php endforeach; ?></select></td>
            </tr>
            <tr>
                <td>Sport :</td>
                <td><select name="sport">
                    <option value="">— Aucun —</option>
                    <?php foreach ($sports as $sportOption): ?>
                        <option value="<?= h($sportOption) ?>" <?= ($personne['sport'] ?? '') === $sportOption ? 'selected' : '' ?>><?= h($sportOption) ?></option>
                    <?php endforeach; ?>
                </select></td>
            </tr>
            <tr>
                <td>Modèle de voiture :</td>
                <td><select name="modele_voiture">
                    <option value="">— Aucun —</option>
                    <?php foreach ($modelesVoiture as $modeleOption): ?>
                        <option value="<?= h($modeleOption) ?>" <?= ($personne['modele_voiture'] ?? '') === $modeleOption ? 'selected' : '' ?>><?= h($modeleOption) ?></option>
                    <?php endforeach; ?>
                </select></td>
            </tr>
            <tr>
                <td>Groupe sanguin :</td>
                <td><select name="groupe_sanguin">
                    <option value="">— Aucun / Inconnu —</option>
                    <?php foreach ($GROUPES_SANGUINS as $gsOption): ?>
                        <option value="<?= h($gsOption) ?>" <?= ($personne['groupe_sanguin'] ?? '') === $gsOption ? 'selected' : '' ?>><?= h($gsOption) ?></option>
                    <?php endforeach; ?>
                </select></td>
            </tr>
            <tr>
                <td>Module(s) :</td>
                <td><?php foreach ($MODULES as $module): ?>
                    <label><input type="checkbox" name="modules[]" value="<?= h($module) ?>" <?= in_array($module, $modulesSelectionnes, true) ? 'checked' : '' ?>> <?= h($module) ?></label>
                <?php endforeach; ?></td>
            </tr>
            <tr>
                <td>Activité(s) :</td>
                <td><?php foreach ($ACTIVITES as $activite): ?>
                    <label><input type="checkbox" name="activites[]" value="<?= h($activite) ?>" <?= in_array($activite, $activitesSelectionnees, true) ? 'checked' : '' ?>> <?= h($activite) ?></label><br>
                <?php endforeach; ?></td>
            </tr>
            <tr>
                <td>Nationalité :</td>
                <td><select name="nationalite"><?php foreach ($nationalites as $nationalite): ?>
                    <option value="<?= h($nationalite['code']) ?>" <?= $personne['nationalite_code'] === $nationalite['code'] ? 'selected' : '' ?>><?= h($nationalite['libelle']) ?></option>
                <?php endforeach; ?></select></td>
            </tr>
            <tr>
                <td>Photo actuelle :</td>
                <td>
                    <?php if ($personne['photo']): ?>
                        <img src="../uploads/<?= h($personne['photo']) ?>" width="80" alt="Photo actuelle"><br>
                    <?php else: ?>
                        Aucune photo
                    <?php endif; ?>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif">
                    <span class="petit">Laissez vide pour conserver la photo actuelle.</span>
                </td>
            </tr>
        </table>
        <button type="submit">Enregistrer les modifications</button>
        <button type="reset">Réinitialiser</button>
    </form>
<?php endif; ?>
<p><a href="liste.php">Retour à la liste</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
