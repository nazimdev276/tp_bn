<?php
// bulletin/bulletin.php : formulaire principal de gestion du Bulletin de Notes
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

// Création de la table note si elle n'existe pas encore (idempotent)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS note (
      id          INT AUTO_INCREMENT PRIMARY KEY,
      etudiant_id INT NOT NULL,
      module_id   INT NOT NULL,
      note        DECIMAL(4, 2) NOT NULL,
      date_saisie TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      CONSTRAINT fk_note_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES personne(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
      CONSTRAINT fk_note_module
        FOREIGN KEY (module_id) REFERENCES module(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
      CONSTRAINT uk_etudiant_module UNIQUE (etudiant_id, module_id)
    ) ENGINE=InnoDB");
} catch (Exception $e) {
    // Si la table existe déjà ou contraintes déjà présentes
}

// Chargement des modules depuis la base de données
$stmtModules = $pdo->query('SELECT id, code_module, designation, coefficient, filiere FROM module ORDER BY designation ASC');
$modules = $stmtModules ? $stmtModules->fetchAll() : [];

// Recherche d'un étudiant (par ID ou Numéro)
$termeRecherche = trim((string)($_GET['recherche'] ?? $_GET['id'] ?? $_GET['etudiant_id'] ?? ''));
$erreurRecherche = null;
$succesRecherche = null;
$etudiant = null;
$notesEtudiant = [];
$notesParModule = [];

