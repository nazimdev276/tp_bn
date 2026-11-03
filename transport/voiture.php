<?php
// transport/voiture.php : formulaire de saisie d'un modèle de voiture
require_once __DIR__ . '/../includes/fonctions.php';
$base = '../';
$titre = 'Gestion des modèles de voiture';
require __DIR__ . '/../includes/header.php';
?>
<form id="form-voiture" method="post" action="voiture_enregistrer.php">
    <table class="formulaire">
        <tr>
            <td>Modèle de voiture</td>
            <td><input type="text" name="libelle" id="voiture-libelle" maxlength="50" size="30" placeholder="ex. SMV, Fiat, Peugeot" required></td>
        </tr>
    </table>
    <button type="submit">Enregistrer</button>
    <button type="submit" formaction="voiture_liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
</form>
<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
