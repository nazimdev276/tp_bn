<?php
// personne/formulaire.php : formulaire principal
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

// Les nationalités sont lues dans MySQL (rien n'est écrit "en dur")
$stmt = $pdo->prepare('SELECT code, libelle FROM nationalite ORDER BY libelle');
$stmt->execute();
$nationalites = $stmt->fetchAll();

$transports = charger_options_colonne($pdo, 'moyen_transport', 'libelle', 'libelle');
$sports = charger_options_colonne($pdo, 'sport', 'libelle', 'libelle');
$modelesVoiture = charger_options_colonne($pdo, 'modele_voiture', 'libelle', 'libelle', $MODELES_VOITURE);
$filieres = charger_options_colonne($pdo, 'filiere', 'nom', 'id', $FILIERES);

$modeRecherche = ($_GET['mode'] ?? 'id') === 'numero' ? 'numero' : 'id';
$idRecherche = $_GET['id'] ?? '';
$erreurRecherche = null;
$personne = null;
$idRechercheValide = null;
if ($idRecherche !== '') {
    if (!is_string($idRecherche) && !is_int($idRecherche)) {
        $erreurRecherche = "Identifiant invalide.";
        $idRecherche = '';
    } else {
        $idRecherche = trim((string)$idRecherche);
        if ($modeRecherche === 'numero') {
            $stmt = $pdo->prepare('SELECT * FROM personne WHERE numero = :numero ORDER BY id LIMIT 1');
            $stmt->execute([':numero' => $idRecherche]);
            $personne = $stmt->fetch() ?: null;
        } else {
            $idRechercheValide = filter_var($idRecherche, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 2147483647],
            ]);
            if ($idRechercheValide === false) {
                $erreurRecherche = "Identifiant invalide.";
                $idRechercheValide = null;
            } else {
                $stmt = $pdo->prepare('SELECT * FROM personne WHERE id = :id');
                $stmt->execute([':id' => $idRechercheValide]);
                $personne = $stmt->fetch() ?: null;
            }
        }
    }
}

$plateformesSelectionnees = lire_liste_texte($personne['plateformes'] ?? null);
$applicationsSelectionnees = lire_liste_texte($personne['applications'] ?? null);
$activitesSelectionnees = lire_liste_texte($personne['activites'] ?? null);
$modulesSelectionnes = lire_liste_texte($personne['modules'] ?? null);
$modelesVoitureSelectionnes = lire_liste_texte($personne['modele_voiture'] ?? null);

$base  = '../';
$titre = 'Formulaire Personne';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" action="formulaire.php">
    <label for="mode-recherche">Rechercher par :</label>
    <select name="mode" id="mode-recherche">
        <option value="id" <?= $modeRecherche === 'id' ? 'selected' : '' ?>>ID</option>
        <option value="numero" <?= $modeRecherche === 'numero' ? 'selected' : '' ?>>Numéro</option>
    </select>
    <input type="text" name="id" id="recherche-id" inputmode="<?= $modeRecherche === 'id' ? 'numeric' : 'text' ?>"
           value="<?= h($idRecherche) ?>" required>
    <button type="submit">Rechercher</button>
</form>
<?php if ($erreurRecherche): ?>
    <p class="erreur"><?= h($erreurRecherche) ?></p>
<?php elseif ($personne): ?>
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin: 12px 0;">
        <p class="ok" style="margin: 0;">Personne trouvée (ID <?= h($personne['id']) ?>) : modifiez les champs puis enregistrez.</p>
        <form method="post" action="supprimer.php" onsubmit="return confirm('Supprimer définitivement cette personne (ID <?= h($personne['id']) ?>) ?');" style="margin: 0;">
            <input type="hidden" name="id" value="<?= h($personne['id']) ?>">
            <button type="submit" style="background: #fef2f2; color: #dc2626; border-color: #fecaca; margin: 0;">Supprimer cette personne</button>
        </form>
    </div>
<?php elseif ($idRechercheValide !== null): ?>
    <p>Aucune personne trouvée pour l’ID <?= h($idRechercheValide) ?>. Remplissez les champs pour la créer avec cet ID.</p>
<?php elseif ($modeRecherche === 'numero' && $idRecherche !== '' && !$erreurRecherche): ?>
    <p>Aucune personne trouvée pour le numéro <?= h($idRecherche) ?>. Remplissez les champs pour en créer une.</p>
<?php endif; ?>