if ($termeRecherche !== '') {
    if (ctype_digit($termeRecherche)) {
        // Recherche par ID ou par Numéro étudiant
        $stmt = $pdo->prepare('SELECT * FROM personne WHERE id = :id OR numero = :num LIMIT 1');
        $stmt->execute([
            ':id'  => (int)$termeRecherche,
            ':num' => $termeRecherche,
        ]);
        $etudiant = $stmt->fetch() ?: null;
    } else {
        $stmt = $pdo->prepare('SELECT * FROM personne WHERE numero = :num OR nom_prenom LIKE :nom LIMIT 1');
        $stmt->execute([
            ':num' => $termeRecherche,
            ':nom' => '%' . $termeRecherche . '%',
        ]);
        $etudiant = $stmt->fetch() ?: null;
    }

    if ($etudiant) {
        $succesRecherche = "Étudiant trouvé : " . $etudiant['nom_prenom'] . " (N° " . $etudiant['numero'] . ", ID " . $etudiant['id'] . ").";

        // Récupérer les notes existantes de l'étudiant
        $stmtNotes = $pdo->prepare('
            SELECT n.id AS note_id, n.note, n.date_saisie,
                   m.id AS module_id, m.code_module, m.designation, m.coefficient
            FROM note n
            JOIN module m ON m.id = n.module_id
            WHERE n.etudiant_id = :eid
            ORDER BY m.designation ASC
        ');
        $stmtNotes->execute([':eid' => $etudiant['id']]);
        $notesEtudiant = $stmtNotes->fetchAll();

        foreach ($notesEtudiant as $ne) {
            $notesParModule[(int)$ne['module_id']] = $ne;
        }
    } else {
        $erreurRecherche = "Aucun étudiant trouvé pour « " . $termeRecherche . " ». Vérifiez le numéro ou l'identifiant.";
    }
}

// Module sélectionné (via paramètre GET ou note_id ou premier module)
$selectedModuleId = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;
$noteIdParam      = isset($_GET['note_id']) ? (int)$_GET['note_id'] : 0;

if ($noteIdParam > 0 && empty($selectedModuleId)) {
    $stmtFindNote = $pdo->prepare('SELECT etudiant_id, module_id, note FROM note WHERE id = :id');
    $stmtFindNote->execute([':id' => $noteIdParam]);
    $noteData = $stmtFindNote->fetch();
    if ($noteData) {
        $selectedModuleId = (int)$noteData['module_id'];
        if (!$etudiant && !empty($noteData['etudiant_id'])) {
            $stmt = $pdo->prepare('SELECT * FROM personne WHERE id = :id');
            $stmt->execute([':id' => (int)$noteData['etudiant_id']]);
            $etudiant = $stmt->fetch() ?: null;
            if ($etudiant) {
                // Recharger les notes
                $stmtNotes = $pdo->prepare('
                    SELECT n.id AS note_id, n.note, n.date_saisie,
                           m.id AS module_id, m.code_module, m.designation, m.coefficient
                    FROM note n
                    JOIN module m ON m.id = n.module_id
                    WHERE n.etudiant_id = :eid
                    ORDER BY m.designation ASC
                ');
                $stmtNotes->execute([':eid' => $etudiant['id']]);
                $notesEtudiant = $stmtNotes->fetchAll();
                foreach ($notesEtudiant as $ne) {
                    $notesParModule[(int)$ne['module_id']] = $ne;
                }
            }
        }
    }
}

// Calculer les données du module courant
$currentCodeModule = '';
$currentCoeff      = '';
$currentNote       = '';
$currentNoteId     = '';

if ($selectedModuleId > 0) {
    foreach ($modules as $m) {
        if ((int)$m['id'] === $selectedModuleId) {
            $currentCodeModule = $m['code_module'];
            $currentCoeff      = $m['coefficient'];
            break;
        }
    }
    if (isset($notesParModule[$selectedModuleId])) {
        $currentNote   = $notesParModule[$selectedModuleId]['note'];
        $currentNoteId = $notesParModule[$selectedModuleId]['note_id'];
    }
}

$messageFlashSucces  = $_GET['succes'] ?? null;
$messageFlashErreur  = $_GET['erreur'] ?? null;

$base  = '../';
$titre = 'Bulletin de notes';
require __DIR__ . '/../includes/header.php';
?>

<!-- Formulaire de recherche d'un étudiant -->
<form method="get" action="bulletin.php" class="form-recherche" style="margin-bottom: 20px;">
    <label for="recherche-etudiant"><strong>Rechercher un étudiant :</strong></label>
    <input type="text" name="recherche" id="recherche-etudiant" value="<?= h($termeRecherche) ?>" placeholder="ID ou Numéro d'étudiant..." style="width: 240px;" required>
    <button type="submit">Rechercher</button>
    <?php if ($termeRecherche !== ''): ?>
        <a href="bulletin.php" style="margin-left: 12px; font-size: 13.5px;">Réinitialiser</a>
    <?php endif; ?>
</form>

<?php if ($messageFlashSucces): ?>
    <p class="ok"><?= h($messageFlashSucces) ?></p>
<?php endif; ?>

<?php if ($messageFlashErreur): ?>
    <p class="erreur"><?= h($messageFlashErreur) ?></p>
<?php endif; ?>

<?php if ($erreurRecherche): ?>
    <p class="erreur"><?= h($erreurRecherche) ?></p>
<?php elseif ($succesRecherche): ?>
    <p class="ok"><?= h($succesRecherche) ?></p>
<?php endif; ?>

<!-- Formulaire Bulletin de Notes -->
<form id="form-bulletin" method="post" action="enregistrer.php">
    <input type="hidden" name="etudiant_id" id="etudiant_id" value="<?= h($etudiant['id'] ?? '') ?>">
    <input type="hidden" name="note_id" id="note_id" value="<?= h($currentNoteId) ?>">

    <div class="bulletin-student-header">
        <table class="formulaire bulletin-student-info">
        <tr>
            <td>Numéro d'étudiant :</td>
            <td>
                <input type="text" name="numero" id="numero" size="10" maxlength="10" placeholder="ex: 15" value="<?= h($etudiant['numero'] ?? '') ?>" readonly required>
                <?php if (!$etudiant): ?>
                    <span class="petit" style="color: var(--text-muted); margin-left: 8px;">(Recherchez d'abord un étudiant via le bouton « Rechercher »)</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Civilité :</td>
            <td>
                <input type="text" name="civilite" id="civilite" size="15" value="<?= h($etudiant['civilite'] ?? '') ?>" readonly placeholder="Monsieur / Madame">
            </td>
        </tr>
        <tr>
            <td>Nom / Prénom :</td>
            <td>
                <input type="text" name="nom_prenom" id="nom_prenom" size="40" maxlength="80" value="<?= h($etudiant['nom_prenom'] ?? '') ?>" readonly required placeholder="Nom et prénom de l'étudiant">
            </td>
        </tr>
        <tr>
            <td>Filière :</td>
            <td>
                <input type="text" name="filiere" id="filiere" size="20" value="<?= h($etudiant['filiere'] ?? '') ?>" readonly placeholder="ex: 3ISIL">
            </td>
        </tr>
        </table>
        <div id="cadre-photo" class="bulletin-student-photo">
            <?php if (!empty($etudiant['photo']) && file_exists(__DIR__ . '/../uploads/' . $etudiant['photo'])): ?>
                <img id="apercu" src="<?= $base ?>uploads/<?= h($etudiant['photo']) ?>" alt="Photo de <?= h($etudiant['nom_prenom']) ?>" style="display: block;">
            <?php else: ?>
                <img id="apercu" alt="Aperçu de la photo" style="display: none;">
                <div id="photo-placeholder" style="padding: 30px 10px; color: var(--text-muted); font-size: 13px;">
                    <?php if ($etudiant): ?>
                        <em>Aucune photo</em>
                    <?php else: ?>
                        <em>Photo étudiant</em>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <table class="formulaire">
        <tr>
            <td>Module :</td>
            <td>
                <?php if (!$modules): ?>
                    <span class="erreur">Aucun module enregistré. <a href="../module/module.php">En ajouter un</a></span>
                <?php else: ?>
                    <select name="module_id" id="module_id" required>
                        <option value="">— Sélectionner un module —</option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= h($m['id']) ?>"
                                    data-code="<?= h($m['code_module']) ?>"
                                    data-coeff="<?= h($m['coefficient']) ?>"
                                    data-designation="<?= h($m['designation']) ?>"
                                    <?= ($selectedModuleId === (int)$m['id']) ? 'selected' : '' ?>>
                                <?= h($m['designation']) ?> (<?= h($m['code_module']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Code module :</td>
            <td>
                <input type="text" name="code_module" id="code_module" size="15" value="<?= h($currentCodeModule) ?>" readonly placeholder="Code automatique">
            </td>
        </tr>
        <tr>
            <td>Coefficient :</td>
            <td>
                <input type="number" name="coefficient" id="coefficient" size="5" value="<?= h($currentCoeff) ?>" readonly placeholder="Coeff">
            </td>
        </tr>
        <tr>
            <td>Note (/20) :</td>
            <td>
                <input type="number" step="0.01" min="0" max="20" name="note" id="note" size="8" placeholder="ex: 14.50" value="<?= h($currentNote) ?>" required>
                <span class="petit" style="color: var(--text-muted); margin-left: 8px;">(Valeur entre 0 et 20)</span>
                <?php if ($currentNote !== ''): ?>
                    <span id="note-existante-badge" style="display:inline-block; margin-left: 10px; background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Note déjà enregistrée</span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
        <button type="submit" name="action" value="enregistrer" id="btn-enregistrer-note">Enregistrer</button>
        <button type="submit" name="action" value="modifier" formaction="enregistrer.php?action=modifier" id="btn-modifier-note">Modifier</button>
        <button type="submit" name="action" value="supprimer" formaction="supprimer.php" formnovalidate onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette note ?');" id="btn-supprimer-note" style="background: #fef2f2; color: #dc2626; border-color: #fecaca;">Supprimer</button>
        <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Afficher Liste</button>
        <button type="submit" formaction="afficher_bulletin.php" formmethod="get" formnovalidate>Afficher Bulletin</button>
        <button type="reset" id="btn-reset-bulletin">Réinitialiser</button>
    </div>
</form>

<?php if ($etudiant && $notesEtudiant): ?>
    <div style="margin-top: 36px; padding-top: 20px; border-top: 2px dashed var(--border-medium);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
            <h3 style="margin: 0; color: var(--text-heading);">Relevé de notes de l'étudiant : <?= h($etudiant['nom_prenom']) ?></h3>
            <button type="button" onclick="window.print()" style="font-size: 13px; padding: 6px 12px;">🖨️ Imprimer le bulletin</button>
        </div>

        <table class="liste" style="width: 100%; margin-top: 10px;">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Code</th>
                    <th>Coefficient</th>
                    <th>Note / 20</th>
                    <th>Note pondérée</th>
                    <th>Date saisie</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalCoeff = 0;
                $totalPoints = 0;
                foreach ($notesEtudiant as $ne):
                    $noteVal = (float)$ne['note'];
                    $coeffVal = (int)$ne['coefficient'];
                    $ponderee = $noteVal * $coeffVal;
                    $totalCoeff += $coeffVal;
                    $totalPoints += $ponderee;
                ?>
                    <tr>
                        <td><strong><?= h($ne['designation']) ?></strong></td>
                        <td><?= h($ne['code_module']) ?></td>
                        <td><?= h($ne['coefficient']) ?></td>
                        <td>
                            <strong style="color: <?= $noteVal >= 10 ? '#15803d' : '#b91c1c' ?>;">
                                <?= number_format($noteVal, 2, ',', ' ') ?> / 20
                            </strong>
                        </td>
                        <td><?= number_format($ponderee, 2, ',', ' ') ?></td>
                        <td><?= h($ne['date_saisie']) ?></td>
                        <td>
                            <a href="bulletin.php?id=<?= h($etudiant['id']) ?>&module_id=<?= h($ne['module_id']) ?>&note_id=<?= h($ne['note_id']) ?>" style="font-weight: 600; text-decoration: underline;">Modifier</a>
                            &nbsp;|&nbsp;
                            <form method="post" action="supprimer.php" style="display: inline;" onsubmit="return confirm('Supprimer la note du module <?= h($ne['designation']) ?> ?');">
                                <input type="hidden" name="id" value="<?= h($ne['note_id']) ?>">
                                <input type="hidden" name="etudiant_id" value="<?= h($etudiant['id']) ?>">
                                <input type="hidden" name="module_id" value="<?= h($ne['module_id']) ?>">
                                <button type="submit" style="background: none; border: none; color: #dc2626; padding: 0; cursor: pointer; text-decoration: underline; font-size: inherit;">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: var(--bg-subtle); font-weight: bold;">
                    <td colspan="2" style="text-align: right;">Total :</td>
                    <td><?= $totalCoeff ?></td>
                    <td>Moyenne :</td>
                    <td colspan="3" style="font-size: 15px; color: <?= ($totalCoeff > 0 && ($totalPoints / $totalCoeff) >= 10) ? '#15803d' : '#b91c1c' ?>;">
                        <?= $totalCoeff > 0 ? number_format($totalPoints / $totalCoeff, 2, ',', ' ') . ' / 20' : '—' ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php elseif ($etudiant): ?>
    <p style="margin-top: 24px; color: var(--text-muted);">
        <em>Aucune note enregistrée pour le moment pour cet étudiant. Sélectionnez un module ci-dessus pour lui attribuer une note.</em>
    </p>
<?php endif; ?>

<!-- Stockage des notes en JSON pour mise à jour dynamique lors du changement de module -->
<script>
    window.NOTES_ETUDIANT = <?= json_encode($notesParModule) ?>;
</script>
<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
