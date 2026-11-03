<?php
// voyage/liste.php : affichage de la liste des voyages enregistrés dans la table `voyager`
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$termeRecherche = trim((string)($_GET['q'] ?? $_GET['id'] ?? ''));
$erreurRecherche = false;
$voyages = [];
$voyageUniqueAffiche = null;

$sql = 'SELECT v.*, n.libelle AS nationalite
        FROM voyager v
        LEFT JOIN nationalite n ON n.code = v.nationalite_code';

$params = [];
if ($termeRecherche !== '') {
    $idValide = filter_var($termeRecherche, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);

    if ($idValide !== false) {
        $sql .= ' WHERE v.id = :id OR v.etudiant_id = :id OR v.nom_prenom LIKE :nom';
        $params[':id'] = (int)$termeRecherche;
        $params[':nom'] = '%' . $termeRecherche . '%';
    } else {
        $sql .= ' WHERE v.nom_prenom LIKE :nom';
        $params[':nom'] = '%' . $termeRecherche . '%';
    }
}

$sql .= ' ORDER BY v.id DESC';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $voyages = $stmt->fetchAll();
    if (count($voyages) === 1 && $termeRecherche !== '') {
        $voyageUniqueAffiche = $voyages[0];
    }
} catch (PDOException $e) {
    $erreurRecherche = true;
}

$base  = '../';
$titre = 'Liste des voyages';
require __DIR__ . '/../includes/header.php';
?>

<form method="get" action="liste.php" class="form-recherche" style="margin-bottom: 20px;">
    <label for="search-input">Rechercher un voyage par ID ou Nom :</label>
    <input type="text" name="q" id="search-input" placeholder="ID ou Nom / Prénom..." value="<?= h($termeRecherche) ?>">
    <button type="submit">Rechercher</button>
    <?php if ($termeRecherche !== ''): ?>
        <a href="liste.php" style="margin-left: 10px;">Réinitialiser (voir tout)</a>
    <?php endif; ?>
    <a href="voyage.php" style="margin-left: 15px; font-weight: 600;">+ Nouveau voyage</a>
</form>

<?php if ($voyageUniqueAffiche): ?>
    <!-- IMPRESSION / AFFICHAGE COMPLET LORS D'UNE RECHERCHE PAR ID -->
    <div id="print-fiche-recherche" style="background: white; border: 2px solid var(--primary-light-border); border-radius: var(--radius-lg); padding: 20px; margin: 20px 0; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-light); padding-bottom: 10px; margin-bottom: 14px;">
            <h3 style="margin: 0; color: var(--primary);">Informations complètes de l'étudiant et du voyage (ID <?= h($voyageUniqueAffiche['id']) ?>)</h3>
            <button type="button" onclick="window.print()" style="padding: 6px 12px; font-size: 13px;">🖨️ Imprimer la fiche (Print)</button>
        </div>
        <table class="liste" style="margin-bottom: 12px;">
            <tr><td><strong>ID Voyage</strong></td><td><?= h($voyageUniqueAffiche['id']) ?> (ID Étudiant lié : <?= h($voyageUniqueAffiche['etudiant_id'] ?? '—') ?>)</td></tr>
            <tr><td><strong>Numéro</strong></td><td><?= h($voyageUniqueAffiche['numero']) ?></td></tr>
            <tr><td><strong>Civilité</strong></td><td><?= h($voyageUniqueAffiche['civilite']) ?></td></tr>
            <tr><td><strong>Nom / Prénom</strong></td><td><strong><?= h($voyageUniqueAffiche['nom_prenom']) ?></strong></td></tr>
            <tr><td><strong>Filière</strong></td><td><?= h($voyageUniqueAffiche['filiere']) ?></td></tr>
            <tr><td><strong>Adresse</strong></td><td><?= h($voyageUniqueAffiche['adresse']) ?></td></tr>
            <tr><td><strong>CP / Localité</strong></td><td><?= h($voyageUniqueAffiche['code_postal']) ?> <?= h($voyageUniqueAffiche['localite']) ?></td></tr>
            <tr><td><strong>Pays</strong></td><td><?= h($voyageUniqueAffiche['pays']) ?></td></tr>
            <tr><td><strong>Nationalité</strong></td><td><?= h($voyageUniqueAffiche['nationalite'] ?? '—') ?></td></tr>
            <tr><td><strong>Plateformes</strong></td><td><?= h($voyageUniqueAffiche['plateformes'] ?: '—') ?></td></tr>
            <tr><td><strong>Applications</strong></td><td><?= h($voyageUniqueAffiche['applications'] ?: '—') ?></td></tr>
            <tr><td><strong>Transport habituel</strong></td><td><?= h($voyageUniqueAffiche['transport'] ?: '—') ?></td></tr>
            <tr><td><strong>Sport</strong></td><td><?= h($voyageUniqueAffiche['sport'] ?: '—') ?></td></tr>
            <tr><td><strong>Voiture</strong></td><td><?= h($voyageUniqueAffiche['modele_voiture'] ?: '—') ?></td></tr>
            <tr><td><strong>Groupe sanguin</strong></td><td><?= h($voyageUniqueAffiche['groupe_sanguin'] ?: '—') ?></td></tr>
            <tr><td><strong>Modules</strong></td><td><?= h($voyageUniqueAffiche['modules'] ?: '—') ?></td></tr>
            <tr><td><strong>Activités</strong></td><td><?= h($voyageUniqueAffiche['activites'] ?: '—') ?></td></tr>
            <tr style="background: #fdf4ff;"><td><strong>Travel methodes</strong></td><td><strong style="color: #6b21a8;"><?= h($voyageUniqueAffiche['travel_methodes'] ?: '—') ?></strong></td></tr>
            <tr style="background: #fdf4ff;"><td><strong>Travel with</strong></td><td><strong style="color: #6b21a8;"><?= h($voyageUniqueAffiche['travel_with'] ?: '—') ?></strong></td></tr>
            <tr style="background: #fdf4ff;"><td><strong>Description</strong></td><td><div style="white-space: pre-wrap;"><?= h($voyageUniqueAffiche['description'] ?: '—') ?></div></td></tr>
            <?php if (!empty($voyageUniqueAffiche['photo'])): ?>
                <tr><td><strong>Photo</strong></td><td><img src="../uploads/<?= h($voyageUniqueAffiche['photo']) ?>" alt="Photo" style="max-height: 80px; border-radius: 4px;"></td></tr>
            <?php endif; ?>
        </table>
    </div>
