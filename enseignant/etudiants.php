<?php
// enseignant/etudiants.php : affiche la liste des étudiants suivant les cours d'un enseignant
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

// Récupération des paramètres
$idParam        = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['enseignant_id']) ? (int)$_GET['enseignant_id'] : 0);
$filiereParam   = trim((string)($_GET['filiere'] ?? ''));
$moduleFilter   = trim((string)($_GET['module'] ?? ''));

// Chargement de la liste de tous les enseignants pour le sélecteur
$stmtEnseignants = $pdo->query('SELECT id, numero, nom_prenom, grade, specialite, filiere, modules FROM enseignant ORDER BY nom_prenom ASC');
$tousEnseignants = $stmtEnseignants ? $stmtEnseignants->fetchAll() : [];

$enseignant = null;
if ($idParam > 0) {
    foreach ($tousEnseignants as $e) {
        if ((int)$e['id'] === $idParam) {
            $enseignant = $e;
            break;
        }
    }
}

// Si aucun enseignant sélectionné et qu'il en existe, on prend le premier
if (!$enseignant && !empty($tousEnseignants)) {
    $enseignant = $tousEnseignants[0];
    $idParam = (int)$enseignant['id'];
}

// Filière et modules de l'enseignant sélectionné
$filiere = $filiereParam !== '' ? $filiereParam : ($enseignant['filiere'] ?? '');
$modulesList = !empty($enseignant['modules'])
    ? lire_liste_texte($enseignant['modules'])
    : [];

// Recherche des informations complètes des modules de cet enseignant (avec coefficient direct depuis la table module)
$activeModules = [];
if (!empty($modulesList)) {
    $placeholders = implode(',', array_fill(0, count($modulesList), '?'));
    if (!empty($filiere)) {
        // Prioritiser la filière de l'enseignant si plusieurs modules ont le même code
        $stmtMods = $pdo->prepare("
            SELECT id, code_module, designation, coefficient, filiere
            FROM module
            WHERE (code_module IN ($placeholders) OR designation IN ($placeholders))
              AND (filiere = ? OR filiere = '' OR ? = '')
            ORDER BY (filiere = ?) DESC, code_module ASC
        ");
        $params = array_merge($modulesList, $modulesList, [$filiere, $filiere, $filiere]);
        $stmtMods->execute($params);
        $activeModules = $stmtMods->fetchAll();
    }
    if (empty($activeModules)) {
        $stmtMods = $pdo->prepare("SELECT id, code_module, designation, coefficient, filiere FROM module WHERE code_module IN ($placeholders) OR designation IN ($placeholders) ORDER BY code_module ASC");
        $stmtMods->execute(array_merge($modulesList, $modulesList));
        $activeModules = $stmtMods->fetchAll();
    }
}

// Si aucun module n'a été spécifié pour l'enseignant, charger les modules de sa filière
if (empty($activeModules) && !empty($filiere)) {
    $stmtMods = $pdo->prepare("SELECT id, code_module, designation, coefficient, filiere FROM module WHERE filiere = :filiere ORDER BY code_module ASC");
    $stmtMods->execute([':filiere' => $filiere]);
    $activeModules = $stmtMods->fetchAll();
}

// Filtrage par module spécifique si demandé
$displayedModules = $activeModules;
if ($moduleFilter !== '' && $moduleFilter !== 'all') {
    $displayedModules = array_filter($activeModules, function ($m) use ($moduleFilter) {
        return strcasecmp($m['code_module'], $moduleFilter) === 0 || (string)$m['id'] === $moduleFilter;
    });
}

// Récupération des étudiants de la classe
$etudiants = [];
$moduleIds = array_column($activeModules, 'id');

if (!empty($filiere)) {
    if (!empty($moduleIds)) {
        $inIds = implode(',', array_map('intval', $moduleIds));
        $sql = "SELECT DISTINCT p.id, p.numero, p.civilite, p.nom_prenom, p.filiere, p.photo
                FROM personne p
                LEFT JOIN note n ON n.etudiant_id = p.id
                WHERE p.filiere = :filiere OR (n.module_id IN ($inIds) AND :filiere2 != '')
                ORDER BY p.nom_prenom ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':filiere'  => $filiere,
            ':filiere2' => $filiere,
        ]);
    } else {
        $stmt = $pdo->prepare("SELECT id, numero, civilite, nom_prenom, filiere, photo FROM personne WHERE filiere = :filiere ORDER BY nom_prenom ASC");
        $stmt->execute([':filiere' => $filiere]);
    }
    $etudiants = $stmt->fetchAll();
}

