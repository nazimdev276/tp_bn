<?php
// filiere/choix_statistiques.php : choix du filtrage et affichage des statistiques de réussite
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

assurer_schema_note($pdo);

$erreur = null;
$filieres = [];
$statsParFiliere = [];
$detailsEtudiants = [];
$totalAdmis = 0;
$totalAjournes = 0;
$totalNonEvalues = 0;
$totalEtudiants = 0;

// Récupération des options de filtrage :
$filtrer = (string)($_GET['filtrer'] ?? '');
$filiereSelectionnee = trim((string)($_GET['filiere'] ?? ''));
if ($filtrer === '' && $filiereSelectionnee !== '') {
    $filtrer = 'oui';
} elseif ($filtrer !== 'oui') {
    $filtrer = 'non';
}

try {
    $filieres = $pdo->query('SELECT id, nom FROM filiere ORDER BY id ASC')->fetchAll();
    $nomsFilieres = array_column($filieres, 'nom');

    if ($filtrer === 'oui') {
        if ($filiereSelectionnee === '' || !in_array($filiereSelectionnee, $nomsFilieres, true)) {
            $filiereSelectionnee = $nomsFilieres[0] ?? '';
        }
    }

    // Initialisation des statistiques par filière
    foreach ($filieres as $f) {
        $statsParFiliere[$f['nom']] = [
            'id' => $f['id'],
            'nom' => $f['nom'],
            'admis' => 0,
            'ajournes' => 0,
            'non_evalues' => 0,
            'total_etudiants' => 0,
        ];
    }

    $sql = '
        SELECT f.id AS filiere_id, f.nom AS filiere, p.id AS etudiant_id, p.numero, p.nom_prenom,
               p.nationalite_code,
               n.note, m.coefficient, m.designation AS module
        FROM filiere f
        LEFT JOIN personne p ON p.filiere = f.nom
        LEFT JOIN note n ON n.etudiant_id = p.id
        LEFT JOIN module m ON m.id = n.module_id
    ';
    $params = [];
    $sql .= ' ORDER BY f.id ASC, p.nom_prenom ASC, m.designation ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $etudiants = [];
    foreach ($rows as $row) {
        $nomF = $row['filiere'];
        if (!isset($statsParFiliere[$nomF]) || $row['etudiant_id'] === null) {
            continue;
        }

        $idEtudiant = (int)$row['etudiant_id'];
        if (!isset($etudiants[$idEtudiant])) {
            $etudiants[$idEtudiant] = [
                'id' => $idEtudiant,
                'filiere' => $nomF,
                'numero' => $row['numero'],
                'nom_prenom' => $row['nom_prenom'],
                'nationalite_code' => $row['nationalite_code'],
                'notes' => [],
            ];
            $statsParFiliere[$nomF]['total_etudiants']++;
        }
        if ($row['note'] !== null && $row['coefficient'] !== null) {
            $etudiants[$idEtudiant]['notes'][] = [
                'module' => $row['module'],
                'note' => (float)$row['note'],
                'coefficient' => (float)$row['coefficient'],
            ];
        }
    }

    foreach ($etudiants as $etudiant) {
        $nomF = $etudiant['filiere'];
        $calculsMoyenne = calculer_moyenne_ponderee($etudiant['notes']);
        $moyenne = $calculsMoyenne['moyenne'];
        $etudiant['moyenne'] = $moyenne;

        if ($moyenne === null) {
            $statsParFiliere[$nomF]['non_evalues']++;
            $totalNonEvalues++;
            $etudiant['resultat'] = 'Non évalué';
        } elseif ($moyenne >= 10.0) {
            $statsParFiliere[$nomF]['admis']++;
            $totalAdmis++;
            $etudiant['resultat'] = 'Admis(e)';
        } else {
            $statsParFiliere[$nomF]['ajournes']++;
            $totalAjournes++;
            $etudiant['resultat'] = 'Ajourné(e)';
        }
        $totalEtudiants++;
        $detailsEtudiants[] = $etudiant;
    }

    if ($filtrer === 'oui') {
        $statsParFiliere = array_intersect_key($statsParFiliere, [$filiereSelectionnee => true]);
        $detailsEtudiants = array_values(array_filter(
            $detailsEtudiants,
            static fn(array $etudiant): bool => $etudiant['filiere'] === $filiereSelectionnee
        ));
    }
} catch (PDOException $e) {
    $erreur = "Erreur lors du chargement des statistiques : " . $e->getMessage();
}

