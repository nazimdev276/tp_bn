<?php
// transport/voiture_liste.php : affiche les modèles de voiture enregistrés
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare('SELECT libelle FROM modele_voiture ORDER BY libelle');
$stmt->execute();
$voitures = $stmt->fetchAll(PDO::FETCH_COLUMN);

$base = '../';
$titre = 'Liste des modèles de voiture';
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$voitures): ?>
    <p>Aucun modèle de voiture enregistré.</p>
<?php else: ?>
    <table class="liste">
        <tr><th>Modèle de voiture</th></tr>
        <?php foreach ($voitures as $v): ?>
            <tr><td><?= h($v) ?></td></tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><a href="voiture.php">Ajouter un modèle de voiture</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
