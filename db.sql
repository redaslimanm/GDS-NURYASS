-- ============================================
-- GDS - STOCK MANAGEMENT SYSTEM FOR NURYASS
-- Database Schema - Improved Version
-- ============================================

-- ==========================
-- TABLE : users
-- ==========================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'caissier') NOT NULL DEFAULT 'caissier',
    actif TINYINT(1) DEFAULT 1,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================
-- TABLE : clients
-- ==========================
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_client ENUM('personne', 'entreprise') NOT NULL,
    
    -- Informations pour personnes physiques
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NULL,
    cin VARCHAR(20) NULL,
    
    -- Informations pour entreprises
    nom_entreprise VARCHAR(150) NULL,
    patente VARCHAR(50) NULL,
    
    -- Informations communes
    adresse VARCHAR(255) NOT NULL,
    telephone VARCHAR(30),
    email VARCHAR(150),
    credit_max DECIMAL(10,2) NOT NULL DEFAULT 5000.00,
    
    actif TINYINT(1) DEFAULT 1,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type_client (type_client),
    INDEX idx_cin (cin),
    INDEX idx_patente (patente),
    INDEX idx_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================
-- TABLE : types_produits
-- ==========================
CREATE TABLE types_produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_type VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_nom_type (nom_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================
-- TABLE : couleurs
-- ==========================
CREATE TABLE couleurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_couleur VARCHAR(50) NOT NULL UNIQUE,
    code_couleur VARCHAR(7) NULL COMMENT 'Code hexadécimal pour affichage',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_nom_couleur (nom_couleur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================
-- TABLE : produits
-- ==========================
CREATE TABLE produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    couleur_id INT NOT NULL,
    nom_produit VARCHAR(150) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    stock_minimum INT DEFAULT 0 COMMENT 'Seuil d''alerte pour stock faible',
    description TEXT,
    actif TINYINT(1) DEFAULT 1,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (type_id) REFERENCES types_produits(id) ON DELETE RESTRICT,
    FOREIGN KEY (couleur_id) REFERENCES couleurs(id) ON DELETE RESTRICT,
    
    INDEX idx_type_id (type_id),
    INDEX idx_couleur_id (couleur_id),
    INDEX idx_nom_produit (nom_produit),
    INDEX idx_stock (stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================
-- TABLE : bons
-- ==========================
CREATE TABLE bons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_bon VARCHAR(50) NOT NULL UNIQUE,
    client_id INT NOT NULL,
    user_id INT NOT NULL COMMENT 'Utilisateur qui a créé le bon',
    type_bon ENUM('entree', 'sortie') NOT NULL,
    statut_paiement ENUM('paye', 'non_paye') NOT NULL DEFAULT 'non_paye',
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    date_bon TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_numero_bon (numero_bon),
    INDEX idx_client_id (client_id),
    INDEX idx_user_id (user_id),
    INDEX idx_type_bon (type_bon),
    INDEX idx_statut_paiement (statut_paiement),
    INDEX idx_date_bon (date_bon)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================
-- TABLE : bons_details
-- ==========================
CREATE TABLE bons_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bon_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    sous_total DECIMAL(10,2) NOT NULL,
    
    FOREIGN KEY (bon_id) REFERENCES bons(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE RESTRICT,
    
    INDEX idx_bon_id (bon_id),
    INDEX idx_produit_id (produit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================
-- TABLE : credits
-- ==========================
CREATE TABLE credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL UNIQUE,
    montant_actuel DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    max_montant DECIMAL(10,2) NOT NULL DEFAULT 5000.00,
    statut ENUM('actif', 'bloque') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    
    INDEX idx_client_id (client_id),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================
-- TABLE : credits_transactions
-- ==========================
CREATE TABLE credits_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    credit_id INT NOT NULL,
    client_id INT NOT NULL,
    bon_id INT NULL COMMENT 'Lien vers le bon si crédit lié à une vente',
    type_transaction ENUM('ajout', 'paiement', 'annulation') NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    montant_avant DECIMAL(10,2) NOT NULL,
    montant_apres DECIMAL(10,2) NOT NULL,
    user_id INT NOT NULL COMMENT 'Utilisateur qui a effectué la transaction',
    details TEXT,
    date_transaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (credit_id) REFERENCES credits(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (bon_id) REFERENCES bons(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_credit_id (credit_id),
    INDEX idx_client_id (client_id),
    INDEX idx_bon_id (bon_id),
    INDEX idx_type_transaction (type_transaction),
    INDEX idx_date_transaction (date_transaction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================
-- TABLE : factures
-- ==========================
CREATE TABLE factures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_facture VARCHAR(50) NOT NULL UNIQUE,
    client_id INT NOT NULL,
    bon_id INT NULL COMMENT 'Lien vers le bon associé',
    user_id INT NOT NULL COMMENT 'Utilisateur qui a créé la facture',
    total DECIMAL(10,2) NOT NULL,
    statut ENUM('brouillon', 'validee', 'annulee') DEFAULT 'validee',
    date_facture TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (bon_id) REFERENCES bons(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_numero_facture (numero_facture),
    INDEX idx_client_id (client_id),
    INDEX idx_bon_id (bon_id),
    INDEX idx_user_id (user_id),
    INDEX idx_statut (statut),
    INDEX idx_date_facture (date_facture)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================
-- TABLE : factures_details
-- ==========================
CREATE TABLE factures_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facture_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    sous_total DECIMAL(10,2) NOT NULL,
    
    FOREIGN KEY (facture_id) REFERENCES factures(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE RESTRICT,
    
    INDEX idx_facture_id (facture_id),
    INDEX idx_produit_id (produit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================
-- TABLE : historique
-- ==========================
CREATE TABLE historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    client_id INT NULL,
    produit_id INT NULL,
    bon_id INT NULL,
    facture_id INT NULL,
    credit_id INT NULL,
    action VARCHAR(255) NOT NULL,
    type_action ENUM('creation', 'modification', 'suppression', 'paiement', 'export') NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE SET NULL,
    FOREIGN KEY (bon_id) REFERENCES bons(id) ON DELETE SET NULL,
    FOREIGN KEY (facture_id) REFERENCES factures(id) ON DELETE SET NULL,
    FOREIGN KEY (credit_id) REFERENCES credits(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_client_id (client_id),
    INDEX idx_action (action),
    INDEX idx_type_action (type_action),
    INDEX idx_date_action (date_action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERTION DES DONNÉES INITIALES
-- ============================================

-- Insertion des types de produits par défaut
INSERT INTO types_produits (nom_type, description) VALUES
('Bars', 'Produits de type Bars'),
('Accessories', 'Accessoires'),
('Co-bands', 'Produits Co-bands'),
('Lamat', 'Produits Lamat'),
('Tombore', 'Produits Tombore');

-- Insertion de quelques couleurs de base
INSERT INTO couleurs (nom_couleur, code_couleur) VALUES
('Rouge', '#FF0000'),
('Bleu', '#0000FF'),
('Vert', '#00FF00'),
('Noir', '#000000'),
('Blanc', '#FFFFFF'),
('Jaune', '#FFFF00'),
('Orange', '#FFA500'),
('Violet', '#800080');

-- Création d'un utilisateur admin par défaut
-- Note: Utilisez create_admin.php ou install.php pour créer l'utilisateur admin
-- Le mot de passe doit être hashé avec password_hash() en PHP
-- INSERT INTO users (username, password, role) VALUES
-- ('admin', 'HASH_GENERATED_BY_PHP', 'admin');