$totalEvalues = $totalAdmis + $totalAjournes;
$tauxReussiteGlobal = $totalEvalues > 0 ? ($totalAdmis / $totalEvalues) * 100 : 0;
$labelsGraphique = array_keys($statsParFiliere);
$admisGraphique = array_column($statsParFiliere, 'admis');
$ajournesGraphique = array_column($statsParFiliere, 'ajournes');

$base = '../';
$titre = 'Statistiques de réussite par filière';
require __DIR__ . '/../includes/header.php';
?>

<style>
    .choix-stats-container {
        max-width: 900px;
        margin: 20px auto;
        padding: 24px 28px;
        background: var(--bg-card);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
    }
    .choix-stats-container h3 {
        margin-top: 0;
        margin-bottom: 12px;
        color: var(--text-heading);
    }
    .choix-stats-intro {
        color: var(--text-muted);
        margin-bottom: 20px;
        font-size: 14px;
    }
    .filter-options-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 16px;
    }
    .radio-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: var(--bg-subtle);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all 0.18s ease;
    }
    .radio-card:hover {
        border-color: var(--primary);
        background: var(--bg-alt);
    }
    .radio-card.active {
        border-color: var(--primary);
        background: var(--primary-light);
    }
    .radio-card input[type="radio"] {
        margin: 0;
    }
    .filiere-selection-box {
        margin-top: 14px;
        padding: 16px;
        background: var(--bg-subtle);
        border: 1px dashed var(--border-medium);
        border-radius: var(--radius-md);
        transition: all 0.2s ease;
    }
    .filiere-selection-box.hidden {
        display: none;
    }
    .filiere-radios-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 10px;
        margin-top: 12px;
    }
    .filiere-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: var(--bg-card);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: 13.5px;
        transition: all 0.15s ease;
    }
    .filiere-pill:hover {
        border-color: var(--primary);
    }
    .filiere-pill.selected {
        border-color: var(--primary);
        background: var(--primary-light);
    }
    .choix-actions {
        margin-top: 22px;
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }
    .btn-submit-stats {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        background: var(--primary);
        color: #062b29;
        border: none;
        border-radius: var(--radius-md);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.18s ease;
    }
    .btn-submit-stats:hover {
        background: var(--primary-hover);
    }
    .stats-summary-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin: 24px 0 16px;
    }
    .stats-badge-card {
        flex: 1 1 180px;
        padding: 14px 18px;
        background: var(--bg-subtle);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
        text-align: center;
    }
    .stats-badge-card strong {
        display: block;
        font-size: 24px;
        margin-bottom: 2px;
    }
    .stats-badge-card span {
        color: var(--text-muted);
        font-size: 13px;
    }
    .stats-graphique {
        margin-top: 30px;
        padding: 20px;
        background: var(--bg-card);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
    }
    .stats-graphiques-globaux {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }
    .stats-graphiques-globaux .stats-graphique {
        min-width: 0;
    }
    .stats-graphique h3 {
        margin-top: 0;
        color: var(--text-heading);
    }
    .stats-graphique-canvas {
        position: relative;
        height: 360px;
    }
    @media (max-width: 600px) {
        .stats-graphiques-globaux {
            grid-template-columns: 1fr;
        }
        .stats-graphique-canvas {
            height: 280px;
        }
    }
</style>

