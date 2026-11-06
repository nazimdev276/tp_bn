<?php
// enseignant/enseignant.php : formulaire de gestion des enseignants
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

// Chargement des filières depuis la base de données
$stmtFilieres = $pdo->query('SELECT nom FROM filiere ORDER BY nom ASC');
$filieres = $stmtFilieres ? $stmtFilieres->fetchAll(PDO::FETCH_COLUMN) : [];
if (!$filieres) {
    $filieres = $FILIERES;
}

// Chargement des modules depuis la base de données
$stmtModules = $pdo->query('SELECT id, code_module, designation, coefficient, filiere FROM module ORDER BY filiere ASC, code_module ASC');
$tousLesModules = $stmtModules ? $stmtModules->fetchAll() : [];

$idRecherche = trim((string)($_GET['id'] ?? ''));
$enseignant = null;
$messageRecherche = null;

if ($idRecherche !== '') {
    $idValide = filter_var($idRecherche, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idValide !== false) {
        $stmt = $pdo->prepare('SELECT * FROM enseignant WHERE id = :id');
        $stmt->execute([':id' => $idValide]);
        $enseignant = $stmt->fetch() ?: null;
        if ($enseignant) {
            $messageRecherche = "Enseignant trouvé (ID {$idValide}) : vous pouvez le modifier ou le supprimer.";
        } else {
            $messageRecherche = "Aucun enseignant trouvé pour l'ID {$idValide}. Remplissez le formulaire pour le créer avec cet ID.";
        }
    }
}

$idCourant            = $enseignant['id'] ?? $idRecherche;
$numeroCourant        = $enseignant['numero'] ?? '';
$civiliteCourante     = $enseignant['civilite'] ?? 'Monsieur';
$nomPrenomCourant     = $enseignant['nom_prenom'] ?? '';
$adresseCourante      = $enseignant['adresse'] ?? '';
$dateNaissanceCourant = $enseignant['date_naissance'] ?? '';
$lieuNaissanceCourant = $enseignant['lieu_naissance'] ?? '';
$paysCourant          = $enseignant['pays'] ?? 'Algérie';
$gradeCourant         = $enseignant['grade'] ?? 'MAA';
$specialiteCourante   = $enseignant['specialite'] ?? '';
$filiereCourante      = $enseignant['filiere'] ?? '';
$modulesCourants      = !empty($enseignant['modules'])
    ? lire_liste_texte($enseignant['modules'])
    : [];

$base = '../';
$titre = 'Gestion des enseignants';
require __DIR__ . '/../includes/header.php';
?>

<form method="get" action="enseignant.php" class="form-recherche">
    <label for="recherche-id">Rechercher par ID :</label>
    <input type="number" name="id" id="recherche-id" min="1" value="<?= h($idRecherche) ?>" placeholder="ex: 1">
    <button type="submit">Rechercher</button>
    <?php if ($idRecherche !== ''): ?>
        <a href="enseignant.php" style="margin-left: 10px;">Réinitialiser</a>
    <?php endif; ?>
</form>

<?php if ($messageRecherche): ?>
    <p class="<?= $enseignant ? 'ok' : '' ?>">
        <?= h($messageRecherche) ?>
        <?php if ($enseignant): ?>
            <a href="etudiants.php?id=<?= h($enseignant['id']) ?>" class="btn-action" style="margin-left: 12px; font-size: 12px; background:#e0f2fe; color:#0369a1; border-color:#bae6fd; padding:4px 10px; border-radius:6px; text-decoration:none;">Liste Des Etudiants →</a>
        <?php endif; ?>
    </p>
<?php endif; ?>