<?php endif; ?>

<?php if ($erreurRecherche): ?>
    <p class="erreur">Une erreur est survenue lors de la recherche dans la table Voyager.</p>
<?php elseif (!$voyages && $termeRecherche !== ''): ?>
    <p>Aucun voyage trouvé pour la recherche « <?= h($termeRecherche) ?> ».</p>
<?php elseif (!$voyages): ?>
    <p>Aucun voyage enregistré pour le moment. <a href="voyage.php">Ajouter un premier voyage</a></p>
<?php else: ?>
    <?php if ($termeRecherche !== ''): ?>
        <p class="ok"><?= count($voyages) ?> voyage(s) trouvé(s) pour « <?= h($termeRecherche) ?> » :</p>
    <?php endif; ?>

    <!-- Seules les colonnes demandées sont visibles : numero, civilite, nom/prenom, filiere, adresse, pays + 3 options uniques. Le reste est masqué. -->
    <table class="liste">
        <thead>
            <tr>
                <th>ID</th>
                <th>N°</th>
                <th>Civ.</th>
                <th>Nom / Prénom</th>
                <th>Filière</th>
                <th>Adresse</th>
                <th>Pays</th>
                <th style="background: #f5f3ff; color: #5b21b6;">Travel methodes</th>
                <th style="background: #f5f3ff; color: #5b21b6;">Travel with</th>
                <th style="background: #f5f3ff; color: #5b21b6;">Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($voyages as $v): ?>
                <tr>
                    <td><strong><?= h($v['id']) ?></strong></td>
                    <td><?= h($v['numero']) ?></td>
                    <td><?= h($v['civilite']) ?></td>
                    <td><strong><?= h($v['nom_prenom']) ?></strong></td>
                    <td><?= h($v['filiere']) ?></td>
                    <td><?= h($v['adresse']) ?></td>
                    <td><?= h($v['pays']) ?></td>
                    <td style="background: #faf5ff;">
                        <?php if ($v['travel_methodes']): ?>
                            <span style="font-weight: 600; color: #6b21a8;"><?= h($v['travel_methodes']) ?></span>
                        <?php else: ?>
                            <span style="color: var(--text-muted);">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="background: #faf5ff;">
                        <?php if ($v['travel_with']): ?>
                            <span style="font-weight: 600; color: #6b21a8;"><?= h($v['travel_with']) ?></span>
                        <?php else: ?>
                            <span style="color: var(--text-muted);">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="background: #faf5ff; max-width: 260px;">
                        <?php if ($v['description']): ?>
                            <span title="<?= h($v['description']) ?>">
                                <?= h(mb_strimwidth($v['description'], 0, 60, '...')) ?>
                            </span>
                        <?php else: ?>
                            <span style="color: var(--text-muted);">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                            <a href="voyage.php?id=<?= h($v['id']) ?>" title="Afficher et modifier">Modifier</a>
                            <a href="details.php?id=<?= h($v['id']) ?>" title="Fiche complète" style="color: var(--primary);">Fiche</a>
                            <form method="post" action="supprimer.php" onsubmit="return confirm('Supprimer définitivement le voyage ID <?= h($v['id']) ?> (<?= h($v['nom_prenom']) ?>) ?');" style="margin: 0;">
                                <input type="hidden" name="id" value="<?= h($v['id']) ?>">
                                <button type="submit" style="background: none; border: none; color: #dc2626; padding: 0; cursor: pointer; text-decoration: underline; font-size: inherit;">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