<div class="choix-stats-container">
    <h3>Options d'affichage des statistiques de réussite</h3>
    <p class="choix-stats-intro">
        Sélectionnez si vous souhaitez consulter les statistiques de toutes les filières ou filtrer par une filière spécifique.
    </p>

    <?php if ($erreur): ?>
        <p class="erreur"><?= h($erreur) ?></p>
    <?php endif; ?>

    <form method="get" action="choix_statistiques.php" id="form-choix-statistiques">
        <div class="filter-options-group">
            <label class="radio-card <?= $filtrer === 'non' ? 'active' : '' ?>" id="label-filtrer-non">
                <input type="radio" name="filtrer" value="non" id="radio-filtrer-non" <?= $filtrer === 'non' ? 'checked' : '' ?>>
                <div>
                    <strong>Toutes les filières</strong>
                    <div style="font-size: 12.5px; color: var(--text-muted);">Afficher le tableau récapitulatif avec toutes les filières et leurs nombres d'admis et d'ajournés</div>
                </div>
            </label>

            <label class="radio-card <?= $filtrer === 'oui' ? 'active' : '' ?>" id="label-filtrer-oui">
                <input type="radio" name="filtrer" value="oui" id="radio-filtrer-oui" <?= $filtrer === 'oui' ? 'checked' : '' ?>>
                <div>
                    <strong>Filtrer par filière</strong>
                    <div style="font-size: 12.5px; color: var(--text-muted);">Consulter uniquement les résultats d'une filière spécifique</div>
                </div>
            </label>
        </div>

        <div id="section-filiere" class="filiere-selection-box <?= $filtrer === 'oui' ? '' : 'hidden' ?>">
            <label style="font-weight:600; color:var(--text-heading); display:block; margin-bottom:4px;">
                Sélectionnez la filière à filtrer :
            </label>
            <?php if (!$filieres): ?>
                <p style="color:var(--text-muted); margin:8px 0 0;">Aucune filière trouvée.</p>
            <?php else: ?>
                <div class="filiere-radios-grid">
                    <?php foreach ($filieres as $index => $f): ?>
                        <?php $isFiliereActive = ($filtrer === 'oui' && $filiereSelectionnee === $f['nom']) || ($filtrer !== 'oui' && $index === 0); ?>
                        <label class="filiere-pill <?= $isFiliereActive ? 'selected' : '' ?>">
                            <input
                                type="radio"
                                name="filiere"
                                value="<?= h($f['nom']) ?>"
                                <?= $isFiliereActive ? 'checked' : '' ?>
                            >
                            <span><?= h($f['nom']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="choix-actions">
            <button type="submit" class="btn-submit-stats">
                Afficher les statistiques
            </button>
            <a href="liste.php" class="btn-link" style="color:var(--text-muted); text-decoration:none; font-size:13.5px;">
                ← Retour à la liste des filières
            </a>
        </div>
    </form>
</div>

<hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border-light);">

