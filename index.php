<?php
// index.php : page d'accueil
require_once __DIR__ . '/includes/fonctions.php';
$base  = '';
$titre = 'Accueil';
require __DIR__ . '/includes/header.php';
?>
<p>Bienvenue sur l'application de gestion universitaire TP01 – PAW.</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin: 24px 0;">
    <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 20px;">
        <h3 style="margin: 0 0 8px 0; color: var(--text-heading);">Étudiants</h3>
        <p style="margin: 0 0 14px 0; color: var(--text-muted); font-size: 13.5px;">Formulaire complet des étudiants avec choix de filière, recherche par ID, affichage et gestion.</p>
        <a href="personne/formulaire.php">Accéder au formulaire</a> | <a href="personne/liste.php">Liste</a>
    </div>

    <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 20px;">
        <h3 style="margin: 0 0 8px 0; color: var(--text-heading);">Enseignants</h3>
        <p style="margin: 0 0 14px 0; color: var(--text-muted); font-size: 13.5px;">Gestion des enseignants (filière, modules enseignés, grade, spécialité) et consultation des étudiants par classe.</p>
        <a href="enseignant/enseignant.php">Accéder au formulaire</a> | <a href="enseignant/liste.php">Liste</a> | <a href="enseignant/etudiants.php">Liste Des Etudiants</a>
    </div>

    <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 20px;">
        <h3 style="margin: 0 0 8px 0; color: var(--text-heading);">Modules</h3>
        <p style="margin: 0 0 14px 0; color: var(--text-muted); font-size: 13.5px;">Gestion des modules d'enseignement (ID, code module, désignation, coefficient, volume horaire, filière).</p>
        <a href="module/module.php">Accéder au formulaire</a> | <a href="module/liste.php">Liste</a>
    </div>

    <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 20px;">
        <h3 style="margin: 0 0 8px 0; color: var(--text-heading);">Voyage</h3>
        <p style="margin: 0 0 14px 0; color: var(--text-muted); font-size: 13.5px;">Gestion des voyages étudiants (méthodes de voyage : Train, Car, Flight, accompagnement, description et recherche par ID).</p>
        <a href="voyage/voyage.php">Accéder au formulaire</a> | <a href="voyage/liste.php">Liste</a>
    </div>

    <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 20px;">
        <h3 style="margin: 0 0 8px 0; color: var(--text-heading);">Bulletin de Notes</h3>
        <p style="margin: 0 0 14px 0; color: var(--text-muted); font-size: 13.5px;">Gestion des notes des étudiants (recherche étudiant avec photo, choix module avec code et coefficient, enregistrement, modification et suppression).</p>
        <a href="bulletin/bulletin.php">Accéder au formulaire</a> | <a href="bulletin/liste.php">Liste</a>
    </div>

    <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 20px;">
        <h3 style="margin: 0 0 8px 0; color: var(--text-heading);">Tables</h3>
        <p style="margin: 0 0 14px 0; color: var(--text-muted); font-size: 13.5px;">Gestion des tables de référence : Nationalités, Sports, et Filières.</p>
        <a href="tables.php">Ouvrir le sous-menu</a>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
