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
  note        DECIMAL(4, 2) NOT NULL,
  date_saisie TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_note_etudiant
    FOREIGN KEY (etudiant_id) REFERENCES personne(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_note_module
    FOREIGN KEY (module_id) REFERENCES module(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT uk_etudiant_module UNIQUE (etudiant_id, module_id)
) ENGINE=InnoDB;


