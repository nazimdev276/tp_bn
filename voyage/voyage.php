<?php
// voyage/voyage.php : formulaire principal de gestion des voyages (table Voyager)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

// Chargement des listes de référence depuis la base de données
$stmt = $pdo->prepare('SELECT code, libelle FROM nationalite ORDER BY libelle');
$stmt->execute();
$nationalites = $stmt->fetchAll();

$transports = charger_options_colonne($pdo, 'moyen_transport', 'libelle', 'libelle');
$sports = charger_options_colonne($pdo, 'sport', 'libelle', 'libelle');
$modelesVoiture = charger_options_colonne($pdo, 'modele_voiture', 'libelle', 'libelle', $MODELES_VOITURE);
$filieres = charger_options_colonne($pdo, 'filiere', 'nom', 'id', $FILIERES);

// Recherche par ID : recherche l'étudiant ou le voyage existant
$idRecherche = $_GET['id'] ?? '';
$erreurRecherche = null;
$etudiant = null;
$voyage = null;
$idRechercheValide = null;
$isVoyageExistant = false;

if ($idRecherche !== '') {
    if (!is_string($idRecherche) && !is_int($idRecherche)) {
        $erreurRecherche = "Identifiant invalide.";
        $idRecherche = '';
    } else {
        $idRecherche = trim((string)$idRecherche);
        $idRechercheValide = filter_var($idRecherche, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 2147483647],
        ]);
        if ($idRechercheValide === false) {
            $erreurRecherche = "Identifiant invalide.";
            $idRechercheValide = null;
        } else {
            // 1. Rechercher d'abord dans la table voyager
            $stmt = $pdo->prepare('SELECT v.*, n.libelle AS nationalite_libelle 
                                   FROM voyager v 
                                   LEFT JOIN nationalite n ON n.code = v.nationalite_code 
                                   WHERE v.id = :id OR v.etudiant_id = :id');
            $stmt->execute([':id' => $idRechercheValide]);
            $voyage = $stmt->fetch() ?: null;

            // 2. Rechercher aussi dans la table personne (étudiant)
            $stmt = $pdo->prepare('SELECT p.*, n.libelle AS nationalite_libelle 
                                   FROM personne p 
                                   LEFT JOIN nationalite n ON n.code = p.nationalite_code 
                                   WHERE p.id = :id');
            $stmt->execute([':id' => $idRechercheValide]);
            $etudiant = $stmt->fetch() ?: null;

            if ($voyage) {
                $isVoyageExistant = true;
            }
        }
    }
}

// Déterminer la source des données : voyage existant en priorité, sinon données de l'étudiant
$donnees = $voyage ?: $etudiant ?: [];
$voyageId = $voyage['id'] ?? ($idRechercheValide ?: null);
$etudiantId = $voyage['etudiant_id'] ?? ($etudiant['id'] ?? ($idRechercheValide ?: null));

