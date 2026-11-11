<?php
// bulletin/modifier.php : redirection vers bulletin.php avec les données préremplies pour modification
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

assurer_schema_note($pdo);

$noteId     = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$etudiantId = filter_var($_GET['etudiant_id'] ?? $_POST['etudiant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$moduleId   = filter_var($_GET['module_id'] ?? $_POST['module_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($noteId) {
    $stmt = $pdo->prepare('SELECT etudiant_id, module_id FROM note WHERE id = :id');
    $stmt->execute([':id' => $noteId]);
    $note = $stmt->fetch();
    if ($note) {
        header('Location: bulletin.php?mode=id&id=' . urlencode((string)$note['etudiant_id']) . '&module_id=' . urlencode((string)$note['module_id']) . '&note_id=' . urlencode((string)$noteId));
        exit;
    }
}

if ($etudiantId && $moduleId) {
    $stmt = $pdo->prepare('SELECT id FROM note WHERE etudiant_id = :eid AND module_id = :mid');
    $stmt->execute([':eid' => $etudiantId, ':mid' => $moduleId]);
    $note = $stmt->fetch();
    $paramNote = $note ? '&note_id=' . urlencode((string)$note['id']) : '';
    header('Location: bulletin.php?mode=id&id=' . urlencode((string)$etudiantId) . '&module_id=' . urlencode((string)$moduleId) . $paramNote);
    exit;
}

if ($etudiantId) {
    header('Location: bulletin.php?mode=id&id=' . urlencode((string)$etudiantId));
    exit;
}

header('Location: bulletin.php');
exit;