<?php if ($filtrer === 'non'): ?>
    <!-- Affichage pour "Toutes les filières" : tableau des filières avec admis et ajournés -->
    <h3>Tableau récapitulatif des statistiques de réussite par filière</h3>
    <p style="color:var(--text-muted); margin-bottom:18px;">
        Nombre d'étudiants admis (moyenne &ge; 10/20) et ajournés (moyenne &lt; 10/20) pour chaque filière enregistrée :
    </p>

    <div class="stats-summary-bar">
        <div class="stats-badge-card">
            <strong style="color: #34d399;"><?= $totalAdmis ?></strong>
            <span>Total Admis (Passing)</span>
        </div>
        <div class="stats-badge-card">
            <strong style="color: #f87171;"><?= $totalAjournes ?></strong>
            <span>Total Ajournés (Failing)</span>
        </div>
        <div class="stats-badge-card">
            <strong style="color: var(--text-muted);"><?= $totalNonEvalues ?></strong>
            <span>Non évalués</span>
        </div>
        <div class="stats-badge-card">
            <strong><?= $totalEtudiants ?></strong>
            <span>Total étudiants</span>
        </div>
        <div class="stats-badge-card">
            <strong style="color: var(--primary);"><?= number_format($tauxReussiteGlobal, 1, ',', ' ') ?> %</strong>
            <span>Taux de réussite global</span>
        </div>
    </div>

    <?php if (!$statsParFiliere): ?>
        <p>Aucune filière trouvée.</p>
    <?php else: ?>
        <table class="liste">
            <thead>
                <tr>
                    <th>Filière</th>
                    <th style="text-align: center;">Étudiants admis (Passing)</th>
                    <th style="text-align: center;">Étudiants ajournés (Failing)</th>
                    <th style="text-align: center;">Non évalués</th>
                    <th style="text-align: center;">Total étudiants</th>
                    <th style="text-align: center;">Taux de réussite</th>
                    <th style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statsParFiliere as $nomF => $stat): ?>
                    <?php
                        $evaluesFiliere = $stat['admis'] + $stat['ajournes'];
                        $tauxFiliere = $evaluesFiliere > 0 ? ($stat['admis'] / $evaluesFiliere) * 100 : 0;
                    ?>
                    <tr>
                        <td><strong><?= h($nomF) ?></strong></td>
                        <td style="text-align: center;">
                            <strong style="color: #34d399; font-size: 15px;"><?= $stat['admis'] ?></strong>
                        </td>
                        <td style="text-align: center;">
                            <strong style="color: #f87171; font-size: 15px;"><?= $stat['ajournes'] ?></strong>
                        </td>
                        <td style="text-align: center; color: var(--text-muted);">
                            <?= $stat['non_evalues'] ?>
                        </td>
                        <td style="text-align: center; font-weight: 600;">
                            <?= $stat['total_etudiants'] ?>
                        </td>
                        <td style="text-align: center;">
                            <?= $evaluesFiliere > 0 ? number_format($tauxFiliere, 1, ',', ' ') . ' %' : '—' ?>
                        </td>
                        <td style="text-align: center;">
                            <a href="choix_statistiques.php?filtrer=oui&filiere=<?= urlencode($nomF) ?>" class="btn-link" style="padding: 4px 10px; font-size: 12.5px; border-radius: var(--radius-sm);">
                                Filtrer cette filière
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: var(--bg-subtle); font-weight: bold;">
                    <td>Total évalués (admis + ajournés)</td>
                    <td style="text-align: center; color: #34d399; font-size: 15px;"><?= $totalAdmis ?></td>
                    <td style="text-align: center; color: #f87171; font-size: 15px;"><?= $totalAjournes ?></td>
                    <td style="text-align: center; color: var(--text-muted);">—</td>
                    <td style="text-align: center;"><?= $totalEvalues ?></td>
                    <td style="text-align: center; color: var(--primary);"><?= number_format($tauxReussiteGlobal, 1, ',', ' ') ?> %</td>
                    <td></td>
                </tr>
                <tr style="background: var(--bg-subtle); font-weight: bold; border-top: 2px solid var(--border-medium);">
                    <td>Total général (tous les étudiants)</td>
                    <td style="text-align: center; color: #34d399; font-size: 15px;"><?= $totalAdmis ?></td>
                    <td style="text-align: center; color: #f87171; font-size: 15px;"><?= $totalAjournes ?></td>
                    <td style="text-align: center; color: var(--text-muted);"><?= $totalNonEvalues ?></td>
                    <td style="text-align: center;"><?= $totalEtudiants ?></td>
                    <td style="text-align: center; color: var(--primary);"><?= number_format($tauxReussiteGlobal, 1, ',', ' ') ?> %</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

