<?php
// filiere/statistiques.php : statistiques de réussite par filière
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

assurer_schema_note($pdo);

$statistiques = [];
$erreur = null;
$totalAdmis = 0;
$totalAjournes = 0;
$totalNonEvalues = 0;
$termeRechercheFiliere = trim((string)($_GET['filiere'] ?? ''));
$modeRecherche = ($_GET['mode'] ?? 'nom') === 'id' ? 'id' : 'nom';
$nomFiliereRecherche = '';
$filieres = [];
$detailsEtudiants = [];

try {
    $filieres = $pdo->query('SELECT nom FROM filiere ORDER BY nom ASC')->fetchAll(PDO::FETCH_COLUMN);
    if ($termeRechercheFiliere !== '') {
        if ($modeRecherche === 'id') {
            $idFiliere = filter_var($termeRechercheFiliere, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $stmtFiliere = $pdo->prepare('SELECT nom FROM filiere WHERE id = :id');
            $stmtFiliere->execute([':id' => $idFiliere !== false ? $idFiliere : 0]);
            $nomFiliereRecherche = $stmtFiliere->fetchColumn() ?: '';
        } elseif (in_array($termeRechercheFiliere, $filieres, true)) {
            $nomFiliereRecherche = $termeRechercheFiliere;
        }
        if ($nomFiliereRecherche === '') {
            $erreur = "Aucune filière trouvée pour « {$termeRechercheFiliere} ».";
        }
    }

    $sql = '
        SELECT f.nom AS filiere, p.id AS etudiant_id, p.numero, p.nom_prenom,
               p.nationalite_code,
               n.note, m.coefficient, m.designation AS module
        FROM filiere f
        LEFT JOIN personne p ON p.filiere = f.nom
        LEFT JOIN note n ON n.etudiant_id = p.id
        LEFT JOIN module m ON m.id = n.module_id
    ';
    $params = [];
    if ($nomFiliereRecherche !== '') {
        $sql .= ' WHERE f.nom = :filiere';
        $params[':filiere'] = $nomFiliereRecherche;
    }
    $sql .= ' ORDER BY f.nom ASC, p.nom_prenom ASC, m.designation ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $etudiants = [];
    foreach ($stmt->fetchAll() as $row) {
        $nomFiliere = $row['filiere'];
        if (!isset($statistiques[$nomFiliere])) {
            $statistiques[$nomFiliere] = [
                'admis' => 0,
                'ajournes' => 0,
                'non_evalues' => 0,
            ];
        }
        if ($row['etudiant_id'] === null) {
            continue;
        }

        $idEtudiant = (int)$row['etudiant_id'];
        if (!isset($etudiants[$idEtudiant])) {
            $etudiants[$idEtudiant] = [
                'id' => $idEtudiant,
                'filiere' => $nomFiliere,
                'numero' => $row['numero'],
                'nom_prenom' => $row['nom_prenom'],
                'nationalite_code' => $row['nationalite_code'],
                'notes' => [],
            ];
        }
        if ($row['note'] !== null && $row['coefficient'] !== null) {
            $etudiants[$idEtudiant]['notes'][] = [
                'module' => $row['module'],
                'note' => $row['note'],
                'coefficient' => $row['coefficient'],
            ];
        }
    }

    foreach ($etudiants as $etudiant) {
        $filiereEtudiant = $etudiant['filiere'];
        $calculsMoyenne = calculer_moyenne_ponderee($etudiant['notes']);
        $etudiant['moyenne'] = $calculsMoyenne['moyenne'];
        $etudiant['resultat'] = 'Non évalué';
        if ($etudiant['moyenne'] === null) {
            $statistiques[$filiereEtudiant]['non_evalues']++;
            $totalNonEvalues++;
            $detailsEtudiants[] = $etudiant;
            continue;
        }

        $moyenne = $etudiant['moyenne'];
        $etudiant['moyenne'] = $moyenne;
        if ($moyenne >= 10) {
            $statistiques[$filiereEtudiant]['admis']++;
            $totalAdmis++;
            $etudiant['resultat'] = 'Admis(e)';
        } else {
            $statistiques[$filiereEtudiant]['ajournes']++;
            $totalAjournes++;
            $etudiant['resultat'] = 'Ajourné(e)';
        }
        $detailsEtudiants[] = $etudiant;
    }
} catch (PDOException $e) {
    $erreur = "Erreur lors du calcul des statistiques des filières.";
}

$totalEvalues = $totalAdmis + $totalAjournes;
$tauxAdmis = $totalEvalues > 0 ? ($totalAdmis / $totalEvalues) * 100 : 0;
$tauxAjournes = 100 - $tauxAdmis;
$maxEffectif = 1;
foreach ($statistiques as $statistique) {
    $maxEffectif = max($maxEffectif, $statistique['admis'], $statistique['ajournes']);
}

$base = '../';
$titre = 'Statistiques de réussite par filière';
require __DIR__ . '/../includes/header.php';
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    .stats-card {
        padding: 20px;
        background: var(--bg-card);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
    }
    .stats-card h3 {
        margin: 0 0 18px;
        color: var(--text-heading);
    }
    .bar-chart {
        display: flex;
        align-items: stretch;
        gap: 12px;
        min-height: 250px;
        overflow-x: auto;
        padding: 12px 4px 0;
        border-bottom: 1px solid var(--border-medium);
    }
    .bar-group {
        display: flex;
        flex: 1 0 72px;
        align-items: flex-end;
        justify-content: center;
        gap: 5px;
        position: relative;
    }
    .bar {
        width: 24px;
        min-height: 2px;
        border-radius: 4px 4px 0 0;
        position: relative;
    }
    .bar.admis { background: #34d399; }
    .bar.ajournes { background: #f87171; }
    .bar-value {
        position: absolute;
        top: -22px;
        left: 50%;
        transform: translateX(-50%);
        color: var(--text-body);
        font-size: 11px;
    }
    .bar-label {
        position: absolute;
        bottom: -24px;
        left: 0;
        right: 0;
        overflow: hidden;
        text-align: center;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: var(--text-muted);
        font-size: 11px;
    }
    .chart-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 32px;
        color: var(--text-body);
        font-size: 13px;
    }
    .legend-item { display: inline-flex; align-items: center; gap: 6px; }
    .legend-swatch { width: 12px; height: 12px; border-radius: 3px; }
    .pie-chart {
        width: 190px;
        height: 190px;
        margin: 12px auto;
        border-radius: 50%;
        background: conic-gradient(#34d399 0 <?= $tauxAdmis ?>%, #f87171 <?= $tauxAdmis ?>% 100%);
    }
    .pie-chart.empty {
        background: var(--bg-alt);
    }
    .stats-totals {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 16px;
        margin-top: 24px;
    }
    .stats-total {
        min-width: 180px;
        padding: 16px 20px;
        text-align: center;
        background: var(--bg-subtle);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
    }
    .stats-total strong { display: block; font-size: 26px; }
</style>

<?php if ($erreur): ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php elseif (!$statistiques): ?>
    <p>Aucune filière enregistrée.</p>
<?php else: ?>
    <form method="get" action="statistiques.php" class="form-recherche">
        <label for="mode-recherche">Rechercher une filière par :</label>
        <select name="mode" id="mode-recherche">
            <option value="nom" <?= $modeRecherche === 'nom' ? 'selected' : '' ?>>Nom</option>
            <option value="id" <?= $modeRecherche === 'id' ? 'selected' : '' ?>>ID</option>
        </select>
        <input type="search" name="filiere" id="filiere" <?= $modeRecherche === 'nom' ? 'list="filieres-disponibles"' : '' ?> value="<?= h($termeRechercheFiliere) ?>" placeholder="<?= $modeRecherche === 'id' ? 'ex: 3' : 'Toutes les filières' ?>" autocomplete="off">
        <datalist id="filieres-disponibles">
            <?php foreach ($filieres as $nomFiliere): ?>
                <option value="<?= h($nomFiliere) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <button type="submit">Filtrer les graphiques</button>
        <?php if ($termeRechercheFiliere !== ''): ?>
            <a href="statistiques.php">Afficher toutes</a>
        <?php endif; ?>
    </form>

    <p>Les statistiques sont recalculées depuis les notes actuelles à chaque chargement. Les étudiants sans note sont comptés comme non évalués, hors taux d’admission.</p>

    <div class="stats-grid">
        <section class="stats-card">
            <h3>Admis et ajournés<?= $nomFiliereRecherche !== '' ? ' — ' . h($nomFiliereRecherche) : ' par filière' ?></h3>
            <div class="bar-chart" role="img" aria-label="Histogramme du nombre d'étudiants admis et ajournés par filière">
                <?php foreach ($statistiques as $nomFiliere => $statistique): ?>
                    <div class="bar-group" title="<?= h($nomFiliere) ?> — <?= $statistique['admis'] ?> admis, <?= $statistique['ajournes'] ?> ajournés">
                        <div class="bar admis" style="height: <?= max(2, ($statistique['admis'] / $maxEffectif) * 190) ?>px;">
                            <span class="bar-value"><?= $statistique['admis'] ?></span>
                        </div>
                        <div class="bar ajournes" style="height: <?= max(2, ($statistique['ajournes'] / $maxEffectif) * 190) ?>px;">
                            <span class="bar-value"><?= $statistique['ajournes'] ?></span>
                        </div>
                        <span class="bar-label"><?= h($nomFiliere) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="chart-legend">
                <span class="legend-item"><span class="legend-swatch" style="background:#34d399;"></span>Admis</span>
                <span class="legend-item"><span class="legend-swatch" style="background:#f87171;"></span>Ajournés</span>
            </div>
        </section>

        <section class="stats-card">
            <h3>Répartition<?= $nomFiliereRecherche !== '' ? ' — ' . h($nomFiliereRecherche) : ' globale' ?> des étudiants évalués</h3>
            <div class="pie-chart <?= $totalEvalues === 0 ? 'empty' : '' ?>" role="img" aria-label="Répartition globale : <?= $totalAdmis ?> admis et <?= $totalAjournes ?> ajournés"></div>
            <?php if ($totalEvalues === 0): ?>
                <p style="text-align:center;">Aucun étudiant évalué.</p>
            <?php endif; ?>
            <div class="chart-legend">
                <span class="legend-item"><span class="legend-swatch" style="background:#34d399;"></span>Admis : <?= $totalAdmis ?> (<?= number_format($tauxAdmis, 1, ',', ' ') ?> %)</span>
                <span class="legend-item"><span class="legend-swatch" style="background:#f87171;"></span>Ajournés : <?= $totalAjournes ?> (<?= number_format($tauxAjournes, 1, ',', ' ') ?> %)</span>
            </div>
        </section>
    </div>

    <h3><?= $nomFiliereRecherche !== '' ? 'Résultats de la filière ' . h($nomFiliereRecherche) : 'Résultats par filière' ?></h3>
    <table class="liste">
        <thead>
            <tr>
                <th>Nom de la filière</th>
                <th>Admis</th>
                <th>Ajournés</th>
                <th>Non évalués</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($statistiques as $nomFiliere => $statistique): ?>
                <tr>
                    <td><?= h($nomFiliere) ?></td>
                    <td><?= $statistique['admis'] ?></td>
                    <td><?= $statistique['ajournes'] ?></td>
                    <td><?= $statistique['non_evalues'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Notes des étudiants<?= $nomFiliereRecherche !== '' ? ' — ' . h($nomFiliereRecherche) : ' par filière' ?></h3>
    <table class="liste">
        <thead>
            <tr>
                <th>ID étudiant</th>
                <th>N° étudiant</th>
                <th>Nom / Prénom</th>
                <?php if ($nomFiliereRecherche === ''): ?>
                    <th>Filière</th>
                <?php endif; ?>
                <th>Notes par module</th>
                <th>Moyenne</th>
                <th>Résultat</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detailsEtudiants as $etudiant): ?>
                <tr>
                    <td><?= h($etudiant['id'] ?? '') ?></td>
                    <td><?= h($etudiant['numero'] ?? '') ?></td>
                    <td><?= h($etudiant['nom_prenom'] ?? '') ?></td>
                    <?php if ($nomFiliereRecherche === ''): ?>
                        <td><?= h($etudiant['filiere']) ?></td>
                    <?php endif; ?>
                    <td>
                        <?php if ($etudiant['notes'] && preg_match('/^[A-Za-z]{2}$/', (string)$etudiant['nationalite_code'])): ?>
                            <img
                                class="nationalite-flag"
                                src="https://flagcdn.com/w40/<?= h(strtolower($etudiant['nationalite_code'])) ?>.png"
                                alt="Drapeau de la nationalité de <?= h($etudiant['nom_prenom']) ?>"
                                loading="lazy"
                            >
                            <br>
                        <?php endif; ?>
                        <?php if ($etudiant['notes']): ?>
                            <?php foreach ($etudiant['notes'] as $index => $note): ?>
                                <?= $index > 0 ? '<br>' : '' ?>
                                <?= h($note['module']) ?>: <?= number_format($note['note'], 2, ',', ' ') ?> / 20
                            <?php endforeach; ?>
                        <?php else: ?>
                            Aucune note
                        <?php endif; ?>
                    </td>
                    <td><?= $etudiant['moyenne'] !== null ? number_format($etudiant['moyenne'], 2, ',', ' ') . ' / 20' : '—' ?></td>
                    <td>
                        <?php if ($etudiant['resultat'] === 'Non évalué'): ?>
                            Non évalué
                        <?php else: ?>
                            <strong style="color: <?= $etudiant['resultat'] === 'Admis(e)' ? '#15803d' : '#b91c1c' ?>;">
                                <?= h($etudiant['resultat']) ?>
                            </strong>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="stats-totals">
        <div class="stats-total"><strong style="color:#34d399;"><?= $totalAdmis ?></strong>Étudiants admis</div>
        <div class="stats-total"><strong style="color:#f87171;"><?= $totalAjournes ?></strong>Étudiants ajournés</div>
        <div class="stats-total"><strong style="color:var(--text-muted);"><?= $totalNonEvalues ?></strong>Étudiants non évalués</div>
    </div>
<?php endif; ?>

<p style="margin-top:24px;"><a href="filiere.php">Retour à la gestion des filières</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