if ($voyage && !empty($voyage['etudiant_id']) && !$etudiant) {
    $stmt = $pdo->prepare('SELECT p.*, n.libelle AS nationalite_libelle 
                           FROM personne p 
                           LEFT JOIN nationalite n ON n.code = p.nationalite_code 
                           WHERE p.id = :id');
    $stmt->execute([':id' => $voyage['etudiant_id']]);
    $etudiant = $stmt->fetch() ?: null;
}

if (empty($donnees['photo']) && !empty($etudiant['photo'])) {
    $donnees['photo'] = $etudiant['photo'];
}

// Les 3 fonctionnalités Voyage
$methodesSelectionnees = lire_liste_texte($voyage['travel_methodes'] ?? null);
$travelWithSelectionne = $voyage['travel_with'] ?? '';
$descriptionValue = $voyage['description'] ?? '';

// Les champs étudiants existants
$plateformesSelectionnees = lire_liste_texte($donnees['plateformes'] ?? null);
$applicationsSelectionnees = lire_liste_texte($donnees['applications'] ?? null);
$activitesSelectionnees = lire_liste_texte($donnees['activites'] ?? null);
$modulesSelectionnes = lire_liste_texte($donnees['modules'] ?? null);
$modelesVoitureSelectionnes = lire_liste_texte($donnees['modele_voiture'] ?? null);

$base  = '../';
$titre = 'Formulaire Voyage';
require __DIR__ . '/../includes/header.php';
?>

<!-- 1. Formulaire de recherche par ID -->
<form method="get" action="voyage.php" class="form-recherche" style="margin-bottom: 20px;">
    <label for="recherche-id"><strong>Rechercher un étudiant / voyage par ID :</strong></label>
    <input type="text" name="id" id="recherche-id" inputmode="numeric" pattern="[1-9][0-9]*"
           value="<?= h($idRecherche) ?>" placeholder="Ex: 1" required style="max-width: 150px;">
    <button type="submit">Rechercher</button>
    <?php if ($idRecherche !== ''): ?>
        <a href="voyage.php" style="margin-left: 10px;">Réinitialiser</a>
    <?php endif; ?>
</form>

<?php if ($erreurRecherche): ?>
    <p class="erreur"><?= h($erreurRecherche) ?></p>
<?php elseif ($idRechercheValide !== null && ($voyage || $etudiant)): ?>
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin: 12px 0;">
        <p class="ok" style="margin: 0;">
            <?php if ($isVoyageExistant): ?>
                Voyage trouvé pour l’ID <?= h($idRechercheValide) ?> (Étudiant : <?= h($donnees['nom_prenom'] ?? '') ?>).
            <?php else: ?>
                Étudiant trouvé dans la base (ID <?= h($idRechercheValide) ?> : <?= h($donnees['nom_prenom'] ?? '') ?>). Renseignez les informations de voyage puis enregistrez.
            <?php endif; ?>
        </p>
        <div style="display: flex; gap: 8px;">
            <button type="button" onclick="window.print()" style="background: #eef2ff; color: var(--primary); border-color: var(--primary-light-border);">🖨️ Imprimer la fiche (Print)</button>
            <?php if ($isVoyageExistant): ?>
                <form method="post" action="supprimer.php" onsubmit="return confirm('Supprimer définitivement ce voyage (ID <?= h($voyageId) ?>) ?');" style="margin: 0;">
                    <input type="hidden" name="id" value="<?= h($voyageId) ?>">
                    <button type="submit" style="background: #fef2f2; color: #dc2626; border-color: #fecaca; margin: 0;">Supprimer ce voyage</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- AFFICHAGE COMPLET / PRINT DES INFORMATIONS DE L'ÉTUDIANT + 3 FONCTIONNALITÉS VOYAGE -->
    <div id="fiche-etudiant-print" style="background: #ffffff; border: 2px solid var(--primary-light-border); border-radius: var(--radius-lg); padding: 22px; margin: 20px 0; box-shadow: var(--shadow-md);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-light); padding-bottom: 12px; margin-bottom: 16px;">
            <div>
                <h3 style="margin: 0; color: var(--primary); font-size: 18px;">Fiche Récapitulative Complète : Étudiant & Voyage</h3>
                <span style="font-size: 13px; color: var(--text-muted);">Recherche effectuée pour l'identifiant <strong>#<?= h($idRechercheValide) ?></strong></span>
            </div>
            <?php if (!empty($donnees['photo'])): ?>
                <div>
                    <img src="<?= $base ?>uploads/<?= h($donnees['photo']) ?>" alt="Photo de l'étudiant" style="width: 90px; height: 90px; object-fit: cover; border-radius: var(--radius-md); border: 1px solid var(--border-medium); display: block;">
                </div>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
            <!-- Bloc Données Étudiant -->
            <div style="background: var(--bg-subtle); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                <h4 style="margin: 0 0 10px 0; color: var(--text-heading); font-size: 15px; border-bottom: 1px solid var(--border-medium); padding-bottom: 4px;">Informations de l'Étudiant</h4>
                <table style="width: 100%; font-size: 13.5px; line-height: 1.8;">
                    <tr><td style="color: var(--text-muted); width: 140px;">Identifiant (ID) :</td><td><strong><?= h($idRechercheValide) ?></strong></td></tr>
                    <tr><td style="color: var(--text-muted);">Numéro :</td><td><?= h($donnees['numero'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Civilité :</td><td><?= h($donnees['civilite'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Nom / Prénom :</td><td><strong><?= h($donnees['nom_prenom'] ?? '—') ?></strong></td></tr>
                    <tr><td style="color: var(--text-muted);">Filière :</td><td><?= h($donnees['filiere'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Adresse :</td><td><?= h($donnees['adresse'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">No postal / Localité :</td><td><?= h(($donnees['code_postal'] ?? '') . ' ' . ($donnees['localite'] ?? '')) ?: '—' ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Pays :</td><td><?= h($donnees['pays'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Nationalité :</td><td><?= h($donnees['nationalite_libelle'] ?? ($donnees['nationalite_code'] ?? '—')) ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Groupe sanguin :</td><td><?= h($donnees['groupe_sanguin'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Transport habituel :</td><td><?= h($donnees['transport'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Sport :</td><td><?= h($donnees['sport'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Modèle voiture :</td><td><?= h($donnees['modele_voiture'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Plateforme(s) :</td><td><?= h($donnees['plateformes'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Application(s) :</td><td><?= h($donnees['applications'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Module(s) :</td><td><?= h($donnees['modules'] ?? '—') ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Activité(s) :</td><td><?= h($donnees['activites'] ?? '—') ?></td></tr>
                    <tr>
                        <td style="color: var(--text-muted); vertical-align: top;">Photo :</td>
                        <td>
                            <?php if (!empty($donnees['photo'])): ?>
                                <img src="<?= $base ?>uploads/<?= h($donnees['photo']) ?>" alt="Photo de l'étudiant" style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-medium); display: block;">
                            <?php else: ?>
                                <em style="color: var(--text-muted);">Aucune photo</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Bloc 3 Fonctionnalités Voyage -->
            <div style="background: #f5f3ff; padding: 16px; border-radius: var(--radius-md); border: 1px solid #ddd6fe;">
                <h4 style="margin: 0 0 10px 0; color: #5b21b6; font-size: 15px; border-bottom: 1px solid #c4b5fd; padding-bottom: 4px;">Informations Voyage (3 Nouvelles Options)</h4>
                <table style="width: 100%; font-size: 13.5px; line-height: 1.8;">
                    <tr>
                        <td style="color: var(--text-muted); width: 150px; vertical-align: top;">Travel methodes :</td>
                        <td>
                            <?php if ($methodesSelectionnees): ?>
                                <span style="display: inline-block; background: #ede9fe; color: #5b21b6; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                    <?= h(implode(', ', $methodesSelectionnees)) ?>
                                </span>
                            <?php else: ?>
                                <em style="color: var(--text-muted);">Non renseigné (Train, Car, Flight)</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="color: var(--text-muted); vertical-align: top;">Travel with :</td>
                        <td>
                            <?php if ($travelWithSelectionne): ?>
                                <span style="display: inline-block; background: #ede9fe; color: #5b21b6; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                    <?= h($travelWithSelectionne) ?>
                                </span>
                            <?php else: ?>
                                <em style="color: var(--text-muted);">Non renseigné (Alone, With someone, With family)</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="color: var(--text-muted); vertical-align: top;">Description :</td>
                        <td>
                            <?php if ($descriptionValue !== ''): ?>
                                <div style="background: white; border: 1px solid #c4b5fd; border-radius: var(--radius-sm); padding: 8px 10px; margin-top: 4px; white-space: pre-wrap; font-size: 13px;">
                                    <?= h($descriptionValue) ?>
                                </div>
                            <?php else: ?>
                                <em style="color: var(--text-muted);">Aucune description renseignée.</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($idRechercheValide !== null): ?>
    <p>Aucun étudiant ni voyage trouvé pour l’ID <?= h($idRechercheValide) ?>. Remplissez les champs ci-dessous pour créer ce voyage avec cet ID.</p>
<?php endif; ?>

<!-- 2. Formulaire Voyage -->
<form id="form-voyage" method="post" action="enregistrer.php" enctype="multipart/form-data">
    <?php if ($idRechercheValide !== null): ?>
        <input type="hidden" name="id" value="<?= h($idRechercheValide) ?>">
        <input type="hidden" name="etudiant_id" value="<?= h($etudiantId) ?>">
    <?php elseif ($voyageId !== null): ?>
        <input type="hidden" name="id" value="<?= h($voyageId) ?>">
    <?php endif; ?>

    <p style="margin: 12px 0; color: var(--text-muted); font-size: 13.5px;">
        Champs visibles du formulaire : <strong>Numéro, Civilité, Nom / Prénom, Filière, Adresse, Pays</strong> et les <strong>3 options de voyage</strong>. Les autres informations étudiantes sont conservées en arrière-plan.
    </p>

    <div id="cadre-photo">
        <?php if (!empty($donnees['photo'])): ?>
            <img id="apercu" src="<?= $base ?>uploads/<?= h($donnees['photo']) ?>" alt="Aperçu de la photo" style="display: block;">
        <?php else: ?>
            <img id="apercu" alt="Aperçu de la photo">
        <?php endif; ?>
    </div>

    <table class="formulaire">
        <!-- 1. Numéro -->
        <tr>
            <td>Numéro :</td>
            <td><input type="text" name="numero" id="numero" size="6" maxlength="10" placeholder="15" value="<?= h($donnees['numero'] ?? '') ?>" required></td>
        </tr>

        <!-- 2. Civilité -->
        <tr>
            <td>Civilité :</td>
            <td>
                <?php foreach ($CIVILITES as $i => $c): ?>
                    <label><input type="radio" name="civilite" value="<?= h($c) ?>" <?= isset($donnees['civilite']) ? ($donnees['civilite'] === $c ? 'checked' : '') : ($i === 0 ? 'checked' : '') ?>> <?= h($c) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>

        <!-- 3. Nom / Prénom -->
        <tr>
            <td>Nom / Prénom :</td>
            <td><input type="text" name="nom_prenom" id="nom_prenom" size="40" maxlength="80" placeholder="Benali Farid" value="<?= h($donnees['nom_prenom'] ?? '') ?>" required></td>
        </tr>

        <!-- 4. Filière -->
        <tr>
            <td>Filière :</td>
            <td>
                <select name="filiere" id="filiere">
                    <option value="">— Sélectionner une filière —</option>
                    <?php foreach ($filieres as $f): ?>
                        <option value="<?= h($f) ?>" <?= ($donnees['filiere'] ?? '') === $f ? 'selected' : '' ?>><?= h($f) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <!-- 5. Adresse -->
        <tr>
            <td>Adresse :</td>
            <td><input type="text" name="adresse" id="adresse" size="40" maxlength="150" placeholder="Rue des Roses - SBA" value="<?= h($donnees['adresse'] ?? '') ?>" required></td>
        </tr>

        <!-- 6. Pays -->
        <tr>
            <td>Pays :</td>
            <td>
                <select name="pays" id="pays">
                    <?php foreach ($PAYS as $p): ?>
                        <option value="<?= h($p) ?>" <?= ($donnees['pays'] ?? '') === $p ? 'selected' : '' ?>><?= h($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <!-- ============================================================ -->
        <!-- 3 NOUVELLES OPTIONS DE VOYAGE (UNIQUE ONES)                  -->
        <!-- ============================================================ -->

        <!-- 7. Travel methodes (Train, Car, Flight) -> check box -->
        <tr style="background: #fdf4ff;">
            <td><strong>Méthodes de voyage :</strong><br><small style="color: var(--text-muted);">(Travel methods)</small></td>
            <td>
                <?php foreach ($TRAVEL_METHODES as $tm): ?>
                    <label style="margin-right: 18px; font-weight: 500;">
                        <input type="checkbox" name="travel_methodes[]" value="<?= h($tm) ?>" <?= in_array($tm, $methodesSelectionnees, true) ? 'checked' : '' ?>>
                        <?= h($tm) ?>
                    </label>
                <?php endforeach; ?>
            </td>
        </tr>

        <!-- 8. Travel with (someone / family / alone) -> Radio button -->
        <tr style="background: #fdf4ff;">
            <td><strong>Accompagnement :</strong><br><small style="color: var(--text-muted);">(Travel with)</small></td>
            <td>
                <?php foreach ($TRAVEL_WITH as $i => $tw): ?>
                    <label style="margin-right: 18px; font-weight: 500;">
                        <input type="radio" name="travel_with" value="<?= h($tw) ?>" <?= ($travelWithSelectionne === $tw) ? 'checked' : ($travelWithSelectionne === '' && $i === 0 ? 'checked' : '') ?>>
                        <?= h($tw) ?>
                    </label>
                <?php endforeach; ?>
            </td>
        </tr>

        <!-- 9. Description (Big text area for comments) -->
        <tr style="background: #fdf4ff;">
            <td><strong>Description :</strong><br><small style="color: var(--text-muted);">(Commentaires)</small></td>
            <td>
                <textarea name="description" id="description" rows="5" cols="60" style="width: 100%; max-width: 600px; padding: 10px; border-radius: var(--radius-md); border: 1px solid var(--border-medium); font-family: inherit; font-size: 13.5px;" placeholder="Commentaires ou description du voyage..."><?= h($descriptionValue) ?></textarea>
            </td>
        </tr>

        <!-- ============================================================ -->
        <!-- RESTE DES CHAMPS ÉTUDIANTS (MASQUÉS PAR DÉFAUT)              -->
        <!-- ============================================================ -->
        <tbody id="champs-caches-etudiant" style="display: none; border-top: 2px dashed var(--border-medium);">
            <tr>
                <td colspan="2" style="background: var(--bg-alt); padding: 8px 12px; font-weight: 600; color: var(--text-heading);">
                    Informations étudiantes complémentaires (masquées par défaut) :
                </td>
            </tr>
            <tr>
                <td>No postal / Localité :</td>
                <td>
                    <input type="text" name="code_postal" id="code_postal" size="6" maxlength="10" placeholder="22000" value="<?= h($donnees['code_postal'] ?? '') ?>">
                    <input type="text" name="localite" id="localite" size="25" maxlength="60" placeholder="Sidi Bel Abbès" value="<?= h($donnees['localite'] ?? '') ?>">
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
                <td>Moyen de transport habituel :</td>
                <td>
                    <select name="transport" id="transport">
                        <option value="">— Aucun —</option>
                        <?php foreach ($transports as $tr): ?>
                            <option value="<?= h($tr) ?>" <?= ($donnees['transport'] ?? '') === $tr ? 'selected' : '' ?>><?= h($tr) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Sport :</td>
                <td>
                    <select name="sport" id="sport">
                        <option value="">— Aucun —</option>
                        <?php foreach ($sports as $sp): ?>
                            <option value="<?= h($sp) ?>" <?= ($donnees['sport'] ?? '') === $sp ? 'selected' : '' ?>><?= h($sp) ?></option>
                        <?php endforeach; ?>
                    </select>
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
                            <option value="<?= h($gs) ?>" <?= ($donnees['groupe_sanguin'] ?? '') === $gs ? 'selected' : '' ?>><?= h($gs) ?></option>
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
                    <?php foreach ($ACTIVITES as $act): ?>
                        <label><input type="checkbox" name="activites[]" value="<?= h($act) ?>" <?= in_array($act, $activitesSelectionnees, true) ? 'checked' : '' ?>> <?= h($act) ?></label><br>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <td>Nationalité :</td>
                <td>
                    <select name="nationalite" id="nationalite">
                        <option value="">— Non renseigné —</option>
                        <?php foreach ($nationalites as $n): ?>
                            <option value="<?= h($n['code']) ?>" <?= ($donnees['nationalite_code'] ?? '') === $n['code'] ? 'selected' : '' ?>><?= h($n['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Photo :</td>
                <td>
                    <?php if (!empty($donnees['photo'])): ?>
                        <div style="margin-bottom: 8px;">
                            <img src="<?= $base ?>uploads/<?= h($donnees['photo']) ?>" alt="Photo actuelle" style="max-width: 100px; max-height: 100px; border-radius: 6px; border: 1px solid var(--border-medium); display: block;">
                        </div>
                        <input type="hidden" name="photo_actuelle" value="<?= h($donnees['photo']) ?>">
                        <span style="font-size: 12.5px; color: var(--text-muted); display: block; margin-bottom: 6px;">Photo actuelle : <?= h($donnees['photo']) ?></span>
                    <?php endif; ?>
                    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif">
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Bouton pour basculer l'affichage des informations complémentaires masquées -->
    <div style="margin: 12px 0 20px 0;">
        <button type="button" id="btn-toggle-etudiant" style="background: var(--bg-alt); border: 1px solid var(--border-medium); color: var(--text-heading); font-size: 13px; padding: 7px 14px; border-radius: var(--radius-md); cursor: pointer;">
            👁️ Afficher / Masquer les autres détails étudiants masqués
        </button>
    </div>

    <!-- Boutons d'action -->
    <button type="submit" formaction="afficher_php.php" formnovalidate>Affichage PHP</button>
    <button type="button" id="btn-affichage-js-voyage">Affichage JavaScript</button>
    <button type="submit">Enregistrer</button>
    <button type="reset">Réinitialiser</button>
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
    <button type="button" onclick="window.print()">Imprimer (Print)</button>
</form>

<!-- Zone de résultat pour Affichage JavaScript -->
<div id="resultat_js_voyage" style="display: none; margin-top: 24px; padding: 20px; background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-lg);"></div>

<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>