<?php else: ?>
    <!-- Affichage pour "Filtrer par filière" : résultats détaillés de la filière sélectionnée -->
    <?php
        $statUnique = $statsParFiliere[$filiereSelectionnee] ?? null;
        $evaluesUnique = $statUnique ? ($statUnique['admis'] + $statUnique['ajournes']) : 0;
        $tauxUnique = $evaluesUnique > 0 ? ($statUnique['admis'] / $evaluesUnique) * 100 : 0;
    ?>

    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
        <h3>Résultats de réussite — Filière <?= h($filiereSelectionnee) ?></h3>
        <a href="choix_statistiques.php?filtrer=non" class="btn-link" style="display:inline-block; padding:7px 14px; background:var(--bg-subtle); color:var(--text-body); border:1px solid var(--border-light); border-radius:var(--radius-md); text-decoration:none; font-size:13px;">
            ← Afficher toutes les filières
        </a>
    </div>

    <div class="stats-summary-bar">
        <div class="stats-badge-card">
            <strong style="color: #34d399;"><?= $statUnique ? $statUnique['admis'] : 0 ?></strong>
            <span>Admis (Passing)</span>
        </div>
        <div class="stats-badge-card">
            <strong style="color: #f87171;"><?= $statUnique ? $statUnique['ajournes'] : 0 ?></strong>
            <span>Ajournés (Failing)</span>
        </div>
        <div class="stats-badge-card">
            <strong style="color: var(--text-muted);"><?= $statUnique ? $statUnique['non_evalues'] : 0 ?></strong>
            <span>Non évalués</span>
        </div>
        <div class="stats-badge-card">
            <strong><?= $statUnique ? $statUnique['total_etudiants'] : 0 ?></strong>
            <span>Total étudiants</span>
        </div>
        <div class="stats-badge-card">
            <strong style="color: var(--primary);"><?= $evaluesUnique > 0 ? number_format($tauxUnique, 1, ',', ' ') . ' %' : '—' ?></strong>
            <span>Taux de réussite</span>
        </div>
    </div>

    <h4>Notes et moyennes des étudiants de <?= h($filiereSelectionnee) ?></h4>
    <?php if (!$detailsEtudiants): ?>
        <p>Aucun étudiant enregistré dans cette filière.</p>
    <?php else: ?>
        <table class="liste">
            <thead>
                <tr>
                    <th>N° étudiant</th>
                    <th>Nom et prénom</th>
                    <th>Notes par module</th>
                    <th>Moyenne</th>
                    <th>Résultat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detailsEtudiants as $etudiant): ?>
                    <tr>
                        <td><?= h($etudiant['numero'] ?? '') ?></td>
                        <td><?= h($etudiant['nom_prenom'] ?? '') ?></td>
                        <td>
                            <?php if ($etudiant['notes']): ?>
                                <?php foreach ($etudiant['notes'] as $nIdx => $nItem): ?>
                                    <?= $nIdx > 0 ? '<br>' : '' ?>
                                    <?= h($nItem['module']) ?>: <?= number_format($nItem['note'], 2, ',', ' ') ?> / 20
                                    <span style="color:var(--text-muted); font-size:12px;">(coeff <?= h($nItem['coefficient']) ?>)</span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">Aucune note</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= $etudiant['moyenne'] !== null ? number_format($etudiant['moyenne'], 2, ',', ' ') . ' / 20' : '—' ?></strong>
                        </td>
                        <td>
                            <?php if ($etudiant['resultat'] === 'Non évalué'): ?>
                                <span style="color: var(--text-muted);">Non évalué</span>
                            <?php elseif ($etudiant['resultat'] === 'Admis(e)'): ?>
                                <strong style="color: #34d399;">Admis(e)</strong>
                            <?php else: ?>
                                <strong style="color: #f87171;">Ajourné(e)</strong>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>

<section class="stats-graphique">
    <h3>Histogramme des étudiants admis et ajournés</h3>
    <p style="color:var(--text-muted);">
        <?= $filtrer === 'non'
            ? 'Comparaison du nombre d’étudiants admis et ajournés pour chaque filière.'
            : 'Nombre d’étudiants admis et ajournés dans la filière sélectionnée.' ?>
    </p>
    <?php if ($labelsGraphique): ?>
        <div class="stats-graphique-canvas">
            <canvas id="graphique-reussite" aria-label="Histogramme des étudiants admis et ajournés par filière" role="img"></canvas>
        </div>
    <?php else: ?>
        <p style="color:var(--text-muted);">Aucune statistique à représenter.</p>
    <?php endif; ?>
</section>

