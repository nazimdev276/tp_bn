<?php
// tables.php : sous-menu de gestion des tables
require_once __DIR__ . '/includes/fonctions.php';

$base  = '';
$titre = 'Sous-menu Tables';
require __DIR__ . '/includes/header.php';
?>

<p>Sélectionnez une table à gérer :</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin: 24px 0;">
    <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 style="margin: 0 0 8px 0; color: var(--text-heading); font-size: 16px;">Nationalité</h3>
            <p style="margin: 0 0 16px 0; color: var(--text-muted); font-size: 13.5px;">Gérer les codes et libellés des nationalités (ex: DZ, FR, BE...).</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="nationalite/nationalite.php" class="btn-link" style="display:inline-block; padding: 8px 14px; background: var(--primary); color: white; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Formulaire</a>
            <a href="nationalite/liste.php" style="display:inline-block; padding: 8px 14px; background: white; border: 1px solid var(--border-medium); border-radius: var(--radius-md); text-decoration: none;">Affichage Liste</a>
        </div>
    </div>

    <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 style="margin: 0 0 8px 0; color: var(--text-heading); font-size: 16px;">Sports</h3>
            <p style="margin: 0 0 16px 0; color: var(--text-muted); font-size: 13.5px;">Gérer la liste des sports proposés dans les formulaires.</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="sport/sport.php" style="display:inline-block; padding: 8px 14px; background: var(--primary); color: white; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Formulaire</a>
            <a href="sport/liste.php" style="display:inline-block; padding: 8px 14px; background: white; border: 1px solid var(--border-medium); border-radius: var(--radius-md); text-decoration: none;">Affichage Liste</a>
        </div>
    </div>

    <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 style="margin: 0 0 8px 0; color: var(--text-heading); font-size: 16px;">Filières</h3>
            <p style="margin: 0 0 16px 0; color: var(--text-muted); font-size: 13.5px;">Gérer les filières (ID et nom) : TC, 2SC, 3ISIL, 3SI, M1, M2ISI...</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="filiere/filiere.php" style="display:inline-block; padding: 8px 14px; background: var(--primary); color: white; border-radius: var(--radius-md); font-weight: 600; text-decoration: none;">Formulaire</a>
            <a href="filiere/liste.php" style="display:inline-block; padding: 8px 14px; background: white; border: 1px solid var(--border-medium); border-radius: var(--radius-md); text-decoration: none;">Affichage Liste</a>
        </div>
    </div>
</div>

<hr>
<p style="font-size: 13px; color: var(--text-muted);">Autres tables : <a href="voyage/voyage.php">Voyages (Voyager)</a> | <a href="transport/transport.php">Transports</a> | <a href="transport/voiture.php">Modèles de voiture</a></p>

<?php require __DIR__ . '/includes/footer.php'; ?>
