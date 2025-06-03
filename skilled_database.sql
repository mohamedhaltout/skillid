CREATE TABLE Utilisateur (
    id_utilisateur INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL CHECK (role IN ('client', 'prestataire', 'admin'))
);


CREATE TABLE Categories (
    id_categorie INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icone TEXT,
    type ENUM('standard', 'emergency') DEFAULT 'standard'
);


CREATE TABLE Prestataire (
    id_prestataire INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    id_categorie INT NOT NULL,
    photo TEXT NOT NULL,
    specialite VARCHAR(100) NOT NULL,
    pays VARCHAR(100) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) NOT NULL UNIQUE,
    tarif_journalier DECIMAL(10, 2) NOT NULL,
    accepte_budget_global BOOLEAN NOT NULL,
    disponibilite TEXT NOT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateur(id_utilisateur),
    FOREIGN KEY (id_categorie) REFERENCES Categories(id_categorie)
);




CREATE TABLE Experience_prestataire (
    id_experience INT PRIMARY KEY AUTO_INCREMENT,
    id_prestataire INT NOT NULL,
    titre_experience VARCHAR(255) NOT NULL,
    description VARCHAR(1000) NOT NULL,
    date_project INT,
    FOREIGN KEY (id_prestataire) REFERENCES Prestataire(id_prestataire)
);

CREATE TABLE Media_experience (
    id_media INT PRIMARY KEY AUTO_INCREMENT,
    id_experience INT NOT NULL,
    type_contenu VARCHAR(50) NOT NULL CHECK (type_contenu IN ('image', 'video')),
    chemin_fichier TEXT NOT NULL,
    description_media VARCHAR(1000),
    FOREIGN KEY (id_experience) REFERENCES Experience_prestataire(id_experience)
);

CREATE TABLE Client (
    id_client INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    telephone VARCHAR(20) NOT NULL UNIQUE,
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateur(id_utilisateur)
);

CREATE TABLE Reservation (
    id_reservation INT PRIMARY KEY AUTO_INCREMENT,
    id_client INT NOT NULL,
    id_prestataire INT NOT NULL,
    description_service TEXT NOT NULL,
    budget_total DECIMAL(10, 2),
    tarif_par_jour DECIMAL(10, 2),
    date_debut DATE NOT NULL,
    nb_jours_estime INT NOT NULL,
    statut VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_client) REFERENCES Client(id_client),
    FOREIGN KEY (id_prestataire) REFERENCES Prestataire(id_prestataire),
    CHECK ((budget_total IS NULL AND tarif_par_jour IS NOT NULL) OR (budget_total IS NOT NULL AND tarif_par_jour IS NULL))
);

CREATE TABLE Devis (
    id_devis INT PRIMARY KEY AUTO_INCREMENT,
    id_reservation INT NOT NULL,
    date_debut_travaux DATE NOT NULL,
    cout_total DECIMAL(10, 2) NOT NULL,
    tarif_journalier DECIMAL(10, 2),
    acompte DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_reservation) REFERENCES Reservation(id_reservation),
    CHECK ((tarif_journalier IS NULL) OR (tarif_journalier IS NOT NULL AND cout_total IS NOT NULL))
);

CREATE TABLE Evaluation (
    id_evaluation INT PRIMARY KEY AUTO_INCREMENT,
    id_reservation INT NOT NULL,
    id_client INT NOT NULL,
    id_prestataire INT NOT NULL,
    note DECIMAL(2, 1) NOT NULL CHECK (note >= 0 AND note <= 5),
    commentaire TEXT,
    date_evaluation DATE NOT NULL,
    FOREIGN KEY (id_reservation) REFERENCES Reservation(id_reservation),
    FOREIGN KEY (id_client) REFERENCES Client(id_client),
    FOREIGN KEY (id_prestataire) REFERENCES Prestataire(id_prestataire)
);


CREATE TABLE Admin (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateur(id_utilisateur)
);


CREATE TABLE Paiement (
    id_paiement INT AUTO_INCREMENT PRIMARY KEY,
    id_reservation INT NOT NULL,
    id_client INT NOT NULL,
    id_prestataire INT NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    type_paiement ENUM('acompte', 'par_jour', 'global') NOT NULL,
    methode_paiement ENUM('stripe', 'paypal', 'virement') NOT NULL,
    date_paiement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut_paiement ENUM('en_attente', 'effectué', 'échoué') NOT NULL,
    reference_transaction VARCHAR(255),
    
    FOREIGN KEY (id_reservation) REFERENCES Reservation(id_reservation),
    FOREIGN KEY (id_client) REFERENCES Client(id_client),
    FOREIGN KEY (id_prestataire) REFERENCES Prestataire(id_prestataire)
);
