<?php
// module/module.php : formulaire de gestion des modules
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

// Chargement des filières avec fallback sur la liste définie
$stmt = $pdo->prepare('SELECT nom FROM filiere ORDER BY id');
$stmt->execute();
$filieres = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (!$filieres) {
    $filieres = $FILIERES;
}

$modeRecherche = ($_GET['mode'] ?? 'id') === 'code' ? 'code' : 'id';
$idRecherche = trim((string)($_GET['id'] ?? ''));
$module = null;
$messageRecherche = null;

if ($idRecherche !== '') {
    if ($modeRecherche === 'code') {
        $stmt = $pdo->prepare('SELECT * FROM module WHERE code_module = :code ORDER BY id LIMIT 1');
        $stmt->execute([':code' => $idRecherche]);
        $module = $stmt->fetch() ?: null;
        if ($module) {
            $messageRecherche = "Module trouvé (code {$idRecherche}, ID {$module['id']}) : vous pouvez le modifier ou le supprimer.";
        } else {
            $messageRecherche = "Aucun module trouvé pour le code {$idRecherche}. Remplissez le formulaire pour en créer un.";
        }
    } else {
        $idValide = filter_var($idRecherche, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($idValide !== false) {
            $stmt = $pdo->prepare('SELECT * FROM module WHERE id = :id');
            $stmt->execute([':id' => $idValide]);
            $module = $stmt->fetch() ?: null;
            if ($module) {
                $messageRecherche = "Module trouvé (ID {$idValide}) : vous pouvez le modifier ou le supprimer.";
            } else {
                $messageRecherche = "Aucun module trouvé pour l'ID {$idValide}. Remplissez le formulaire pour le créer avec cet ID.";
            }
        }
    }
}

$idCourant            = $module['id'] ?? ($modeRecherche === 'id' ? $idRecherche : '');
$codeModuleCourant    = $module['code_module'] ?? '';
$designationCourante  = $module['designation'] ?? '';
$coefficientCourant   = $module['coefficient'] ?? 1;
$volumeHoraireCourant = $module['volume_horaire'] ?? 45;
$filiereCourante      = $module['filiere'] ?? '';

$base = '../';
$titre = 'Gestion des modules';
require __DIR__ . '/../includes/header.php';
?>

<form method="get" action="module.php" class="form-recherche">
    <label for="mode-recherche">Rechercher par :</label>
    <select name="mode" id="mode-recherche">
        <option value="id" <?= $modeRecherche === 'id' ? 'selected' : '' ?>>ID</option>
        <option value="code" <?= $modeRecherche === 'code' ? 'selected' : '' ?>>Code module</option>
    </select>
    <input type="text" name="id" id="recherche-id" value="<?= h($idRecherche) ?>" placeholder="<?= $modeRecherche === 'id' ? 'ex: 1' : 'ex: BDD' ?>">
    <button type="submit">Rechercher</button>
    <?php if ($idRecherche !== ''): ?>
        <a href="module.php" style="margin-left: 10px;">Réinitialiser</a>
    <?php endif; ?>
</form>

<?php if ($messageRecherche): ?>
    <p class="<?= $module ? 'ok' : '' ?>"><?= h($messageRecherche) ?></p>
<?php endif; ?>

<form id="form-module" method="post" action="enregistrer.php">
    <table class="formulaire">
        <tr>
            <td>ID :</td>
            <td>
                <input type="number" name="id" id="module-id" min="1" size="10" placeholder="Auto ou spécifier" value="<?= h($idCourant) ?>">
                <span class="petit" style="color: var(--text-muted); font-size: 12px; margin-left: 8px;">(Laisser vide pour auto-incrémentation lors de l'ajout)</span>
            </td>
        </tr>
        <tr>
            <td>Code module :</td>
            <td>
                <input type="text" name="code_module" id="code_module" maxlength="20" size="15" placeholder="ex: BDD, PAW" value="<?= h($codeModuleCourant) ?>" required>
            </td>
        </tr>
        <tr>
            <td>Désignation :</td>
            <td>
                <input type="text" name="designation" id="designation" maxlength="100" size="40" placeholder="ex: Bases de données" value="<?= h($designationCourante) ?>" required>
            </td>
        </tr>
        <tr>
            <td>Coefficient :</td>
            <td>
                <input type="number" name="coefficient" id="coefficient" min="1" max="20" size="5" value="<?= h($coefficientCourant) ?>" required>
            </td>
        </tr>
        <tr>
            <td>Volume horaire (heures) :</td>
            <td>
                <input type="number" name="volume_horaire" id="volume_horaire" min="1" max="500" size="5" placeholder="ex: 45" value="<?= h($volumeHoraireCourant) ?>" required>
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
            </td>
        </tr>
    </table>

    <button type="submit" name="action" value="ajouter">Ajouter</button>
    <button type="submit" name="action" value="modifier" formaction="enregistrer.php?action=modifier">Modifier</button>
    <button type="submit" name="action" value="supprimer" formaction="supprimer.php" formnovalidate onclick="return confirm('Supprimer ce module ?');">Supprimer</button>
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
    <button type="reset">Réinitialiser</button>
</form>

<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
