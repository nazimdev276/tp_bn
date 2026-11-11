<?php
// includes/fonctions.php : petites fonctions utilitaires

require_once __DIR__ . '/operations/enregistrer_avec_id.php';
require_once __DIR__ . '/operations/lire_liste_texte.php';
require_once __DIR__ . '/operations/charger_options_colonne.php';
require_once __DIR__ . '/operations/supprimer_par_id.php';

// Échappe le texte avant de l'afficher en HTML (protège contre XSS)
function h($texte): string
{
    return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8');
}

function calculer_moyenne_ponderee(array $notes): array
{
    $totalCoefficients = 0.0;
    $totalPoints = 0.0;

    foreach ($notes as $note) {
        if (
            !is_array($note)
            || !isset($note['note'], $note['coefficient'])
            || !is_numeric($note['note'])
            || !is_numeric($note['coefficient'])
        ) {
            throw new InvalidArgumentException('Chaque note doit contenir une note et un coefficient numériques.');
        }

        $coefficient = (float)$note['coefficient'];
        $totalCoefficients += $coefficient;
        $totalPoints += (float)$note['note'] * $coefficient;
    }

    return [
        'total_coefficients' => $totalCoefficients,
        'total_points' => $totalPoints,
        'moyenne' => $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : null,
    ];
}

function assurer_schema_note(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS note (
      id          INT AUTO_INCREMENT PRIMARY KEY,
      etudiant_id INT NOT NULL,
      module_id   INT NOT NULL,
      nationalite_code VARCHAR(3) NULL,
      note        DECIMAL(4, 2) NOT NULL,
      date_saisie TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      CONSTRAINT fk_note_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES personne(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
      CONSTRAINT fk_note_module
        FOREIGN KEY (module_id) REFERENCES module(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
      CONSTRAINT fk_note_nationalite
        FOREIGN KEY (nationalite_code) REFERENCES nationalite(code)
        ON UPDATE CASCADE ON DELETE SET NULL,
      CONSTRAINT uk_etudiant_module UNIQUE (etudiant_id, module_id)
    ) ENGINE=InnoDB");

    $stmt = $pdo->query("SHOW COLUMNS FROM note LIKE 'nationalite_code'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE note
            ADD COLUMN nationalite_code VARCHAR(3) NULL AFTER module_id,
            ADD CONSTRAINT fk_note_nationalite
                FOREIGN KEY (nationalite_code) REFERENCES nationalite(code)
                ON UPDATE CASCADE ON DELETE SET NULL");
    }

    $pdo->exec('
        UPDATE note n
        JOIN personne p ON p.id = n.etudiant_id
        SET n.nationalite_code = p.nationalite_code
        WHERE n.nationalite_code IS NULL
    ');
}

// Récupère une liste de cases cochées (checkbox) en gardant seulement les valeurs autorisées
function post_liste(string $nom, array $autorises): array
{
    $recu = $_POST[$nom] ?? [];
    if (!is_array($recu)) {
        return [];
    }
    return array_values(array_intersect($autorises, $recu));
}

// Gère l'upload d'une image.
// Retourne [nom_du_fichier_ou_null, message_erreur_ou_null]
// - Aucun fichier envoyé  => [null, null]
function enregistrer_image(array $fichier): array
{
    if (!isset($fichier['error']) || $fichier['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        return [null, "Erreur pendant l'envoi de l'image."];
    }
    // Taille maximale : 2 Mo
    if ($fichier['size'] > 2 * 1024 * 1024) {
        return [null, "Image trop grande (2 Mo maximum)."];
    }
    // On vérifie le VRAI type du fichier (pas seulement son extension)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($fichier['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
    if (!isset($extensions[$mime]) || getimagesize($fichier['tmp_name']) === false) {
        return [null, "Type d'image refusé (JPG, PNG ou GIF uniquement)."];
    }
    // Nom aléatoire + extension choisie par nous : impossible d'envoyer un fichier .php
    $nom = bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    $destination = __DIR__ . '/../uploads/' . $nom;
    if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
        return [null, "Impossible d'enregistrer l'image (dossier uploads/ accessible en écriture ?)."];
    }
    return [$nom, null];
}
