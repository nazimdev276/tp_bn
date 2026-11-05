<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

assurer_schema_note($pdo);

$numero = trim((string)($_GET['numero'] ?? ''));
$etudiant = null;
$notes = [];
$erreur = null;
$totalCoefficients = 0;
$totalPoints = 0.0;
$noteMin = null;
$noteMax = null;
$modulesMin = [];
$modulesMax = [];

if ($numero !== '') {
    $stmt = $pdo->prepare('
        SELECT id, numero, civilite, nom_prenom, filiere, adresse, photo
        FROM personne
        WHERE numero = :numero
        LIMIT 1
    ');
    $stmt->execute([':numero' => $numero]);
    $etudiant = $stmt->fetch() ?: null;

    if (!$etudiant) {
        $erreur = "Aucun étudiant trouvé pour le numéro « {$numero} ».";
    } else {
        $stmt = $pdo->prepare('
            SELECT m.code_module, m.designation, m.coefficient, n.note,
                   COALESCE(n.nationalite_code, p.nationalite_code) AS nationalite_code,
                   COALESCE(nat.libelle, nat_personne.libelle) AS nationalite
            FROM note n
            JOIN personne p ON p.id = n.etudiant_id
            JOIN module m ON m.id = n.module_id
            LEFT JOIN nationalite nat ON nat.code = n.nationalite_code
            LEFT JOIN nationalite nat_personne ON nat_personne.code = p.nationalite_code
            WHERE n.etudiant_id = :etudiant_id
            ORDER BY m.designation ASC
        ');
        $stmt->execute([':etudiant_id' => $etudiant['id']]);
        $notes = $stmt->fetchAll();

        foreach ($notes as $note) {
            $coefficient = (int)$note['coefficient'];
            $noteValue = (float)$note['note'];
            $totalCoefficients += $coefficient;
            $totalPoints += $noteValue * $coefficient;
            if ($noteMin === null || $noteValue < $noteMin) {
                $noteMin = $noteValue;
                $modulesMin = [$note['designation']];
            } elseif ($noteValue === $noteMin) {
                $modulesMin[] = $note['designation'];
            }
            if ($noteMax === null || $noteValue > $noteMax) {
                $noteMax = $noteValue;
                $modulesMax = [$note['designation']];
            } elseif ($noteValue === $noteMax) {
                $modulesMax[] = $note['designation'];
            }
        }
    }
}

$moyenne = $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;

$base = '../';
$titre = 'Bulletin de Notes';
require __DIR__ . '/../includes/header.php';
?>

<style>
    .bl-page {
        max-width: 960px;
        margin: 0 auto;
        padding: 20px 16px 40px;
        font-family: "Segoe UI", Arial, sans-serif;
        color: #1f2937;
    }

    /* Search bar */
    .bl-search {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .bl-search label { font-weight: 600; }
    .bl-search input[type="text"] {
        flex: 1 1 180px;
        padding: 9px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 15px;
    }
    .bl-search input[type="text"]:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,.15);
    }
    .bl-search button {
        padding: 9px 20px;
        background: #2563eb;
        color: #fff;
        border: 0;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
    }
    .bl-search button:hover { background: #1d4ed8; }

    /* Error message */
    .bl-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    /* Student card */
    .bl-card {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .bl-info {
        flex: 1 1 320px;
        display: grid;
        gap: 10px;
    }
    .bl-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .bl-row strong {
        min-width: 130px;
        color: #475569;
    }
    .bl-photo {
        flex: 0 0 auto;
        width: 150px;
        height: 180px;
        border-radius: 10px;
        border: 2px solid #e5e7eb;
        background: #f1f5f9;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bl-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .bl-photo em { color: #94a3b8; font-size: 13px; }

    /* ===== Table: sharp edges, simple shape ===== */
    .bl-table-wrap {
        overflow-x: auto;
        background: #fff;
        border: 1px solid #64748b;
        border-radius: 0;
    }
    .bl-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 15px;
    }
    .bl-table th, .bl-table td {
        padding: 11px 14px;
        text-align: left;
        border: 1px solid #cbd5e1;
        border-radius: 0;
    }
    .bl-table thead th {
        background: #1e3a8a;
        color: #fff;
        font-weight: 600;
        white-space: nowrap;
        border-color: #1e3a8a;
    }
    .bl-table tbody tr:nth-child(even) { background: #f8fafc; }
    .bl-table tbody tr:hover { background: #eff6ff; }
    .bl-table .num { text-align: right; font-variant-numeric: tabular-nums; }

    /* Footer rows */
    .bl-table tfoot th {
        background: #fff;
        text-align: right;
        font-weight: 600;
        color: #334155;
        border-top: 2px solid #64748b;
    }
    .bl-table tfoot tr + tr th,
    .bl-table tfoot tr + tr td { border-top: 1px solid #cbd5e1; }
    .bl-table tfoot td {
        background: #fff;
        text-align: right;
        vertical-align: middle;
    }
    .bl-table tfoot tr:first-child td { border-top: 2px solid #64748b; }

    /* Light orange bubbles for the two sums */
    .bl-bubble {
        display: inline-block;
        min-width: 90px;
        padding: 6px 20px;
        background: #ffedd5;
        color: #c2410c;
        border: 1px solid #fdba74;
        border-radius: 999px;
        font-size: 16px;
        font-weight: 700;
        text-align: center;
        font-variant-numeric: tabular-nums;
    }

    /* Average row (square, blue) */
    .bl-table tfoot tr.bl-moyenne th,
    .bl-table tfoot tr.bl-moyenne td {
        background: #dbeafe;
        color: #1e3a8a;
        font-size: 17px;
        font-weight: 700;
    }

    .bl-ok   { color: #15803d; font-weight: 600; }
    .bl-fail { color: #b91c1c; font-weight: 600; }

    @media (max-width: 600px) {
        .bl-card { justify-content: center; }
        .bl-row strong { min-width: 100%; }
    }
</style>

<div class="bl-page">

    <!-- Search form: always visible, so you can search again after an error -->
    <form method="get" action="afficher_bulletin.php" class="bl-search">
        <label for="numero-etudiant">Numéro d'étudiant :</label>
        <input type="text" name="numero" id="numero-etudiant" value="<?= h($numero) ?>" placeholder="ex: 15" required>
        <button type="submit">Rechercher</button>
    </form>

    <?php if ($erreur): ?>
        <p class="bl-error"><?= h($erreur) ?></p>

    <?php elseif ($etudiant): ?>
        <?php if ($notes && $totalCoefficients > 0): ?>
            <p class="<?= $moyenne >= 10 ? 'bl-ok' : 'bl-fail' ?>" style="padding: 14px 18px; border: 1px solid currentColor; margin-bottom: 20px;">
                <strong><?= $moyenne >= 10 ? 'Admis(e)' : 'Ajourné(e)' ?></strong>
                — Moyenne générale : <?= number_format($moyenne, 2, ',', ' ') ?> / 20
                (seuil de réussite : 10 / 20)
            </p>
        <?php elseif (!$notes): ?>
            <p class="bl-error">Résultat non disponible : aucune note n’est enregistrée pour cet étudiant.</p>
        <?php endif; ?>

        <div class="bl-card">
            <section class="bl-info" aria-label="Informations de l'étudiant">
                <div class="bl-row"><strong>Numéro :</strong> <span><?= h($etudiant['numero']) ?></span></div>
                <div class="bl-row"><strong>Civilité :</strong> <span><?= h($etudiant['civilite'] ?: '—') ?></span></div>
                <div class="bl-row"><strong>Nom / Prénom :</strong> <span><?= h($etudiant['nom_prenom']) ?></span></div>
                <div class="bl-row"><strong>Filière :</strong> <span><?= h($etudiant['filiere']) ?></span></div>
                <div class="bl-row"><strong>Adresse :</strong> <span><?= h($etudiant['adresse']) ?></span></div>
            </section>

            <div id="cadre-photo" class="bl-photo">
                <?php if (!empty($etudiant['photo']) && file_exists(__DIR__ . '/../uploads/' . $etudiant['photo'])): ?>
                    <img src="../uploads/<?= h($etudiant['photo']) ?>" alt="Photo de <?= h($etudiant['nom_prenom']) ?>">
                <?php else: ?>
                    <em>Aucune photo</em>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$notes): ?>
            <p class="bl-error">Aucune note n'est enregistrée pour cet étudiant.</p>
        <?php else: ?>
            <div class="bl-table-wrap">
                <table class="bl-table">
                    <thead>
                        <tr>
                            <th>N°Etudiant</th>
                            <th>Filière</th>
                            <th>Code module</th>
                            <th>Nom module</th>
                            <th>Nationalité</th>
                            <th class="num">Coefficient</th>
                            <th class="num">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notes as $note): ?>
                            <tr>
                                <td><?= h($etudiant['numero']) ?></td>
                                <td><?= h($etudiant['filiere']) ?></td>
                                <td><?= h($note['code_module']) ?></td>
                                <td><?= h($note['designation']) ?></td>
                                <td>
                                    <?php if (preg_match('/^[A-Za-z]{2}$/', (string)($note['nationalite_code'] ?? ''))): ?>
                                        <img class="nationalite-flag" src="https://flagcdn.com/w40/<?= h(strtolower($note['nationalite_code'])) ?>.png" alt="" loading="lazy">
                                    <?php endif; ?>
                                    <?= h($note['nationalite'] ?? '—') ?>
                                </td>
                                <td class="num"><?= h($note['coefficient']) ?></td>
                                <td class="num <?= (float)$note['note'] >= 10 ? 'bl-ok' : 'bl-fail' ?>">
                                    <?= number_format((float)$note['note'], 2, ',', ' ') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5">Somme coefficients</th>
                            <td colspan="2">
                                <span class="bl-bubble"><?= $totalCoefficients ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th colspan="5">Somme Coef*Notes</th>
                            <td colspan="2">
                                <span class="bl-bubble"><?= number_format($totalPoints, 2, ',', ' ') ?></span>
                            </td>
                        </tr>
                        <tr class="bl-moyenne">
                            <th colspan="5">Moyenne</th>
                            <td colspan="2"><?= number_format($moyenne, 2, ',', ' ') ?></td>
                        </tr>
                        <tr>
                            <th colspan="2">Note minimale</th>
                            <td><?= number_format($noteMin, 2, ',', ' ') ?> / 20</td>
                            <th>Module(s)</th>
                            <td colspan="3"><?= h(implode(', ', $modulesMin)) ?></td>
                        </tr>
                        <tr>
                            <th colspan="2">Note maximale</th>
                            <td><?= number_format($noteMax, 2, ',', ' ') ?> / 20</td>
                            <th>Module(s)</th>
                            <td colspan="3"><?= h(implode(', ', $modulesMax)) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