<div class="stats-graphiques-globaux">
    <section class="stats-graphique">
        <h3>Histogramme global — toutes les filières</h3>
        <p style="color:var(--text-muted);">
            Total des étudiants admis et ajournés, toutes filières confondues.
        </p>
        <?php if ($filieres): ?>
            <div class="stats-graphique-canvas">
                <canvas id="graphique-reussite-global" aria-label="Histogramme global des étudiants admis et ajournés toutes filières confondues" role="img"></canvas>
            </div>
        <?php else: ?>
            <p style="color:var(--text-muted);">Aucune statistique à représenter.</p>
        <?php endif; ?>
    </section>

    <section class="stats-graphique">
        <h3>Répartition globale — toutes les filières</h3>
        <p style="color:var(--text-muted);">
            Part des étudiants admis et ajournés, toutes filières confondues.
        </p>
        <?php if ($filieres): ?>
            <div class="stats-graphique-canvas">
                <canvas id="graphique-reussite-camembert" aria-label="Diagramme circulaire de la répartition des étudiants admis et ajournés toutes filières confondues" role="img"></canvas>
            </div>
        <?php else: ?>
            <p style="color:var(--text-muted);">Aucune statistique à représenter.</p>
        <?php endif; ?>
    </section>
</div>

<p style="margin-top: 28px;">
    <a href="liste.php" style="margin-right: 16px;">Liste des filières</a>
    <a href="filiere.php">Retour à la gestion des filières</a>
</p>

<?php if ($labelsGraphique || $filieres): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
<?php if ($labelsGraphique): ?>
new Chart(document.getElementById('graphique-reussite'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsGraphique, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        datasets: [
            {
                label: 'Admis (Passing)',
                data: <?= json_encode($admisGraphique) ?>,
                backgroundColor: '#34d399'
            },
            {
                label: 'Ajournés (Failing)',
                data: <?= json_encode($ajournesGraphique) ?>,
                backgroundColor: '#f87171'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                title: { display: true, text: 'Filière' }
            },
            y: {
                beginAtZero: true,
                ticks: { precision: 0 },
                title: { display: true, text: 'Nombre d’étudiants' }
            }
        }
    }
});
<?php endif; ?>

<?php if ($filieres): ?>
new Chart(document.getElementById('graphique-reussite-global'), {
    type: 'bar',
    data: {
        labels: ['Admis (Passing)', 'Ajournés (Failing)'],
        datasets: [{
            label: 'Nombre d’étudiants',
            data: <?= json_encode([$totalAdmis, $totalAjournes]) ?>,
            backgroundColor: ['#34d399', '#f87171']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                title: { display: true, text: 'Résultat' }
            },
            y: {
                beginAtZero: true,
                ticks: { precision: 0 },
                title: { display: true, text: 'Nombre d’étudiants' }
            }
        },
        plugins: {
            legend: { display: false }
        }
    }
});

new Chart(document.getElementById('graphique-reussite-camembert'), {
    type: 'pie',
    data: {
        labels: ['Admis (Passing)', 'Ajournés (Failing)'],
        datasets: [{
            data: <?= json_encode([$totalAdmis, $totalAjournes]) ?>,
            backgroundColor: ['#34d399', '#f87171']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
<?php endif; ?>
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioNon = document.getElementById('radio-filtrer-non');
    const radioOui = document.getElementById('radio-filtrer-oui');
    const labelNon = document.getElementById('label-filtrer-non');
    const labelOui = document.getElementById('label-filtrer-oui');
    const sectionFiliere = document.getElementById('section-filiere');
    const filierePills = document.querySelectorAll('.filiere-pill');
    const filiereInputs = document.querySelectorAll('input[name="filiere"]');
    const form = document.getElementById('form-choix-statistiques');

    function updateVisibility() {
        if (radioOui && radioOui.checked) {
            sectionFiliere.classList.remove('hidden');
            labelOui.classList.add('active');
            labelNon.classList.remove('active');
        } else {
            sectionFiliere.classList.add('hidden');
            labelNon.classList.add('active');
            labelOui.classList.remove('active');
        }
    }

    if (radioNon && radioOui) {
        radioNon.addEventListener('change', function() {
            updateVisibility();
        });
        radioOui.addEventListener('change', function() {
            updateVisibility();
        });
    }

    filiereInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            if (radioOui) {
                radioOui.checked = true;
                updateVisibility();
            }
            filierePills.forEach(function(pill) {
                pill.classList.remove('selected');
            });
            const parentPill = input.closest('.filiere-pill');
            if (parentPill) {
                parentPill.classList.add('selected');
            }
        });
    });

    updateVisibility();
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