// Récupération des notes des étudiants pour ces modules
$notesMap = []; // clé : "etudiantId_moduleId" => note
if (!empty($etudiants) && !empty($moduleIds)) {
    $eids = implode(',', array_map('intval', array_column($etudiants, 'id')));
    $mids = implode(',', array_map('intval', $moduleIds));
    $stmtNotes = $pdo->query("SELECT etudiant_id, module_id, note FROM note WHERE etudiant_id IN ($eids) AND module_id IN ($mids)");
    while ($row = $stmtNotes->fetch()) {
        $notesMap[$row['etudiant_id'] . '_' . $row['module_id']] = (float)$row['note'];
    }
}

// Construction des lignes du tableau (combinaison étudiant x module enseigné)
$lignesTableau = [];
$sommeNotes = 0;
$sommeNotesPonderees = 0;
$totalCoefficients = 0;
$nbNotes = 0;

foreach ($etudiants as $etd) {
    if (empty($displayedModules)) {
        // Cas sans modules configurés : afficher au moins l'étudiant avec sa filière
        $lignesTableau[] = [
            'id'                 => $etd['id'],
            'numero'             => $etd['numero'],
            'photo'              => $etd['photo'],
            'nom_prenom'         => $etd['nom_prenom'],
            'module_id'          => null,
            'module_code'        => '—',
            'module_designation' => 'Aucun module assigné',
            'coefficient'        => null,
            'note'               => null,
            'filiere'            => $etd['filiere'],
        ];
    } else {
        foreach ($displayedModules as $mod) {
            $key = $etd['id'] . '_' . $mod['id'];
            $note = array_key_exists($key, $notesMap) ? $notesMap[$key] : null;
            $coeff = isset($mod['coefficient']) ? (int)$mod['coefficient'] : 1;
            if ($note !== null) {
                $sommeNotes += $note;
                $sommeNotesPonderees += ($note * $coeff);
                $totalCoefficients += $coeff;
                $nbNotes++;
            }
            $lignesTableau[] = [
                'id'                 => $etd['id'],
                'numero'             => $etd['numero'],
                'photo'              => $etd['photo'],
                'nom_prenom'         => $etd['nom_prenom'],
                'module_id'          => $mod['id'],
                'module_code'        => $mod['code_module'],
                'module_designation' => $mod['designation'],
                'coefficient'        => $coeff,
                'note'               => $note,
                'filiere'            => $etd['filiere'],
            ];
        }
    }
}

$moyenneClasse = $nbNotes > 0 ? round($sommeNotes / $nbNotes, 2) : null;
$moyennePonderee = $totalCoefficients > 0 ? round($sommeNotesPonderees / $totalCoefficients, 2) : null;

$base = '../';
$titre = 'Liste Des Etudiants';
require __DIR__ . '/../includes/header.php';
?>