<!-- enctype="multipart/form-data" est obligatoire pour envoyer une image -->
<form id="form-personne" method="post" action="enregistrer.php" enctype="multipart/form-data">
    <?php if ($personne): ?>
        <input type="hidden" name="id" value="<?= h($personne['id']) ?>">
    <?php elseif ($idRechercheValide !== null): ?>
        <input type="hidden" name="id" value="<?= h($idRechercheValide) ?>">
    <?php endif; ?>

    <div id="cadre-photo">
        <img id="apercu" alt="Aperçu de la photo">
    </div>

    <table class="formulaire">
        <tr>
            <td>Numéro :</td>
            <td><input type="text" name="numero" id="numero" size="6" maxlength="10" placeholder="15" value="<?= h($personne['numero'] ?? '') ?>"></td>
        </tr>
        <tr>
            <td>Civilité :</td>
            <td>
                <?php foreach ($CIVILITES as $i => $c): ?>
                    <label><input type="radio" name="civilite" value="<?= h($c) ?>" <?= isset($personne['civilite']) ? ($personne['civilite'] === $c ? 'checked' : '') : ($i === 0 ? 'checked' : '') ?>> <?= h($c) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>Nom / Prénom :</td>
            <td><input type="text" name="nom_prenom" id="nom_prenom" size="40" maxlength="80" placeholder="Benali Farid" value="<?= h($personne['nom_prenom'] ?? '') ?>"></td>
        </tr>
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
        <tr>
            <td>Adresse :</td>
            <td><input type="text" name="adresse" id="adresse" size="40" maxlength="150" placeholder="Rue des Roses - SBA" value="<?= h($personne['adresse'] ?? '') ?>"></td>
        </tr>
        <tr>
            <td>No postal / Localité :</td>
            <td>
                <input type="text" name="code_postal" id="code_postal" size="6" maxlength="10" placeholder="22000" value="<?= h($personne['code_postal'] ?? '') ?>">
                <input type="text" name="localite" id="localite" size="25" maxlength="60" placeholder="Sidi Bel Abbès" value="<?= h($personne['localite'] ?? '') ?>">
            </td>
        </tr>
        <tr>
            <td>Pays :</td>
            <td>
                <select name="pays" id="pays">
                    <?php foreach ($PAYS as $p): ?>
                        <option value="<?= h($p) ?>" <?= ($personne['pays'] ?? '') === $p ? 'selected' : '' ?>><?= h($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Plateforme(s) :</td>
            <td>
                <?php foreach ($PLATEFORMES as $p): ?>
                    <label><input type="checkbox" name="plateformes[]" value="<?= h($p) ?>" <?= in_array($p, $plateformesSelectionnees, true) ? 'checked' : '' ?>> <?= h($p) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>Application(s) :</td>
            <td>
                <?php foreach ($APPLICATIONS as $a): ?>
                    <label><input type="checkbox" name="applications[]" value="<?= h($a) ?>" <?= in_array($a, $applicationsSelectionnees, true) ? 'checked' : '' ?>> <?= h($a) ?></label><br>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>Moyen de transport :</td>
            <td>
                <select name="transport" id="transport">
                    <?php foreach ($transports as $transport): ?>
                        <option value="<?= h($transport) ?>" <?= ($personne['transport'] ?? '') === $transport ? 'selected' : '' ?>><?= h($transport) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$transports): ?>
                    <span class="erreur">Aucun moyen de transport. <a href="../transport/transport.php">En ajouter un</a></span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Sport :</td>
            <td>
                <select name="sport" id="sport">
                    <option value="">— Aucun —</option>
                    <?php foreach ($sports as $sport): ?>
                        <option value="<?= h($sport) ?>" <?= ($personne['sport'] ?? '') === $sport ? 'selected' : '' ?>><?= h($sport) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$sports): ?>
                    <span class="erreur">Aucun sport enregistré. <a href="../sport/sport.php">En ajouter un</a></span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Modèle(s) de voiture :</td>
            <td>
                <?php foreach ($modelesVoiture as $mv): ?>
                    <label><input type="checkbox" name="modele_voiture[]" value="<?= h($mv) ?>" <?= in_array($mv, $modelesVoitureSelectionnes, true) ? 'checked' : '' ?>> <?= h($mv) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>Groupe sanguin :</td>
            <td>
                <select name="groupe_sanguin" id="groupe_sanguin">
                    <option value="">— Aucun / Inconnu —</option>
                    <?php foreach ($GROUPES_SANGUINS as $gs): ?>
                        <option value="<?= h($gs) ?>" <?= ($personne['groupe_sanguin'] ?? '') === $gs ? 'selected' : '' ?>><?= h($gs) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Module(s) :</td>
            <td>
                <?php foreach ($MODULES as $m): ?>
                    <label><input type="checkbox" name="modules[]" value="<?= h($m) ?>" <?= in_array($m, $modulesSelectionnes, true) ? 'checked' : '' ?>> <?= h($m) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>Activité(s) :</td>
            <td>
                <?php foreach ($ACTIVITES as $activite): ?>
                    <label><input type="checkbox" name="activites[]" value="<?= h($activite) ?>" <?= in_array($activite, $activitesSelectionnees, true) ? 'checked' : '' ?>> <?= h($activite) ?></label><br>
                <?php endforeach; ?>
            </td>
        </tr>
        <!-- NATIONALITE : placée APRES Application(s), options générées depuis MySQL -->
        <tr>
            <td>Nationalité :</td>
            <td>
                <?php if (!$nationalites): ?>
                    <span class="erreur">Aucune nationalité. <a href="../nationalite/nationalite.php">En ajouter une</a></span>
                <?php else: ?>
                    <select name="nationalite" id="nationalite">
                        <?php foreach ($nationalites as $n): ?>
                            <option value="<?= h($n['code']) ?>" <?= ($personne['nationalite_code'] ?? '') === $n['code'] ? 'selected' : '' ?>><?= h($n['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Photo :</td>
            <td><input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif"></td>
        </tr>
    </table>

    <!-- Chaque bouton peut avoir son propre formaction (page de destination) -->
    <button type="submit" formaction="afficher_php.php" formnovalidate>Affichage PHP</button>
    <button type="button" id="btn-affichage-js">Affichage JavaScript</button>
    <button type="submit">Enregistrer</button>
    <button type="reset">Réinitialiser</button>
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
    <button type="submit" formaction="upload_image.php" formnovalidate>Insérer Image</button>
    <button type="submit" formaction="../bulletin/bulletin.php" formmethod="get" formnovalidate>Bulletin de Notes</button>
</form>

<!-- Zone remplie par JavaScript (bouton Affichage JavaScript) -->
<div id="resultat_js"></div>

<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
