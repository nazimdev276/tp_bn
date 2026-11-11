<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$filieres = ['TC', '2SC', '3ISIL', '3SI', 'M1', 'M2RSSI', 'M2WIC', 'M2ISI', '1ING', '2ING'];
$formatJson = ($_GET['format'] ?? '') === 'json';
$filiereDemandee = (string)($_GET['filiere'] ?? 'TC');
$filiereValide = in_array($filiereDemandee, $filieres, true);
$filiere = $filiereValide ? $filiereDemandee : 'TC';
$erreur = $filiereValide ? null : 'La filière sélectionnée est invalide.';
$etudiants = [];

if ($filiereValide) {
    try {
        assurer_schema_note($pdo);

        $stmt = $pdo->prepare('
            SELECT p.id AS etudiant_id, p.numero, p.nom_prenom, n.note, m.coefficient
            FROM personne p
            LEFT JOIN note n ON n.etudiant_id = p.id
            LEFT JOIN module m ON m.id = n.module_id
            WHERE p.filiere = :filiere
            ORDER BY p.numero ASC, p.nom_prenom ASC, m.designation ASC
        ');
        $stmt->execute([':filiere' => $filiere]);

        $etudiantsParId = [];
        foreach ($stmt->fetchAll() as $row) {
            $idEtudiant = (int)$row['etudiant_id'];
            if (!isset($etudiantsParId[$idEtudiant])) {
                $etudiantsParId[$idEtudiant] = [
                    'numero' => $row['numero'],
                    'nom_prenom' => $row['nom_prenom'],
                    'notes' => [],
                ];
            }
            if ($row['note'] !== null && $row['coefficient'] !== null) {
                $etudiantsParId[$idEtudiant]['notes'][] = [
                    'note' => $row['note'],
                    'coefficient' => $row['coefficient'],
                ];
            }
        }
        $etudiants = array_values($etudiantsParId);
    } catch (PDOException $e) {
        $erreur = 'Impossible de charger le PV de cette filière.';
    }
}

$moyennes = [];
$lignesPv = [];
foreach ($etudiants as $etudiant) {
    $moyenne = calculer_moyenne_ponderee($etudiant['notes'])['moyenne'];
    if ($moyenne !== null) {
        $moyennes[] = $moyenne;
    }

    $lignesPv[] = [
        'numero' => $etudiant['numero'],
        'nom_prenom' => $etudiant['nom_prenom'],
        'moyenne' => $moyenne !== null ? sprintf('%05.2f', $moyenne) : null,
    ];
}

$statistiques = [
    'moyenne' => null,
    'minimum' => null,
    'maximum' => null,
];
if ($moyennes) {
    $statistiques = [
        'moyenne' => sprintf('%05.2f', array_sum($moyennes) / count($moyennes)),
        'minimum' => sprintf('%05.2f', min($moyennes)),
        'maximum' => sprintf('%05.2f', max($moyennes)),
    ];
}

if ($formatJson) {
    header('Content-Type: application/json; charset=utf-8');
    if ($erreur !== null) {
        http_response_code($filiereValide ? 500 : 400);
        echo json_encode(['erreur' => $erreur], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'etudiants' => $lignesPv,
        'statistiques' => $statistiques,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$base = '../';
$titre = 'PV GLOBAL';
require __DIR__ . '/../includes/header.php';
?>

<style>
    .pv-controls {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin: 20px 0;
    }
    .pv-content {
        max-width: 900px;
        margin: 24px auto;
    }
    .pv-table {
        width: 100%;
        border-collapse: collapse;
    }
    .pv-table th,
    .pv-table td {
        padding: 10px 12px;
        border: 1px solid var(--border-medium);
        text-align: left;
    }
    .pv-table thead th {
        background: var(--bg-subtle);
        color: var(--text-heading);
    }
    .pv-table .pv-average {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .pv-summary {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 12px 24px;
        margin-top: 20px;
        font-weight: 600;
    }
    #pv-message:empty { display: none; }
    @media print {
        @page { margin: 1.5cm; }
        .header-brand,
        #menu,
        #page > hr,
        #pied,
        .pv-controls,
        .no-print {
            display: none !important;
        }
        #page > h2 {
            display: block !important;
            margin: 0 0 20px;
            text-align: center;
        }
        #page {
            width: auto;
            margin: 0;
            padding: 0;
            border: 0;
            box-shadow: none;
        }
        .pv-content {
            max-width: none;
            margin: 0;
        }
        .pv-table thead th {
            background: #eee !important;
            color: #000 !important;
            print-color-adjust: exact;
        }
    }
</style>

<div class="pv-controls">
    <label for="pv-filiere"><strong>Filière :</strong></label>
    <select id="pv-filiere" name="filiere">
        <?php foreach ($filieres as $optionFiliere): ?>
            <option value="<?= h($optionFiliere) ?>" <?= $filiere === $optionFiliere ? 'selected' : '' ?>>
                <?= h($optionFiliere) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="button" id="pv-imprimer">Imprimer</button>
</div>
<p id="pv-erreur" class="erreur no-print" role="alert"><?= h($erreur ?? '') ?></p>

<main id="pv-content" class="pv-content">
    <table class="pv-table">
        <thead>
            <tr>
                <th>N°Etudiant</th>
                <th>NOMS ET PRENOMS</th>
                <th class="pv-average">MOY.GEN</th>
            </tr>
        </thead>
        <tbody id="pv-etudiants">
            <?php if ($erreur): ?>
                <tr><td colspan="3">PV indisponible.</td></tr>
            <?php elseif (!$lignesPv): ?>
                <tr><td colspan="3">Aucun étudiant n’est enregistré dans cette filière.</td></tr>
            <?php else: ?>
                <?php foreach ($lignesPv as $ligne): ?>
                    <tr>
                        <td><?= h($ligne['numero']) ?></td>
                        <td><?= h($ligne['nom_prenom']) ?></td>
                        <td class="pv-average"><?= h($ligne['moyenne'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <p id="pv-message" class="petit no-print">
        <?php if (!$erreur && $lignesPv && !$moyennes): ?>
            Aucune note enregistrée pour les étudiants de cette filière.
        <?php elseif (count($moyennes) < count($lignesPv)): ?>
            Les statistiques portent sur les étudiants ayant au moins une note.
        <?php endif; ?>
    </p>
    <div class="pv-summary">
        <span>Moyenne : <span id="pv-moyenne"><?= h($statistiques['moyenne'] ?? '—') ?></span></span>
        <span>Moy.Min : <span id="pv-minimum"><?= h($statistiques['minimum'] ?? '—') ?></span></span>
        <span>Moy.Max : <span id="pv-maximum"><?= h($statistiques['maximum'] ?? '—') ?></span></span>
    </div>
</main>

<script>
    (function () {
        var selectFiliere = document.getElementById('pv-filiere');
        var corpsEtudiants = document.getElementById('pv-etudiants');
        var erreur = document.getElementById('pv-erreur');
        var message = document.getElementById('pv-message');

        function ajouterLigne(etudiant) {
            var ligne = document.createElement('tr');
            [etudiant.numero, etudiant.nom_prenom, etudiant.moyenne || '—'].forEach(function (valeur, index) {
                var cellule = document.createElement('td');
                cellule.textContent = valeur;
                if (index === 2) cellule.className = 'pv-average';
                ligne.appendChild(cellule);
            });
            corpsEtudiants.appendChild(ligne);
        }

        selectFiliere.addEventListener('change', async function () {
            erreur.textContent = '';
            message.textContent = '';
            var url = new URL(window.location.href);
            url.searchParams.set('format', 'json');
            url.searchParams.set('filiere', selectFiliere.value);

            try {
                var reponse = await fetch(url, { headers: { Accept: 'application/json' } });
                var donnees = await reponse.json();
                if (!reponse.ok) {
                    throw new Error(donnees.erreur || 'Impossible de charger le PV.');
                }

                corpsEtudiants.replaceChildren();
                if (donnees.etudiants.length === 0) {
                    var ligneVide = document.createElement('tr');
                    var celluleVide = document.createElement('td');
                    celluleVide.colSpan = 3;
                    celluleVide.textContent = 'Aucun étudiant n’est enregistré dans cette filière.';
                    ligneVide.appendChild(celluleVide);
                    corpsEtudiants.appendChild(ligneVide);
                } else {
                    donnees.etudiants.forEach(ajouterLigne);
                    if (donnees.statistiques.moyenne === null) {
                        message.textContent = 'Aucune note enregistrée pour les étudiants de cette filière.';
                    } else if (donnees.etudiants.some(function (etudiant) { return etudiant.moyenne === null; })) {
                        message.textContent = 'Les statistiques portent sur les étudiants ayant au moins une note.';
                    }
                }

                document.getElementById('pv-moyenne').textContent = donnees.statistiques.moyenne || '—';
                document.getElementById('pv-minimum').textContent = donnees.statistiques.minimum || '—';
                document.getElementById('pv-maximum').textContent = donnees.statistiques.maximum || '—';
                url.searchParams.delete('format');
                window.history.replaceState(null, '', url);
            } catch (e) {
                erreur.textContent = e.message || 'Impossible de charger le PV.';
            }
        });

        document.getElementById('pv-imprimer').addEventListener('click', function () {
            window.print();
        });
    })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
