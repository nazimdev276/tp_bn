-- ============================================================
-- TP01 - PAW : base de données
-- Importer ce fichier dans phpMyAdmin (onglet "Importer")
-- ============================================================
CREATE DATABASE IF NOT EXISTS tp01_paw
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE tp01_paw;

-- Table des nationalités : code X(3) et libellé X(40)
CREATE TABLE IF NOT EXISTS nationalite (
  code    VARCHAR(3)  NOT NULL PRIMARY KEY,
  libelle VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

-- Une dizaine de nationalités de départ
INSERT IGNORE INTO nationalite (code, libelle) VALUES
  ('DZ', 'ALGERIE'),
  ('FR', 'FRANCE'),
  ('BE', 'BELGIQUE'),
  ('TN', 'TUNISIE'),
  ('MA', 'MAROC'),
  ('DE', 'ALLEMAGNE'),
  ('IT', 'ITALIE'),
  ('ES', 'ESPAGNE'),
  ('CA', 'CANADA'),
  ('US', 'ETATS-UNIS');

-- Table des moyens de transport
CREATE TABLE IF NOT EXISTS moyen_transport (
  libelle VARCHAR(20) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

INSERT IGNORE INTO moyen_transport (libelle) VALUES
  ('Bus'),
  ('Avion'),
  ('Bateau'),
  ('Train');

-- Table des sports
CREATE TABLE IF NOT EXISTS sport (
  libelle VARCHAR(40) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

INSERT IGNORE INTO sport (libelle) VALUES
  ('Football'),
  ('Basketball'),
  ('Tennis'),
  ('Natation'),
  ('Athlétisme');

-- Table des modèles de voiture
CREATE TABLE IF NOT EXISTS modele_voiture (
  libelle VARCHAR(50) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

INSERT IGNORE INTO modele_voiture (libelle) VALUES
  ('SMV'),
  ('Fiat'),
  ('Peugeot');

-- Table des personnes
-- plateformes, applications, activites, modules : valeurs séparées par des virgules
CREATE TABLE IF NOT EXISTS personne (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  numero           VARCHAR(10)  NOT NULL,
  civilite         VARCHAR(15)  NOT NULL,
  nom_prenom       VARCHAR(80)  NOT NULL,
  adresse          VARCHAR(150) NOT NULL,
  code_postal      VARCHAR(10)  NOT NULL,
  localite         VARCHAR(60)  NOT NULL,
  pays             VARCHAR(40)  NOT NULL,
  plateformes      VARCHAR(100) DEFAULT '',
  applications     VARCHAR(255) DEFAULT '',
  transport        VARCHAR(20)  NOT NULL DEFAULT '',
  sport            VARCHAR(40)  NOT NULL DEFAULT '',
  activites        VARCHAR(100) NOT NULL DEFAULT '',
  modules          VARCHAR(100) NOT NULL DEFAULT '',
  groupe_sanguin   VARCHAR(10)  NOT NULL DEFAULT '',
  modele_voiture   VARCHAR(150) NOT NULL DEFAULT '',
  filiere          VARCHAR(30)  NOT NULL DEFAULT '',
  nationalite_code VARCHAR(3)   NOT NULL,
  photo            VARCHAR(100) DEFAULT NULL,
  -- Relation : personne.nationalite_code -> nationalite.code
  CONSTRAINT fk_personne_nationalite
    FOREIGN KEY (nationalite_code) REFERENCES nationalite(code)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Mise à niveau des bases déjà créées
ALTER TABLE personne
  ADD COLUMN IF NOT EXISTS transport VARCHAR(20) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS sport VARCHAR(40) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS activites VARCHAR(100) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS modules VARCHAR(100) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS groupe_sanguin VARCHAR(10) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS modele_voiture VARCHAR(50) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS filiere VARCHAR(30) NOT NULL DEFAULT '';

-- Table des filières
CREATE TABLE IF NOT EXISTS filiere (
  id  INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT IGNORE INTO filiere (id, nom) VALUES
  (1, 'TC'),
  (2, '2SC'),
  (3, '3ISIL'),
  (4, '3SI'),
  (5, 'M1'),
  (6, 'M2ISI'),
  (7, 'M2WIC'),
  (8, 'M2RSSI'),
  (9, '1ING'),
  (10, '2ING');

-- Table des enseignants
CREATE TABLE IF NOT EXISTS enseignant (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  numero         VARCHAR(10)  NOT NULL,
  civilite       VARCHAR(15)  NOT NULL,
  nom_prenom     VARCHAR(80)  NOT NULL,
  adresse        VARCHAR(150) NOT NULL,
  date_naissance DATE         DEFAULT NULL,
  lieu_naissance VARCHAR(60)  NOT NULL DEFAULT '',
  pays           VARCHAR(40)  NOT NULL,
  grade          VARCHAR(30)  NOT NULL,
  specialite     VARCHAR(80)  NOT NULL DEFAULT '',
  filiere        VARCHAR(50)  NOT NULL DEFAULT '',
  modules        VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

ALTER TABLE enseignant
  ADD COLUMN IF NOT EXISTS filiere VARCHAR(50) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS modules VARCHAR(255) NOT NULL DEFAULT '';

-- Table des modules
CREATE TABLE IF NOT EXISTS module (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  code_module    VARCHAR(20)  NOT NULL,
  designation    VARCHAR(100) NOT NULL,
  coefficient    INT          NOT NULL DEFAULT 1,
  volume_horaire INT          NOT NULL DEFAULT 0,
  filiere        VARCHAR(50)  NOT NULL
) ENGINE=InnoDB;

-- Table des voyages (Voyager)
CREATE TABLE IF NOT EXISTS voyager (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  etudiant_id      INT DEFAULT NULL,
  numero           VARCHAR(10)  NOT NULL,
  civilite         VARCHAR(15)  NOT NULL,
  nom_prenom       VARCHAR(80)  NOT NULL,
  filiere          VARCHAR(30)  NOT NULL DEFAULT '',
  adresse          VARCHAR(150) NOT NULL,
  code_postal      VARCHAR(10)  NOT NULL DEFAULT '',
  localite         VARCHAR(60)  NOT NULL DEFAULT '',
  pays             VARCHAR(40)  NOT NULL,
  plateformes      VARCHAR(100) DEFAULT '',
  applications     VARCHAR(255) DEFAULT '',
  transport        VARCHAR(20)  NOT NULL DEFAULT '',
  sport            VARCHAR(40)  NOT NULL DEFAULT '',
  activites        VARCHAR(100) NOT NULL DEFAULT '',
  modules          VARCHAR(100) NOT NULL DEFAULT '',
  groupe_sanguin   VARCHAR(10)  NOT NULL DEFAULT '',
  modele_voiture   VARCHAR(150) NOT NULL DEFAULT '',
  nationalite_code VARCHAR(3)   DEFAULT NULL,
  photo            VARCHAR(100) DEFAULT NULL,
  travel_methodes  VARCHAR(100) NOT NULL DEFAULT '',
  travel_with      VARCHAR(50)  NOT NULL DEFAULT '',
  description      TEXT         DEFAULT NULL,
  CONSTRAINT fk_voyager_nationalite
    FOREIGN KEY (nationalite_code) REFERENCES nationalite(code)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_voyager_etudiant
    FOREIGN KEY (etudiant_id) REFERENCES personne(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW voyage AS SELECT * FROM voyager;

-- Table des notes (Bulletin de notes)
CREATE TABLE IF NOT EXISTS note (
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
) ENGINE=InnoDB;

-- Jeu de démonstration du bulletin (ajouté uniquement si les numéros/codes n'existent pas)
INSERT INTO personne
  (numero, civilite, nom_prenom, adresse, code_postal, localite, pays, filiere, nationalite_code)
SELECT demo.numero, demo.civilite, demo.nom_prenom, 'Adresse de démonstration', '00000',
     'Sidi Bel Abbès', 'Algérie', demo.filiere, 'DZ'
FROM (
  SELECT '15' AS numero, 'Monsieur' AS civilite, 'Démo Bulletin 15' AS nom_prenom, '3ISIL' AS filiere
  UNION ALL SELECT '16', 'Madame', 'Démo Bulletin 16', '3ISIL'
  UNION ALL SELECT '17', 'Monsieur', 'Démo Bulletin 17', '3ISIL'
  UNION ALL SELECT '18', 'Madame', 'Démo Bulletin 18', '2SC'
  UNION ALL SELECT '19', 'Monsieur', 'Démo Bulletin 19', '3SI'
) AS demo
WHERE NOT EXISTS (
  SELECT 1 FROM personne p WHERE p.numero = demo.numero
);

INSERT INTO module (code_module, designation, coefficient, volume_horaire, filiere)
SELECT demo.code_module, demo.designation, demo.coefficient, 45, demo.filiere
FROM (
  SELECT 'DEMO-BDD' AS code_module, 'Base de données' AS designation, 4 AS coefficient, '3ISIL' AS filiere
  UNION ALL SELECT 'DEMO-POO', 'Programmation orientée objet', 3, '3ISIL'
  UNION ALL SELECT 'DEMO-ALG', 'Algorithmique', 3, '3ISIL'
  UNION ALL SELECT 'DEMO-SYS', 'Systèmes', 3, '3ISIL'
  UNION ALL SELECT 'DEMO-WEB', 'Développement web', 4, '3ISIL'
  UNION ALL SELECT 'DEMO-MATH', 'Mathématiques', 3, '2SC'
  UNION ALL SELECT 'DEMO-RES', 'Réseaux', 4, '3SI'
) AS demo
WHERE NOT EXISTS (
  SELECT 1 FROM module m WHERE m.code_module = demo.code_module
);

INSERT IGNORE INTO note (etudiant_id, module_id, nationalite_code, note)
SELECT p.id, m.id, p.nationalite_code, demo.note
FROM (
  SELECT '15' AS numero, 'Démo Bulletin 15' AS nom_prenom, 'DEMO-BDD' AS code_module, 15.00 AS note
  UNION ALL SELECT '15', 'Démo Bulletin 15', 'DEMO-POO', 16.00
  UNION ALL SELECT '15', 'Démo Bulletin 15', 'DEMO-ALG', 15.00
  UNION ALL SELECT '15', 'Démo Bulletin 15', 'DEMO-SYS', 17.00
  UNION ALL SELECT '15', 'Démo Bulletin 15', 'DEMO-WEB', 14.00
  UNION ALL SELECT '16', 'Démo Bulletin 16', 'DEMO-BDD', 12.00
  UNION ALL SELECT '16', 'Démo Bulletin 16', 'DEMO-POO', 8.00
  UNION ALL SELECT '16', 'Démo Bulletin 16', 'DEMO-WEB', 17.00
  UNION ALL SELECT '17', 'Démo Bulletin 17', 'DEMO-BDD', 9.00
  UNION ALL SELECT '17', 'Démo Bulletin 17', 'DEMO-ALG', 14.00
  UNION ALL SELECT '18', 'Démo Bulletin 18', 'DEMO-MATH', 18.00
  UNION ALL SELECT '19', 'Démo Bulletin 19', 'DEMO-RES', 11.00
) AS demo
JOIN personne p ON p.numero = demo.numero AND p.nom_prenom = demo.nom_prenom
JOIN module m ON m.code_module = demo.code_module;

-- Jeu de démonstration des PV : 4 étudiants par filière, deux modules chacun
DROP TEMPORARY TABLE IF EXISTS demo_pv_students;
CREATE TEMPORARY TABLE demo_pv_students (
  numero VARCHAR(10) PRIMARY KEY,
  civilite VARCHAR(15) NOT NULL,
  nom_prenom VARCHAR(80) NOT NULL,
  filiere VARCHAR(50) NOT NULL,
  note_a DECIMAL(4, 2) NOT NULL,
  note_b DECIMAL(4, 2) NOT NULL
) ENGINE=MEMORY;

INSERT INTO demo_pv_students (numero, civilite, nom_prenom, filiere, note_a, note_b) VALUES
  ('980001', 'Monsieur', 'Démo PV TC Homme 1', 'TC', 9.51, 10.07),
  ('980002', 'Madame', 'Démo PV TC Femme 1', 'TC', 14.99, 15.69),
  ('980003', 'Monsieur', 'Démo PV TC Homme 2', 'TC', 12.00, 13.00),
  ('980004', 'Mademoiselle', 'Démo PV TC Femme 2', 'TC', 17.00, 11.00),
  ('980005', 'Monsieur', 'Démo PV 2SC Homme 1', '2SC', 8.00, 9.00),
  ('980006', 'Madame', 'Démo PV 2SC Femme 1', '2SC', 14.00, 16.00),
  ('980007', 'Monsieur', 'Démo PV 2SC Homme 2', '2SC', 11.00, 12.00),
  ('980008', 'Mademoiselle', 'Démo PV 2SC Femme 2', '2SC', 18.00, 17.00),
  ('980009', 'Monsieur', 'Démo PV 3ISIL Homme 1', '3ISIL', 10.00, 10.00),
  ('980010', 'Madame', 'Démo PV 3ISIL Femme 1', '3ISIL', 19.00, 18.00),
  ('980011', 'Monsieur', 'Démo PV 3ISIL Homme 2', '3ISIL', 6.00, 12.00),
  ('980012', 'Mademoiselle', 'Démo PV 3ISIL Femme 2', '3ISIL', 13.00, 15.00),
  ('980013', 'Monsieur', 'Démo PV 3SI Homme 1', '3SI', 7.00, 10.00),
  ('980014', 'Madame', 'Démo PV 3SI Femme 1', '3SI', 15.00, 15.00),
  ('980015', 'Monsieur', 'Démo PV 3SI Homme 2', '3SI', 11.00, 14.00),
  ('980016', 'Mademoiselle', 'Démo PV 3SI Femme 2', '3SI', 16.00, 13.00),
  ('980017', 'Monsieur', 'Démo PV M1 Homme 1', 'M1', 9.00, 13.00),
  ('980018', 'Madame', 'Démo PV M1 Femme 1', 'M1', 16.00, 17.00),
  ('980019', 'Monsieur', 'Démo PV M1 Homme 2', 'M1', 12.00, 9.00),
  ('980020', 'Mademoiselle', 'Démo PV M1 Femme 2', 'M1', 18.00, 14.00),
  ('980021', 'Monsieur', 'Démo PV M2RSSI Homme 1', 'M2RSSI', 8.00, 11.00),
  ('980022', 'Madame', 'Démo PV M2RSSI Femme 1', 'M2RSSI', 17.00, 18.00),
  ('980023', 'Monsieur', 'Démo PV M2RSSI Homme 2', 'M2RSSI', 14.00, 12.00),
  ('980024', 'Mademoiselle', 'Démo PV M2RSSI Femme 2', 'M2RSSI', 10.00, 16.00),
  ('980025', 'Monsieur', 'Démo PV M2WIC Homme 1', 'M2WIC', 11.00, 9.00),
  ('980026', 'Madame', 'Démo PV M2WIC Femme 1', 'M2WIC', 18.00, 16.00),
  ('980027', 'Monsieur', 'Démo PV M2WIC Homme 2', 'M2WIC', 13.00, 15.00),
  ('980028', 'Mademoiselle', 'Démo PV M2WIC Femme 2', 'M2WIC', 7.00, 12.00),
  ('980029', 'Monsieur', 'Démo PV M2ISI Homme 1', 'M2ISI', 14.00, 15.00),
  ('980030', 'Madame', 'Démo PV M2ISI Femme 1', 'M2ISI', 13.00, 17.00),
  ('980031', 'Monsieur', 'Démo PV M2ISI Homme 2', 'M2ISI', 16.00, 12.00),
  ('980032', 'Mademoiselle', 'Démo PV M2ISI Femme 2', 'M2ISI', 11.00, 10.00),
  ('980033', 'Monsieur', 'Démo PV 1ING Homme 1', '1ING', 9.00, 10.00),
  ('980034', 'Madame', 'Démo PV 1ING Femme 1', '1ING', 15.00, 18.00),
  ('980035', 'Monsieur', 'Démo PV 1ING Homme 2', '1ING', 13.00, 11.00),
  ('980036', 'Mademoiselle', 'Démo PV 1ING Femme 2', '1ING', 17.00, 16.00),
  ('980037', 'Monsieur', 'Démo PV 2ING Homme 1', '2ING', 12.00, 14.00),
  ('980038', 'Madame', 'Démo PV 2ING Femme 1', '2ING', 18.00, 19.00),
  ('980039', 'Monsieur', 'Démo PV 2ING Homme 2', '2ING', 10.00, 8.00),
  ('980040', 'Mademoiselle', 'Démo PV 2ING Femme 2', '2ING', 16.00, 15.00);

INSERT INTO personne
  (numero, civilite, nom_prenom, adresse, code_postal, localite, pays, filiere, nationalite_code)
SELECT d.numero, d.civilite, d.nom_prenom, 'Adresse de démonstration PV', '00000',
       'Sidi Bel Abbès', 'Algérie', d.filiere, 'DZ'
FROM demo_pv_students d
WHERE NOT EXISTS (
  SELECT 1 FROM personne p WHERE p.numero = d.numero
);

INSERT INTO module (code_module, designation, coefficient, volume_horaire, filiere)
SELECT d.code_module, d.designation, d.coefficient, 45, d.filiere
FROM (
  SELECT CONCAT('DEMO-PV-', filiere, '-A') AS code_module,
         CONCAT('PV Démo ', filiere, ' — Module A') AS designation,
         4 AS coefficient, filiere
  FROM (SELECT DISTINCT filiere FROM demo_pv_students) filieres
  UNION ALL
  SELECT CONCAT('DEMO-PV-', filiere, '-B'),
         CONCAT('PV Démo ', filiere, ' — Module B'),
         3, filiere
  FROM (SELECT DISTINCT filiere FROM demo_pv_students) filieres
) d
WHERE NOT EXISTS (
  SELECT 1 FROM module m
  WHERE m.code_module = d.code_module AND m.filiere = d.filiere
);

INSERT IGNORE INTO note (etudiant_id, module_id, nationalite_code, note)
SELECT p.id, m.id, p.nationalite_code, d.note_a
FROM demo_pv_students d
JOIN personne p ON p.numero = d.numero AND p.nom_prenom = d.nom_prenom
JOIN module m ON m.code_module = CONCAT('DEMO-PV-', d.filiere, '-A') AND m.filiere = d.filiere
UNION ALL
SELECT p.id, m.id, p.nationalite_code, d.note_b
FROM demo_pv_students d
JOIN personne p ON p.numero = d.numero AND p.nom_prenom = d.nom_prenom
JOIN module m ON m.code_module = CONCAT('DEMO-PV-', d.filiere, '-B') AND m.filiere = d.filiere;

DROP TEMPORARY TABLE demo_pv_students;