<form id="form-enseignant" method="post" action="enregistrer.php">
    <table class="formulaire">
        <tr>
            <td>ID :</td>
            <td>
                <input type="number" name="id" id="enseignant-id" min="1" size="10" placeholder="Auto ou spécifier" value="<?= h($idCourant) ?>">
                <span class="petit" style="color: var(--text-muted); font-size: 12px; margin-left: 8px;">(Laisser vide pour auto-incrémentation lors de l'ajout)</span>
            </td>
        </tr>
        <tr>
            <td>Numéro :</td>
            <td>
                <input type="text" name="numero" id="numero" maxlength="10" size="10" placeholder="ex: 102" value="<?= h($numeroCourant) ?>" required>
            </td>
        </tr>
        <tr>
            <td>Civilité :</td>
            <td>
                <?php foreach ($CIVILITES as $c): ?>
                    <label>
                        <input type="radio" name="civilite" value="<?= h($c) ?>" <?= $civiliteCourante === $c ? 'checked' : '' ?>>
                        <?= h($c) ?>
                    </label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>Nom / Prénom :</td>
            <td>
                <input type="text" name="nom_prenom" id="nom_prenom" maxlength="80" size="40" placeholder="ex: Mansouri Karim" value="<?= h($nomPrenomCourant) ?>" required>
            </td>
        </tr>
        <tr>
            <td>Filière :</td>
            <td>
                <select name="filiere" id="filiere" required>
                    <option value="">— Sélectionner une filière —</option>
                    <?php foreach ($filieres as $f): ?>
                        <option value="<?= h($f) ?>" <?= $filiereCourante === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="petit" style="color: var(--text-muted); font-size: 12px; margin-left: 8px;">(Filière d'enseignement)</span>
            </td>
        </tr>
        <tr>
            <td>Module(s) :</td>
            <td>
                <!-- Dropdown à choix multiples connecté aux modules de la BD -->
                <div class="multiselect-dropdown" id="modules-dropdown">
                    <div class="multiselect-trigger" id="modules-trigger" tabindex="0" role="combobox" aria-expanded="false">
                        <div class="multiselect-tags" id="modules-tags"></div>
                        <span class="multiselect-placeholder" id="modules-placeholder">Sélectionner un ou plusieurs modules...</span>
                        <span class="multiselect-arrow">▾</span>
                    </div>
                    <div class="multiselect-menu" id="modules-menu">
                        <div class="multiselect-search-box">
                            <input type="text" id="modules-filter" placeholder="Rechercher un module (code, nom, filière)..." autocomplete="off">
                        </div>
                        <div class="multiselect-actions">
                            <button type="button" class="btn-text-sm" id="btn-select-all-modules">Tout cocher</button>
                            <button type="button" class="btn-text-sm" id="btn-clear-modules">Tout décocher</button>
                            <button type="button" class="btn-text-sm" id="btn-filter-filiere" style="display:none;">Modules de la filière</button>
                        </div>
                        <div class="multiselect-list" id="modules-list">
                            <?php if (empty($tousLesModules)): ?>
                                <div class="multiselect-empty" style="padding:10px; color:var(--text-muted);">
                                    Aucun module dans la table module. <a href="../module/module.php">En ajouter un</a>.
                                </div>
                            <?php else: ?>
                                <?php foreach ($tousLesModules as $mod): ?>
                                    <?php $isCoche = in_array($mod['code_module'], $modulesCourants, true); ?>
                                    <label class="multiselect-item" data-code="<?= h($mod['code_module']) ?>" data-filiere="<?= h($mod['filiere']) ?>" data-desig="<?= h($mod['designation']) ?>">
                                        <input type="checkbox" name="modules[]" value="<?= h($mod['code_module']) ?>" <?= $isCoche ? 'checked' : '' ?>>
                                        <span class="mod-badge"><?= h($mod['code_module']) ?></span>
                                        <span class="mod-name"><?= h($mod['designation']) ?></span>
                                        <span class="mod-coeff" style="font-size:11px; background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:1px 6px; border-radius:4px; font-weight:600;">Coeff: <?= h($mod['coefficient']) ?></span>
                                        <span class="mod-filiere"><?= h($mod['filiere']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <noscript>
                    <p class="petit" style="color: var(--text-muted); margin-top: 4px;">Cochez un ou plusieurs modules ci-dessus.</p>
                </noscript>
            </td>
        </tr>
        <tr>
            <td>Adresse :</td>
            <td>
                <input type="text" name="adresse" id="adresse" maxlength="150" size="40" placeholder="ex: Cité 500 logts, Sidi Bel Abbès" value="<?= h($adresseCourante) ?>" required>
            </td>
        </tr>
        <tr>
            <td>Date de naissance :</td>
            <td>
                <input type="date" name="date_naissance" id="date_naissance" value="<?= h($dateNaissanceCourant) ?>" required>
            </td>
        </tr>
        <tr>
            <td>Lieu de naissance :</td>
            <td>
                <input type="text" name="lieu_naissance" id="lieu_naissance" maxlength="60" size="30" placeholder="ex: Sidi Bel Abbès" value="<?= h($lieuNaissanceCourant) ?>" required>
            </td>
        </tr>
        <tr>
            <td>Pays :</td>
            <td>
                <select name="pays" id="pays">
                    <?php foreach ($PAYS as $p): ?>
                        <option value="<?= h($p) ?>" <?= $paysCourant === $p ? 'selected' : '' ?>><?= h($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Grade :</td>
            <td>
                <select name="grade" id="grade">
                    <?php foreach ($GRADES as $g): ?>
                        <option value="<?= h($g) ?>" <?= $gradeCourant === $g ? 'selected' : '' ?>><?= h($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Spécialité :</td>
            <td>
                <input type="text" name="specialite" id="specialite" maxlength="80" size="30" placeholder="ex: Informatique / Réseaux" value="<?= h($specialiteCourante) ?>" required>
            </td>
        </tr>
    </table>

    <button type="submit" name="action" value="ajouter">Ajouter</button>
    <button type="submit" name="action" value="modifier" formaction="enregistrer.php?action=modifier">Modifier</button>
    <button type="submit" name="action" value="supprimer" formaction="supprimer.php" formnovalidate onclick="return confirm('Supprimer cet enseignant ?');">Supprimer</button>
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
    <button type="submit" formaction="etudiants.php" formmethod="get" formnovalidate class="btn-etudiants" id="btn-liste-etudiants">Liste Des Etudiants</button>
    <button type="reset">Réinitialiser</button>
</form>

<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