<!-- Carte d'en-tête de l'enseignant et sélecteur -->
<div class="teacher-class-card" style="background:#ffffff; border:1px solid var(--border-light); border-left:4px solid var(--primary); border-radius:var(--radius-lg); padding:20px 24px; margin-bottom:24px; box-shadow:var(--shadow-sm);">
    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px;">
        <div>
            <span style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--primary);">Classe de l'enseignant</span>
            <h3 style="margin:4px 0 6px 0; color:var(--text-heading); font-size:20px;">
                <?= $enseignant ? h($enseignant['civilite'] . ' ' . $enseignant['nom_prenom']) : 'Aucun enseignant sélectionné' ?>
                <?php if ($enseignant): ?>
                    <span class="brand-badge" style="background:#eef2ff; color:#4f46e5; border-color:#c7d2fe; font-size:12px; vertical-align:middle; margin-left:8px;"><?= h($enseignant['grade']) ?></span>
                <?php endif; ?>
            </h3>
            <p style="margin:0; color:var(--text-muted); font-size:13.5px;">
                <strong>Spécialité :</strong> <?= $enseignant ? h($enseignant['specialite']) : '—' ?> &bull;
                <strong>Filière assignée :</strong>
                <?php if (!empty($filiere)): ?>
                    <span class="brand-badge" style="background:#f0fdf4; color:#166534; border-color:#86efac; font-weight:600;"><?= h($filiere) ?></span>
                <?php else: ?>
                    <span style="color:var(--text-light);">Non renseignée</span>
                <?php endif; ?>
                &bull;
                <strong>Module(s) enseigné(s) :</strong>
                <?php if (!empty($activeModules)): ?>
                    <?php foreach ($activeModules as $am): ?>
                        <span class="brand-badge" style="background:#eef2ff; color:#4f46e5; border-color:#c7d2fe; font-size:11.5px;">
                            <?= h($am['code_module']) ?> <em style="font-size:10.5px; opacity:0.85;">(Coeff: <?= h($am['coefficient']) ?>)</em>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span style="color:var(--text-light);">Aucun module</span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Sélecteur d'enseignant rapide -->
        <form method="get" action="etudiants.php" style="display:flex; align-items:center; gap:10px;">
            <label for="select-enseignant" style="font-weight:600; font-size:13px; color:var(--text-body);">Changer d'enseignant :</label>
            <select name="id" id="select-enseignant" onchange="this.form.submit()" style="padding:6px 12px; font-size:13.5px;">
                <?php foreach ($tousEnseignants as $te): ?>
                    <option value="<?= h($te['id']) ?>" <?= ($enseignant && (int)$te['id'] === (int)$enseignant['id']) ? 'selected' : '' ?>>
                        <?= h($te['nom_prenom']) ?> (<?= h($te['filiere'] ?: 'Sans filière') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Onglets / Filtres par Module -->
    <?php if (count($activeModules) > 1): ?>
        <div style="margin-top:16px; padding-top:14px; border-top:1px dashed var(--border-light); display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <span style="font-size:13px; font-weight:600; color:var(--text-body);">Filtrer par module :</span>
            <a href="etudiants.php?id=<?= h($idParam) ?>&module=all"
               class="btn-action <?= ($moduleFilter === '' || $moduleFilter === 'all') ? 'active-filter' : '' ?>"
               style="font-size:12px; padding:4px 12px; text-decoration:none;">
                Tous les modules (<?= count($activeModules) ?>)
            </a>
            <?php foreach ($activeModules as $am): ?>
                <?php $isActif = (strcasecmp($moduleFilter, $am['code_module']) === 0 || $moduleFilter === (string)$am['id']); ?>
                <a href="etudiants.php?id=<?= h($idParam) ?>&module=<?= urlencode($am['code_module']) ?>"
                   class="btn-action <?= $isActif ? 'active-filter' : '' ?>"
                   style="font-size:12px; padding:4px 12px; text-decoration:none;">
                    <?= h($am['code_module']) ?> — <?= h($am['designation']) ?> (Coeff: <?= h($am['coefficient']) ?>)
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Statistiques rapides -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:20px;">
    <div style="background:#f8fafc; border:1px solid var(--border-light); border-radius:var(--radius-md); padding:12px 16px;">
        <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Étudiants inscrits</div>
        <div style="font-size:22px; font-weight:700; color:var(--text-heading);"><?= count($etudiants) ?></div>
    </div>
    <div style="background:#f8fafc; border:1px solid var(--border-light); border-radius:var(--radius-md); padding:12px 16px;">
        <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Lignes de cours / Module</div>
        <div style="font-size:22px; font-weight:700; color:var(--text-heading);"><?= count($lignesTableau) ?></div>
    </div>
    <div style="background:#f8fafc; border:1px solid var(--border-light); border-radius:var(--radius-md); padding:12px 16px;">
        <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Notes saisies</div>
        <div style="font-size:22px; font-weight:700; color:var(--text-heading);"><?= $nbNotes ?> / <?= count($lignesTableau) ?></div>
    </div>
    <div style="background:#f8fafc; border:1px solid var(--border-light); border-radius:var(--radius-md); padding:12px 16px;">
        <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Moyenne de la classe</div>
        <div style="font-size:22px; font-weight:700; color:<?= ($moyenneClasse !== null && $moyenneClasse >= 10) ? '#166534' : '#991b1b' ?>;">
            <?= $moyenneClasse !== null ? number_format($moyenneClasse, 2, ',', ' ') . ' / 20' : '—' ?>
        </div>
        <?php if ($moyennePonderee !== null && count($activeModules) > 1): ?>
            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">
                Pondérée : <strong><?= number_format($moyennePonderee, 2, ',', ' ') ?> / 20</strong>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tableau des Étudiants -->
<?php if (empty($filiere)): ?>
    <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:var(--radius-md); padding:16px; margin-bottom:20px;">
        <p style="margin:0;"><strong>Attention :</strong> Cet enseignant n'a pas encore de <strong>Filière</strong> assignée. <a href="enseignant.php?id=<?= h($idParam) ?>">Modifier l'enseignant</a> pour lui attribuer une filière et des modules.</p>
    </div>
<?php elseif (empty($lignesTableau)): ?>
    <p>Aucun étudiant trouvé dans la filière <strong><?= h($filiere) ?></strong>.</p>
<?php else: ?>
    <table class="liste">
        <thead>
            <tr>
                <th style="width:50px;">Id</th>
                <th style="width:70px; text-align:center;">image</th>
                <th>nom / prenom</th>
                <th>Module</th>
                <th style="width:85px; text-align:center;">Coeff</th>
                <th style="width:130px; text-align:center;">Note</th>
                <th style="width:100px; text-align:center;">Filiere</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignesTableau as $row): ?>
                <tr>
                    <td><strong>#<?= h($row['id']) ?></strong></td>
                    <td style="text-align:center;">
                        <?php if (!empty($row['photo']) && file_exists(__DIR__ . '/../uploads/' . $row['photo'])): ?>
                            <img src="../uploads/<?= h($row['photo']) ?>" alt="Photo de <?= h($row['nom_prenom']) ?>" style="width:38px; height:38px; border-radius:50%; object-fit:cover; border:1.5px solid var(--border-light); display:inline-block; vertical-align:middle;">
                        <?php else: ?>
                            <div style="width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color:#4338ca; font-weight:700; font-size:14px; display:inline-flex; align-items:center; justify-content:center; border:1.5px solid var(--border-light); vertical-align:middle;" title="Sans photo">
                                <?= mb_strtoupper(mb_substr($row['nom_prenom'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong style="color:var(--text-heading);"><?= h($row['nom_prenom']) ?></strong>
                        <span style="color:var(--text-muted); font-size:12px; margin-left:6px;">(N° <?= h($row['numero']) ?>)</span>
                    </td>
                    <td>
                        <span class="brand-badge" style="background:#eef2ff; color:#4f46e5; border-color:#c7d2fe; font-weight:700; margin-right:6px;"><?= h($row['module_code']) ?></span>
                        <span style="color:var(--text-body); font-size:13.5px;"><?= h($row['module_designation']) ?></span>
                        <?php if ($row['coefficient'] !== null): ?>
                            <span class="brand-badge" style="background:#fef3c7; color:#92400e; border-color:#fde68a; font-weight:600; font-size:11.5px; margin-left:6px;" title="Coefficient du module">Coeff: <?= h($row['coefficient']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <?php if ($row['coefficient'] !== null): ?>
                            <span class="brand-badge" style="background:#fef3c7; color:#92400e; border-color:#fde68a; font-weight:700; font-size:12px; padding:3px 10px;" title="Coefficient du module">
                                <?= h($row['coefficient']) ?>
                            </span>
                        <?php else: ?>
                            <span style="color:var(--text-light);">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <?php if ($row['note'] !== null): ?>
                            <?php $isAdmis = $row['note'] >= 10; ?>
                            <span class="brand-badge" style="background:<?= $isAdmis ? '#f0fdf4' : '#fef2f2' ?>; color:<?= $isAdmis ? '#166534' : '#991b1b' ?>; border-color:<?= $isAdmis ? '#86efac' : '#fca5a5' ?>; font-weight:700; font-size:13px; padding:3px 10px;">
                                <?= number_format($row['note'], 2, ',', ' ') ?> / 20
                            </span>
                        <?php else: ?>
                            <span style="color:var(--text-light); font-size:13px;">—</span>
                            <?php if ($row['module_id']): ?>
                                <a href="../bulletin/bulletin.php?id=<?= h($row['id']) ?>&module_id=<?= h($row['module_id']) ?>" style="font-size:11px; margin-left:4px; color:var(--primary); text-decoration:none;" title="Saisir la note dans le Bulletin">+ Noter</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <span class="brand-badge" style="background:#f0fdf4; color:#166534; border-color:#86efac; font-weight:600;"><?= h($row['filiere']) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- Boutons d'action -->
<div style="margin-top:24px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
    <button type="button" onclick="window.print()" class="btn-print" style="background:var(--primary); color:#ffffff; border:none; padding:8px 18px; border-radius:var(--radius-md); font-weight:600; cursor:pointer;">
        Imprimer cette liste
    </button>
    <a href="enseignant.php?id=<?= h($idParam) ?>" class="btn-action" style="padding:8px 16px;">Modifier cet enseignant</a>
    <a href="liste.php" class="btn-action" style="padding:8px 16px;">Retour à la liste des enseignants</a>
    <a href="../module/module.php" class="btn-action" style="padding:8px 16px;">Gestion des Modules</a>
    <a href="../bulletin/bulletin.php" class="btn-action" style="padding:8px 16px;">Bulletin de Notes</a>
</div>

<style>
.active-filter {
    background: var(--primary) !important;
    color: #ffffff !important;
    border-color: var(--primary) !important;
}
@media print {
    #menu, .header-brand, hr, .form-recherche, .teacher-class-card form, .btn-print, a.btn-action, div[style*="margin-top:24px"] {
        display: none !important;
    }
    #page {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        width: 100% !important;
    }
}
</style>

<?php require __DIR__ . '/../includes/footer.php'; ?>
