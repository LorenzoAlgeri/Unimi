DROP SCHEMA shoepal CASCADE;

CREATE SCHEMA shoepal;

SET search_path TO shoepal, public;

CREATE TABLE Negozio (
    idNegozio SERIAL PRIMARY KEY,
    responsabile VARCHAR(100) NOT NULL,
    indirizzo TEXT NOT NULL,
    attivo BOOLEAN NOT NULL DEFAULT true,
    dataChiusura DATE
);

CREATE TABLE Orario (
    idNegozio INTEGER REFERENCES Negozio(idNegozio) ON DELETE CASCADE,
    giorno VARCHAR(10) NOT NULL CHECK (giorno IN ('Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica')),
    oraInizio TIME,
    oraFine TIME,
    PRIMARY KEY (idNegozio, giorno)
);

CREATE TABLE Prodotto (
    idProdotto SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descrizione TEXT,
    marca VARCHAR(30),
    sesso VARCHAR(10), -- uomo, donna, unisex
    tipologia VARCHAR(20) -- sneakers, stivali, sandali, eleganti, corsa, trekking, casual, ballo, skate, lavoro, ginnastica, golf, basket, tennis, running, sci, surf, yoga, palestra, ciclismo
);

CREATE TABLE Disponibilità (
    idNegozio INTEGER REFERENCES Negozio(idNegozio) ON DELETE CASCADE,
    idProdotto INTEGER REFERENCES Prodotto(idProdotto) ON DELETE CASCADE,
    taglia VARCHAR(10) NOT NULL,
    prezzo DECIMAL(8,2) NOT NULL,
    quantità INTEGER NOT NULL CHECK (quantità >= 0),
    PRIMARY KEY (idNegozio, idProdotto, taglia)
);

CREATE TABLE Fornitore (
    partitaIVA VARCHAR(11) PRIMARY KEY,
    indirizzo TEXT NOT NULL
);

CREATE TABLE Fornitura (
    partitaIVA VARCHAR(11) REFERENCES Fornitore(partitaIVA) ON DELETE CASCADE,
    idProdotto INTEGER REFERENCES Prodotto(idProdotto) ON DELETE CASCADE,
    taglia VARCHAR(10) NOT NULL,
    costo DECIMAL(8,2) NOT NULL,
    disponibilità INTEGER NOT NULL CHECK (disponibilità >= 0),
    PRIMARY KEY (partitaIVA, idProdotto, taglia)
);

CREATE TABLE Ordine (
    idOrdine SERIAL PRIMARY KEY,
    partitaIVA VARCHAR(11) REFERENCES Fornitore(partitaIVA) ON DELETE CASCADE,
    idNegozio INTEGER REFERENCES Negozio(idNegozio) ON DELETE CASCADE,
    dataConsegna DATE NOT NULL
);

CREATE TABLE OrdineDettagli (
    idOrdine INTEGER REFERENCES Ordine(idOrdine) ON DELETE CASCADE,
    idProdotto INTEGER REFERENCES Prodotto(idProdotto) ON DELETE CASCADE,
    taglia VARCHAR(10) NOT NULL,
    quantità INTEGER NOT NULL CHECK (quantità > 0),
    prezzo DECIMAL(8,2) NOT NULL,
    PRIMARY KEY (idOrdine, idProdotto, taglia)
);

CREATE TABLE Utente (
    email VARCHAR(100) PRIMARY KEY,
    passwordHash TEXT NOT NULL,
    tipoUtente VARCHAR(10) NOT NULL CHECK (tipoUtente IN ('cliente', 'manager'))
);

CREATE TABLE Cliente (
    codiceFiscale VARCHAR(16) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR UNIQUE REFERENCES Utente(email) ON DELETE CASCADE
);

CREATE TABLE TesseraFedeltà (
    idtessera SERIAL PRIMARY KEY,
    codicefiscale VARCHAR(16) UNIQUE REFERENCES Cliente(codiceFiscale) ON DELETE CASCADE,
    datarichiesta DATE NOT NULL,
    idNegozio INTEGER NOT NULL REFERENCES Negozio(idNegozio),
    saldopunti INTEGER NOT NULL DEFAULT 0 CHECK (saldopunti >= 0)
);

CREATE TABLE StoricoTessere (
    idtessera INTEGER PRIMARY KEY,
    codicefiscale VARCHAR(16) NOT NULL,
    datarichiesta DATE NOT NULL,
    idnegozio INTEGER NOT NULL REFERENCES Negozio(idNegozio),
    saldopunti INTEGER NOT NULL DEFAULT 0,
    idnegoziotrasferito INTEGER 
);

CREATE TABLE Fattura (
    idFattura SERIAL PRIMARY KEY,
    codiceFiscale VARCHAR(16) REFERENCES Cliente(codiceFiscale) ON DELETE CASCADE,
    idNegozio INTEGER REFERENCES Negozio(idNegozio) NOT NULL,
    dataAcquisto DATE NOT NULL,
    puntiAccumulati INTEGER DEFAULT 0,
    scontoPercentuale INTEGER CHECK (scontoPercentuale BETWEEN 0 AND 100),
    totaleOriginale DECIMAL(10,2) NOT NULL,
    totalePagato DECIMAL(10,2) NOT NULL
);

CREATE TABLE FatturaDettagli (
    idFattura INTEGER REFERENCES Fattura(idFattura) ON DELETE CASCADE,
    idProdotto INTEGER REFERENCES Prodotto(idProdotto) ON DELETE CASCADE,
    taglia VARCHAR(10) NOT NULL,
    quantità INTEGER NOT NULL,
    prezzoUnitario DECIMAL(8,2) NOT NULL,
    PRIMARY KEY (idFattura, idProdotto, taglia)
);

-- Insert sample data into tables

-- Inserimento negozi
INSERT INTO Negozio (responsabile, indirizzo, attivo, dataChiusura) VALUES ('Matteo Professione', 'Galleria Vittorio Emanuele 10, Milano', true, NULL);
INSERT INTO Negozio (responsabile, indirizzo, attivo, dataChiusura) VALUES ('Olmo Paoletti', 'Piazza Duomo 20, Firenze', true, NULL);
INSERT INTO Negozio (responsabile, indirizzo, attivo, dataChiusura) VALUES ('Ciro Esposito', 'Piazza Plebiscito 30, Napoli', true, NULL);
INSERT INTO Negozio (responsabile, indirizzo, attivo, dataChiusura) VALUES ('Vito Ventura', 'Viale Europa 40, Torino', true, NULL);
INSERT INTO Negozio (responsabile, indirizzo, attivo, dataChiusura) VALUES ('Ernesto Marini', 'Via Brigate Rosse 50, Bologna', true, NULL);
INSERT INTO Negozio (responsabile, indirizzo, attivo, dataChiusura) VALUES ('Francesco Totti', 'Via Colosseo 60, Roma', true, NULL);

-- Inserimento orari di apertura per i negozi
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (1, 'Lunedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (1, 'Martedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (1, 'Mercoledì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (1, 'Giovedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (1, 'Venerdì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (1, 'Sabato', '10:00', '16:30');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (1, 'Domenica', NULL, NULL);
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (2, 'Lunedì', '08:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (2, 'Martedì', '08:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (2, 'Mercoledì', '08:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (2, 'Giovedì', '08:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (2, 'Venerdì', '08:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (2, 'Sabato', '09:00', '12:30');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (2, 'Domenica', NULL, NULL);
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (3, 'Lunedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (3, 'Martedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (3, 'Mercoledì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (3, 'Giovedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (3, 'Venerdì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (3, 'Sabato', NULL, NULL);
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (3, 'Domenica', NULL, NULL);
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (4, 'Lunedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (4, 'Martedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (4, 'Mercoledì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (4, 'Giovedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (4, 'Venerdì', '10:00', '20:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (4, 'Sabato', '10:00', '20:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (4, 'Domenica', NULL, NULL);
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (5, 'Lunedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (5, 'Martedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (5, 'Mercoledì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (5, 'Giovedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (5, 'Venerdì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (5, 'Sabato', '10:00', '12:30');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (5, 'Domenica', '10:00', '12:30');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (6, 'Lunedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (6, 'Martedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (6, 'Mercoledì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (6, 'Giovedì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (6, 'Venerdì', '09:00', '18:00');
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (6, 'Sabato', NULL, NULL);
INSERT INTO Orario (idNegozio, giorno, oraInizio, oraFine) VALUES (6, 'Domenica', NULL, NULL);

-- Inserimento prodotti
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sneakers Sportive', 'Scarpe da ginnastica leggere e traspiranti', 'Nike', 'unisex', 'sneakers'); --1
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('All Star', 'Sneakers in tela bianca', 'Converse', 'uomo', 'sneakers'); --2
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sneakers Colorate', 'Sneakers con dettagli colorati', 'Puma', 'donna', 'sneakers'); --3
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sneakers Running', 'Sneakers per corsa leggera', 'New Balance', 'donna', 'sneakers'); --4
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sneakers Alta Moda', 'Sneakers di design per occasioni casual', 'Balenciaga', 'donna', 'sneakers'); --5
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sneakers Urban', 'Sneakers per uso quotidiano in città', 'New Balance', 'uomo', 'sneakers'); --6
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Stivali Invernali', 'Stivali impermeabili invernali', 'Columbia', 'donna', 'stivali'); --7
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Stivali da Pioggia', 'Stivali in gomma per la pioggia', 'Hunter', 'unisex', 'stivali'); --8
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Stivali Corazzati', 'Stivali robusti per escursioni', 'Salomon', 'uomo', 'stivali'); --9
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Stivali Eleganti', 'Stivali in pelle nera', 'Geox', 'donna', 'stivali'); --10
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Texani con ricami', 'Stivali texani/camperos neri con ricami', 'Stradivarius', 'donna', 'stivali'); --11
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sandali Estivi', 'Sandali comodi estivi', 'Maui', 'unisex', 'sandali'); --12
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Zeppe Estive', 'Sandali con zeppa per donna', 'Adidas', 'donna', 'sandali'); --13
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sandali Sportivi', 'Sandali per attività esterne', 'Birkenstock', 'unisex', 'sandali'); --14
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sandali con Tacco', 'Sandali eleganti e raffinati per cerimonie', 'Balenciaga', 'donna', 'sandali'); --15
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sandali Infradito', 'Sandali infradito per la spiaggia', 'Havaianas', 'unisex', 'sandali'); --16
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Tacchi Alti', 'Scarpe eleganti in pelle nera', 'Geox', 'unisex', 'eleganti'); --17
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Mocassini Eleganti', 'Mocassini in pelle lucida','Prada', 'uomo', 'eleganti'); --18
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Décolleté', 'Scarpe eleganti con tacco', 'Prada', 'donna', 'eleganti'); --19
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe Derby', 'Scarpe classiche stringate', 'Gucci', 'uomo', 'eleganti'); --20
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe Oxford', 'Scarpe eleganti per cerimonie', 'Santoni', 'uomo', 'eleganti'); --21
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Ballerine Eleganti', 'Ballerine raffinate per eventi', 'Repetto', 'donna', 'eleganti'); --22
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe da Corsa Nike', 'Scarpe leggere per la corsa su strada', 'Nike', 'uomo', 'corsa'); --23
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe Running Pro', 'Scarpe da corsa professionali', 'Hoka', 'unisex', 'corsa'); --24
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe da Sentiero', 'Scarpe da corsa per sentieri', 'Salomon', 'unisex', 'trekking'); --25
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe da Trekking', 'Scarpe robuste per escursioni in montagna', 'Salomon', 'unisex', 'trekking'); --26
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Trekking Impermeabili', 'Scarpe trekking impermeabili', 'Salomon', 'uomo', 'trekking'); --27
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Trekking Leggere', 'Scarpe trekking leggere', 'Salomon', 'unisex', 'trekking'); --28
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Mocassini Casual', 'Mocassini in pelle per uso quotidiano', 'Prada', 'uomo', 'casual'); --29
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Slip-on Casual', 'Scarpe slip-on comode', 'Birkenstock', 'donna', 'casual'); --30
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe Stringate', 'Scarpe casual in tela', 'Converse', 'uomo', 'casual'); --31
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe da Ballo', 'Scarpe da ballo per danza classica', 'Capezio', 'donna', 'ballo'); --32
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe Ballo Latino', 'Scarpe per balli latino-americani', 'Supadance', 'unisex', 'ballo'); --33
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe Skater', 'Scarpe resistenti per lo skateboarding', 'DC Shoes', 'uomo', 'skate'); --34
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Skate Urban', 'Scarpe skate per uso urbano', 'Nike', 'unisex', 'skate'); --35
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe da Lavoro', 'Scarpe antinfortunistiche per il lavoro', 'U-Power', 'unisex', 'lavoro'); --36
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe Lavoro Leggere', 'Scarpe da lavoro leggere', 'U-Power', 'uomo', 'lavoro'); --37
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sneakers Ginnastica Donna', 'Scarpe da ginnastica per uso quotidiano', 'Superga', 'donna', 'ginnastica'); --38
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Ginnastica Bambini', 'Scarpe da ginnastica per bambini', 'Adidas', 'unisex', 'ginnastica'); --39
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Golf Uomo', 'Scarpe leggere e comode per il golf', 'Callaway', 'uomo', 'golf'); --40
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Golf Donna', 'Scarpe da golf per donna', 'Callaway', 'donna', 'golf'); --41
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Jordan Basket', 'Scarpe alte per il basket', 'Jordan', 'uomo', 'basket'); --42
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Basket Junior', 'Scarpe basket per ragazzi', 'Nike', 'unisex', 'basket'); --43
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe da Tennis', 'Scarpe leggere per il tennis su campi duri', 'Wilson', 'donna', 'tennis'); --44
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Tennis Pro', 'Scarpe da tennis professionali', 'Asics', 'uomo', 'tennis'); --45
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Running Strada', 'Scarpe ammortizzate per la corsa su strada', 'Brooks', 'unisex', 'running'); --46
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Running Leggere', 'Scarpe running ultraleggere', 'Saucony', 'donna', 'running'); --47
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe da Sci', 'Scarpe per lo sci in montagna', 'Tecnica', 'unisex', 'sci'); --48
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sci Junior', 'Scarpe da sci per bambini', 'Nordica', 'unisex', 'sci'); --49
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe da Surf', 'Scarpe leggere e impermeabili per il surf', 'Quicksilver', 'uomo', 'surf'); --50
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Surf Donna', 'Scarpe da surf per donna', 'Quicksilver', 'donna', 'surf'); --51
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe da Yoga', 'Scarpe leggere e flessibili per lo yoga', 'Adidas', 'donna', 'yoga'); --52
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Yoga Unisex', 'Scarpe yoga per tutti', 'Adidas', 'unisex', 'yoga'); --53
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe Allenamento', 'Scarpe robuste per allenarsi in palestra', 'Reebok', 'unisex', 'palestra'); --54
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Allenamento Donna', 'Scarpe palestra leggere per donna', 'Puma', 'donna', 'palestra'); --55
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Scarpe da Ciclismo', 'Scarpe specifiche per il ciclismo su strada', 'Sidi', 'uomo', 'ciclismo'); --56
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Ciclismo Donna', 'Scarpe ciclismo per donna', 'Shimano', 'donna', 'ciclismo'); --57
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Texani Azzurri', 'Stivali texani/camperos azzurri', 'Ceres', 'donna', 'stivali'); --58
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Tacchi Nude', 'Scarpe eleganti in vinile color pelle', 'Geox', 'donna', 'eleganti'); --59
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Adidas Campus', 'Adidas campus il velluto grigie', 'Adidas', 'donna', 'sneakers'); --60
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sandali Rosa', 'Sandali donna rosa con tacco', 'Lora Ferres', 'donna', 'sandali'); --61
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Nike Air Force', 'Nike Air Force rosa', 'Nike', 'donna', 'sneakers'); --62
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Texani in Camoscio', 'Stivali texani in camoscio', 'Butti', 'donna', 'stivali'); --63
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Nike Blazer Low', 'Nike Blazer low bianche dettagli rosa', 'Nike', 'donna', 'sneakers'); --64
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Anfibi Dr.Martens', 'Anfibi Neri in pelle Dr.Martens', 'Dr.Martens', 'donna', 'anfibi'); --65
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Dr.Martens Sinclair', 'Stivaletti con zip Dr.Martens', 'Dr.Martens', 'donna', 'anfibi'); --66
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Tacchi Neri', 'Tacchi eleganti in satino nero', 'Zara', 'donna', 'eleganti'); --67
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Texani Pelle Nera', 'Stivali texani in pelle nera', 'Barca', 'donna', 'stivali'); --68
INSERT INTO Prodotto (nome, descrizione, marca, sesso, tipologia) VALUES ('Sneakers Hooka', 'Sneakers leggere e comode', 'Hooka', 'donna', 'sneakers'); --69

-- Inserimento magazzino negozi

-- Negozio 1
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 1, '41', 59.99, 10);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 1, '42', 59.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 1, '43', 59.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 1, '44', 59.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 2, '41', 79.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 2, '42', 79.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 2, '43', 79.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 3, '37', 49.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 3, '38', 49.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 3, '39', 49.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 4, '40', 89.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 4, '41', 89.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 4, '42', 89.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 5, '39', 129.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 5, '40', 129.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 5, '41', 129.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 6, '41', 69.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 6, '42', 69.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 7, '38', 99.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 7, '39', 99.99, 9);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 8, '40', 59.99, 10);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 8, '41', 59.99, 20);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 9, '42', 109.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 10, '39', 119.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 11, '38', 62.99, 9);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 11, '39', 62.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 12, '41', 34.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 12, '42', 34.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 12, '43', 34.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 13, '38', 39.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 14, '40', 29.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 15, '39', 49.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 16, '41', 19.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 16, '42', 19.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 17, '39', 119.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 18, '42', 89.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 19, '38', 79.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 20, '43', 99.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 21, '42', 109.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 22, '37', 59.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 23, '44', 89.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 24, '43', 99.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 25, '42', 109.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 26, '41', 109.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 27, '43', 119.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 28, '42', 99.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 29, '41', 59.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 29, '42', 59.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 30, '38', 49.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 31, '42', 54.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 32, '37', 69.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 33, '40', 74.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 34, '42', 54.99, 9);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 35, '41', 59.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 36, '43', 69.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 37, '42', 42.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 38, '39', 39.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 39, '36', 29.99, 9);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 40, '43', 99.99, 10);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 41, '39', 99.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 42, '44', 119.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 43, '38', 79.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 44, '40', 69.99, 9);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 45, '42', 69.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 46, '41', 79.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 47, '41', 79.99, 10);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 47, '42', 79.99, 12);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 48, '43', 149.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 49, '36', 129.99, 17);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 50, '43', 59.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 51, '39', 59.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 52, '38', 29.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 53, '41', 29.99, 9);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 54, '42', 44.99, 10);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 55, '39', 44.99, 11);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 56, '43', 89.99, 12);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 57, '43', 89.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 57, '44', 89.99, 9);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 58, '38', 150.99, 11);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 58, '39', 150.99, 10);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 59, '38', 119.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 60, '39', 69.99, 9);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 61, '38', 39.99, 10);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 62, '39', 89.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 1, '40', 59.99, 10);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 2, '44', 79.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 3, '40', 49.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 5, '42', 129.99, 9);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 6, '43', 69.99, 10);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 7, '40', 99.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 8, '42', 59.99, 9);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 9, '43', 109.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 10, '40', 119.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 11, '40', 62.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 12, '44', 34.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 13, '39', 39.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 14, '41', 29.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 15, '40', 49.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 63, '39', 159.99, 10);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 63, '40', 159.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 63, '41', 159.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 64, '39', 70.99, 9);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 64, '40', 70.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 64, '41', 70.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 65, '39', 60.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 65, '40', 60.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 65, '41', 60.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 66, '39', 90.99, 6);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 66, '40', 90.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 66, '41', 90.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 67, '39', 30.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 67, '40', 30.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 67, '41', 30.99, 7);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 68, '40', 150.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 68, '41', 150.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 69, '39', 50.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (1, 69, '40', 50.99, 6);

-- Negozio 2
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 2, '43', 82.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 3, '37', 49.99, 8);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 3, '39', 49.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 4, '40', 89.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 4, '41', 89.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 5, '39', 132.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 7, '38', 101.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 8, '40', 61.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 9, '42', 111.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 10, '39', 121.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 11, '38', 65.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 12, '41', 36.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 13, '38', 41.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 14, '40', 31.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 15, '39', 51.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 16, '41', 21.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 17, '40', 121.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 18, '42', 91.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 19, '38', 81.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 20, '43', 101.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 21, '42', 111.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 22, '37', 61.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 23, '44', 91.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 24, '43', 101.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 25, '42', 111.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 26, '41', 111.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 27, '43', 121.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 28, '42', 101.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 29, '41', 61.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 30, '38', 51.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 31, '42', 56.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 32, '37', 71.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 33, '40', 76.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 34, '42', 56.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 35, '41', 61.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 36, '43', 71.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 37, '42', 44.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 38, '39', 41.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 39, '36', 31.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 40, '43', 101.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 41, '39', 101.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 42, '44', 121.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 43, '38', 81.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 44, '40', 71.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 45, '42', 71.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 46, '41', 81.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 47, '41', 81.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 48, '43', 151.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 49, '36', 131.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 50, '43', 61.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 51, '39', 61.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 52, '38', 31.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 53, '41', 31.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 54, '42', 46.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 56, '43', 91.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 57, '43', 91.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 58, '38', 65.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 59, '38', 121.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 61, '38', 41.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (2, 62, '39', 91.99, 1);

-- Negozio 3
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 5, '39', 129.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 5, '40', 129.99, 0);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 5, '41', 129.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 6, '42', 71.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 7, '39', 101.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 8, '41', 61.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 9, '43', 111.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 10, '40', 121.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 11, '39', 65.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 12, '42', 36.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 13, '39', 41.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 14, '41', 31.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 15, '40', 51.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 16, '42', 21.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 17, '42', 124.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 18, '43', 91.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 19, '39', 81.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 20, '44', 101.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 21, '43', 111.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 22, '38', 61.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 23, '45', 91.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 24, '44', 101.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 25, '43', 111.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 26, '42', 111.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 27, '44', 121.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 28, '43', 101.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 29, '42', 61.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 30, '39', 51.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 31, '43', 56.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 32, '38', 71.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 33, '41', 76.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 34, '42', 54.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 34, '43', 54.99, 0);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 35, '42', 61.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 36, '44', 71.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 37, '43', 44.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 38, '40', 41.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 39, '37', 31.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 40, '44', 101.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 41, '40', 101.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 42, '45', 121.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 43, '39', 81.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 44, '41', 71.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 45, '43', 71.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 46, '42', 81.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 47, '43', 82.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 48, '44', 151.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 49, '37', 131.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 50, '44', 61.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 51, '40', 61.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 52, '39', 31.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 53, '42', 31.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 54, '43', 46.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 55, '40', 46.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 56, '44', 91.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 57, '44', 91.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 58, '39', 65.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 59, '39', 121.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 61, '39', 41.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (3, 62, '40', 91.99, 1);

-- Negozio 4
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 7, '39', 99.99, 0);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 8, '42', 63.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 9, '44', 113.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 10, '41', 123.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 11, '40', 67.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 12, '43', 38.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 13, '40', 43.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 14, '42', 33.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 15, '41', 53.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 16, '43', 23.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 17, '40', 119.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 18, '44', 93.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 19, '40', 83.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 20, '45', 103.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 21, '44', 113.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 22, '39', 63.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 23, '43', 89.99, 5);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 23, '44', 89.99, 0);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 24, '45', 103.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 25, '44', 113.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 26, '43', 113.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 27, '45', 123.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 28, '44', 103.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 29, '43', 63.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 30, '40', 53.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 31, '44', 58.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 32, '39', 73.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 33, '42', 78.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 34, '44', 58.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 35, '43', 63.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 36, '45', 73.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 37, '40', 39.99, 4);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 37, '41', 39.99, 0);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 38, '41', 43.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 39, '38', 33.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 40, '44', 103.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 41, '41', 103.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 42, '45', 123.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 43, '40', 83.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 44, '42', 73.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 45, '44', 73.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 46, '43', 83.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 47, '44', 83.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 48, '45', 153.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 49, '40', 149.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 49, '41', 149.99, 0);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 50, '45', 63.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 51, '41', 63.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 52, '40', 33.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 53, '43', 33.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 54, '44', 48.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 56, '45', 93.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 57, '45', 93.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 58, '40', 67.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 59, '40', 123.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 61, '40', 43.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (4, 62, '41', 93.99, 1);

-- Negozio 5
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 1, '43', 64.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 5, '39', 134.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 6, '44', 75.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 7, '40', 103.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 8, '43', 65.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 9, '45', 115.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 10, '42', 125.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 11, '41', 69.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 12, '41', 36.99, 0);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 13, '41', 45.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 14, '43', 35.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 15, '42', 55.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 16, '44', 25.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 17, '41', 127.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 18, '45', 95.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 19, '41', 85.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 20, '46', 105.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 21, '45', 115.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 22, '41', 65.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 23, '46', 95.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 24, '46', 105.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 25, '45', 115.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 26, '38', 109.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 26, '39', 109.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 27, '46', 125.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 28, '45', 105.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 29, '44', 65.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 30, '41', 55.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 31, '45', 60.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 32, '41', 75.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 33, '43', 80.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 34, '45', 60.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 35, '44', 65.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 36, '46', 75.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 37, '42', 42.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 38, '43', 45.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 39, '40', 35.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 40, '45', 105.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 41, '42', 105.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 42, '46', 125.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 43, '41', 85.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 44, '43', 75.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 45, '38', 69.99, 2);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 45, '39', 69.99, 0);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 46, '44', 85.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 47, '45', 85.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 48, '46', 155.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 49, '41', 133.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 50, '46', 65.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 51, '42', 65.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 52, '41', 35.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 53, '44', 35.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 54, '45', 50.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 55, '41', 44.99, 3);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 55, '42', 44.99, 0);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 56, '46', 95.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 57, '46', 95.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 58, '41', 69.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 59, '41', 125.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 61, '41', 45.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (5, 62, '42', 95.99, 1);

-- Negozio 6
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 1, '44', 65.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 2, '46', 86.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 5, '41', 136.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 6, '45', 77.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 7, '42', 105.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 8, '44', 67.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 9, '46', 117.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 10, '43', 127.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 11, '42', 71.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 12, '43', 39.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 13, '42', 47.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 14, '44', 37.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 15, '43', 57.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 16, '45', 27.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 17, '42', 129.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 18, '46', 97.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 19, '42', 87.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 20, '47', 107.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 21, '46', 117.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 22, '42', 67.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 23, '47', 97.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 24, '47', 107.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 25, '46', 117.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 26, '40', 111.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 27, '47', 127.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 28, '46', 107.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 29, '45', 67.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 30, '42', 57.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 31, '46', 62.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 32, '42', 77.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 33, '44', 82.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 34, '46', 62.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 35, '45', 67.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 36, '47', 77.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 37, '44', 47.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 38, '45', 47.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 39, '42', 37.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 40, '46', 107.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 41, '43', 107.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 42, '47', 127.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 43, '42', 87.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 44, '44', 77.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 45, '46', 77.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 46, '45', 87.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 47, '46', 87.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 48, '47', 157.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 49, '42', 135.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 50, '47', 67.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 51, '43', 67.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 52, '42', 37.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 53, '45', 37.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 54, '46', 52.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 56, '47', 97.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 57, '47', 97.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 58, '42', 71.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 59, '42', 127.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 60, '43', 77.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 61, '42', 47.99, 1);
INSERT INTO Disponibilità (idNegozio, idProdotto, taglia, prezzo, quantità) VALUES (6, 62, '43', 97.99, 1);

-- Inserimento fornitori
INSERT INTO Fornitore (partitaIVA, indirizzo) VALUES ('IT123456789', 'Via delle Industrie 1, Milano');
INSERT INTO Fornitore (partitaIVA, indirizzo) VALUES ('IT987654321', 'Piazza della Libertà 2, Roma');
INSERT INTO Fornitore (partitaIVA, indirizzo) VALUES ('IT112233445', 'Corso Vittorio Emanuele 3, Napoli');
INSERT INTO Fornitore (partitaIVA, indirizzo) VALUES ('IT556677889', 'Viale Europa 4, Torino');
INSERT INTO Fornitore (partitaIVA, indirizzo) VALUES ('IT998877665', 'Via Garibaldi 5, Bologna');

-- Fornitura per IT123456789 (Milano)
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 1, '41', 45.00, 40);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 1, '42', 46.00, 35);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 1, '43', 47.00, 30);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 2, '41', 51.00, 25);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 2, '42', 52.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 2, '43', 53.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 3, '37', 32.00, 30);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 3, '38', 33.00, 28);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 3, '39', 34.00, 25);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 4, '40', 61.00, 22);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 4, '41', 62.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 4, '42', 63.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 5, '39', 85.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 5, '40', 86.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 5, '41', 87.00, 15);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 6, '41', 50.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 6, '42', 51.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 6, '43', 52.00, 15);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 7, '38', 72.00, 15);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 7, '39', 73.00, 13);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 7, '40', 74.00, 12);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 8, '40', 40.00, 34);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 8, '41', 41.00, 30);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 8, '42', 42.00, 28);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 9, '42', 109.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 9, '43', 110.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 9, '44', 111.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 10, '39', 60.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 10, '40', 61.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 10, '41', 62.00, 15);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 11, '38', 65.00, 23);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 11, '39', 66.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 11, '40', 67.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 12, '41', 25.00, 40);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 12, '42', 26.00, 35);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 12, '43', 27.00, 30);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 13, '38', 30.00, 30);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 13, '39', 31.00, 28);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 13, '40', 32.00, 25);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 14, '40', 32.00, 34);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 14, '41', 33.00, 30);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 14, '42', 34.00, 28);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 15, '39', 49.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 15, '40', 50.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 15, '41', 51.00, 15);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 16, '41', 20.00, 47);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 16, '42', 21.00, 43);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 16, '43', 22.00, 40);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 17, '39', 90.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 17, '40', 91.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 17, '41', 92.00, 15);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 18, '42', 63.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 18, '43', 64.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 18, '44', 65.00, 15);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 19, '38', 60.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 19, '39', 61.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 19, '40', 62.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 20, '43', 75.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 20, '44', 76.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 20, '45', 77.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 21, '42', 90.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 21, '43', 91.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 21, '44', 92.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 22, '37', 65.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 22, '38', 66.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 22, '39', 67.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 23, '44', 40.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 23, '45', 41.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 23, '46', 42.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 24, '43', 50.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 24, '44', 51.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 24, '45', 52.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 25, '42', 75.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 25, '43', 76.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 25, '44', 77.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 26, '41', 110.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 26, '42', 111.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 26, '43', 112.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 27, '43', 120.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 27, '44', 121.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 27, '45', 122.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 28, '42', 100.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 28, '43', 101.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 28, '44', 102.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 29, '41', 60.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 29, '42', 61.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 29, '43', 62.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 30, '38', 50.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 30, '39', 51.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 30, '40', 52.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 31, '42', 55.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 31, '43', 56.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 31, '44', 57.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 32, '37', 70.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 32, '38', 71.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 32, '39', 72.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 33, '40', 75.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 33, '41', 76.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 33, '42', 77.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 34, '42', 55.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 34, '43', 56.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 34, '44', 57.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 35, '41', 60.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 35, '42', 61.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 35, '43', 62.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 36, '43', 70.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 36, '44', 71.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 36, '45', 72.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 37, '42', 45.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 37, '43', 46.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 37, '44', 47.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 38, '39', 40.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 38, '40', 41.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 38, '41', 42.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 39, '36', 30.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 39, '37', 31.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 39, '38', 32.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 40, '43', 100.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 40, '44', 101.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 40, '45', 102.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 41, '39', 100.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 41, '40', 101.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 41, '41', 102.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 42, '44', 120.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 42, '45', 121.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 42, '46', 122.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 43, '38', 80.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 43, '39', 81.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 43, '40', 82.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 44, '40', 70.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 44, '41', 71.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 44, '42', 72.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 45, '42', 70.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 45, '43', 71.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 45, '44', 72.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 46, '41', 80.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 46, '42', 81.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 46, '43', 82.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 47, '41', 80.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 47, '42', 81.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 47, '43', 82.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 48, '43', 150.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 48, '44', 151.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 48, '45', 152.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 49, '36', 130.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 49, '37', 131.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 49, '38', 132.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 50, '43', 60.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 50, '44', 61.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 50, '45', 62.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 51, '39', 60.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 51, '40', 61.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 51, '41', 62.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 52, '38', 30.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 52, '39', 31.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 52, '40', 32.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 53, '41', 30.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 53, '42', 31.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 53, '43', 32.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 54, '42', 45.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 54, '43', 46.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 54, '44', 47.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 55, '39', 45.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 55, '40', 46.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 55, '41', 47.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 56, '43', 90.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 56, '44', 91.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 56, '45', 92.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 57, '43', 90.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 57, '44', 91.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 57, '45', 92.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 58, '38', 65.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 58, '39', 66.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 58, '40', 67.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 59, '38', 120.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 59, '39', 121.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 59, '40', 122.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 60, '39', 70.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 60, '40', 71.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 60, '41', 72.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 61, '38', 40.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 61, '39', 41.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 61, '40', 42.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 62, '39', 90.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 62, '40', 91.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 62, '41', 92.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 63, '39', 160.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 63, '40', 161.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 63, '41', 162.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 64, '39', 71.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 64, '40', 72.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 64, '41', 73.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 65, '39', 61.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 65, '40', 62.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 65, '41', 63.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 66, '39', 91.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 66, '40', 92.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 66, '41', 93.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 67, '39', 31.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 67, '40', 32.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 67, '41', 33.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 68, '40', 151.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 68, '41', 152.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 68, '42', 153.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 69, '39', 51.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 69, '40', 52.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT123456789', 69, '41', 53.00, 7);

-- Fornitura per IT987654321 (Roma)
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 1, '41', 46.00, 38);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 1, '42', 47.00, 33);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 1, '43', 48.00, 28);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 2, '41', 52.00, 23);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 2, '42', 53.00, 19);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 2, '43', 54.00, 16);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 3, '37', 34.00, 19);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 3, '38', 35.00, 17);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 3, '39', 36.00, 14);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 4, '40', 62.00, 21);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 4, '41', 63.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 4, '42', 64.00, 16);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 5, '39', 87.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 5, '40', 88.00, 16);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 5, '41', 89.00, 13);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 6, '41', 51.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 6, '42', 52.00, 16);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 6, '43', 53.00, 13);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 7, '38', 73.00, 13);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 7, '39', 74.00, 11);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 7, '40', 75.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 8, '40', 41.00, 32);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 8, '41', 42.00, 28);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 8, '42', 43.00, 26);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 9, '42', 110.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 9, '43', 111.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 9, '44', 112.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 10, '39', 61.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 10, '40', 62.00, 16);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 10, '41', 63.00, 13);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 11, '38', 66.00, 21);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 11, '39', 67.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 11, '40', 68.00, 16);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 12, '41', 26.00, 38);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 12, '42', 27.00, 33);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 12, '43', 28.00, 28);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 13, '38', 31.00, 28);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 13, '39', 32.00, 26);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 13, '40', 33.00, 23);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 14, '40', 33.00, 32);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 14, '41', 34.00, 28);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 14, '42', 35.00, 26);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 15, '39', 50.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 15, '40', 51.00, 16);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 15, '41', 52.00, 13);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 16, '41', 21.00, 45);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 16, '42', 22.00, 41);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 16, '43', 23.00, 38);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 17, '39', 91.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 17, '40', 92.00, 16);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 17, '41', 93.00, 13);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 18, '42', 64.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 18, '43', 65.00, 16);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 18, '44', 66.00, 13);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 19, '38', 61.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 19, '39', 62.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 19, '40', 63.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 20, '43', 76.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 20, '44', 77.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 20, '45', 78.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 21, '42', 91.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 21, '43', 92.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 21, '44', 93.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 22, '37', 66.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 22, '38', 67.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 22, '39', 68.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 23, '44', 41.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 23, '45', 42.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 23, '46', 43.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 24, '43', 51.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 24, '44', 52.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 24, '45', 53.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 25, '42', 76.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 25, '43', 77.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 25, '44', 78.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 26, '41', 110.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 26, '42', 111.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 26, '43', 112.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 27, '43', 122.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 27, '44', 123.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 27, '45', 124.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 28, '42', 102.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 28, '43', 103.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 28, '44', 104.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 29, '41', 62.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 29, '42', 63.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 29, '43', 64.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 30, '38', 52.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 30, '39', 53.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 30, '40', 54.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 31, '42', 57.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 31, '43', 58.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 31, '44', 59.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 32, '37', 72.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 32, '38', 73.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 32, '39', 74.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 33, '40', 77.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 33, '41', 78.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 33, '42', 79.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 34, '42', 57.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 34, '43', 58.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 34, '44', 59.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 35, '41', 62.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 35, '42', 63.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 35, '43', 64.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 36, '43', 72.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 36, '44', 73.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 36, '45', 74.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 37, '42', 47.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 37, '43', 48.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 37, '44', 49.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 38, '39', 42.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 38, '40', 43.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 38, '41', 44.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 39, '36', 32.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 39, '37', 33.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 39, '38', 34.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 40, '43', 102.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 40, '44', 103.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 40, '45', 104.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 41, '39', 102.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 41, '40', 103.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 41, '41', 104.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 42, '44', 122.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 42, '45', 123.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 42, '46', 124.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 43, '38', 82.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 43, '39', 83.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 43, '40', 84.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 44, '40', 72.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 44, '41', 73.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 44, '42', 74.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 45, '42', 72.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 45, '43', 73.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 45, '44', 74.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 46, '41', 82.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 46, '42', 83.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 46, '43', 84.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 47, '41', 82.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 47, '42', 83.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 47, '43', 84.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 48, '43', 152.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 48, '44', 153.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 48, '45', 154.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 49, '36', 132.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 49, '37', 133.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 49, '38', 134.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 50, '43', 62.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 50, '44', 63.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 50, '45', 64.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 51, '39', 62.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 51, '40', 63.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 51, '41', 64.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 52, '38', 32.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 52, '39', 33.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 52, '40', 34.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 53, '41', 32.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 53, '42', 33.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 53, '43', 34.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 54, '42', 47.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 54, '43', 48.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 54, '44', 49.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 55, '39', 47.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 55, '40', 48.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 55, '41', 49.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 56, '43', 92.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 56, '44', 93.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 56, '45', 94.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 57, '43', 92.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 57, '44', 93.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 57, '45', 94.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 58, '38', 67.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 58, '39', 68.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 58, '40', 69.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 59, '38', 122.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 59, '39', 123.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 59, '40', 124.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 60, '39', 72.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 60, '40', 73.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 60, '41', 74.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 61, '38', 42.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 61, '39', 43.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 61, '40', 44.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 62, '39', 92.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 62, '40', 93.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 62, '41', 94.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 63, '39', 162.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 63, '40', 163.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 63, '41', 164.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 64, '39', 73.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 64, '40', 74.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 64, '41', 75.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 65, '39', 63.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 65, '40', 64.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 65, '41', 65.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 66, '39', 93.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 66, '40', 94.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 66, '41', 95.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 67, '39', 33.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 67, '40', 34.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 67, '41', 35.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 68, '40', 153.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 68, '41', 154.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 68, '42', 155.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 69, '39', 53.00, 10);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 69, '40', 54.00, 8);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT987654321', 69, '41', 55.00, 7);

-- Fornitura per IT112233445 (Napoli)
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 26, '41', 111.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 4, '40', 61.00, 23);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 26, '42', 112.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 26, '43', 113.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 4, '41', 62.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 27, '43', 121.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 27, '44', 122.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 4, '42', 63.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 27, '45', 123.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 28, '42', 101.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 28, '43', 102.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 28, '44', 103.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 5, '39', 87.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 29, '41', 61.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 29, '42', 62.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 5, '40', 88.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 29, '43', 63.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 30, '38', 51.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 30, '39', 52.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT112233445', 5, '41', 89.00, 15);

-- Fornitura per IT556677889 (Torino)
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT556677889', 3, '37', 32.00, 27);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT556677889', 3, '38', 33.00, 25);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT556677889', 3, '39', 34.00, 22);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT556677889', 4, '40', 62.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT556677889', 4, '41', 63.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT556677889', 4, '42', 64.00, 15);

-- Fornitura per IT998877665 (Bologna)
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 45, '43', 72.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 45, '44', 73.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 46, '41', 81.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 46, '42', 82.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 1, '41', 46.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 46, '43', 83.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 47, '41', 81.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 47, '42', 82.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 47, '43', 83.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 1, '42', 47.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 48, '43', 151.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 48, '44', 152.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 48, '45', 153.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 49, '36', 131.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 1, '43', 48.00, 15);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 49, '37', 132.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 49, '38', 133.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 50, '43', 61.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 50, '44', 62.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 50, '45', 63.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 51, '39', 61.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 51, '40', 62.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 51, '41', 63.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 52, '38', 32.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 52, '39', 33.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 2, '41', 52.00, 20);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 52, '40', 34.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 53, '41', 31.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 53, '42', 32.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 53, '43', 33.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 54, '42', 46.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 54, '43', 47.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 54, '44', 48.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 55, '39', 46.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 55, '40', 47.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 55, '41', 48.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 56, '43', 91.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 56, '44', 92.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 2, '42', 53.00, 18);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 56, '45', 93.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 57, '43', 91.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 57, '44', 92.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 2, '43', 54.00, 15);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 57, '45', 93.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 58, '38', 66.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 58, '39', 67.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 58, '40', 68.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 59, '38', 121.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 59, '39', 122.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 59, '40', 123.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 60, '39', 71.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 60, '40', 72.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 60, '41', 73.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 61, '38', 41.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 61, '39', 42.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 61, '40', 43.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 62, '39', 91.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 62, '40', 92.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 62, '41', 93.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 63, '39', 161.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 63, '40', 162.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 63, '41', 163.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 64, '39', 72.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 64, '40', 73.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 64, '41', 74.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 65, '39', 62.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 65, '40', 63.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 65, '41', 64.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 66, '39', 92.00, 9); 
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 66, '40', 93.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 66, '41', 94.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 67, '39', 32.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 67, '40', 33.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 67, '41', 34.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 68, '40', 152.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 68, '41', 153.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 68, '42', 154.00, 6);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 69, '39', 52.00, 9);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 69, '40', 53.00, 7);
INSERT INTO Fornitura (partitaIVA, idProdotto, taglia, costo, disponibilità) VALUES ('IT998877665', 69, '41', 54.00, 6);

-- Ordini
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT123456789', 1, '2020-05-15');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT987654321', 2, '2021-08-20');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT112233445', 3, '2022-01-10');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT556677889', 4, '2023-04-25');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT998877665', 5, '2023-09-05');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT123456789', 1, '2024-01-14');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT987654321', 2, '2024-01-30');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT112233445', 6, '2024-02-18');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT556677889', 4, '2024-03-01');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT998877665', 5, '2024-12-10');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT123456789', 1, '2025-01-22');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT987654321', 2, '2025-02-05');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT112233445', 3, '2025-03-15');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT556677889', 6, '2025-04-20');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT998877665', 5, '2025-06-18');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT123456789', 1, '2025-06-21');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT987654321', 2, '2025-06-22');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT112233445', 3, '2025-06-30');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT556677889', 4, '2025-07-03');
INSERT INTO Ordine (partitaIVA, idNegozio, dataConsegna) VALUES ('IT998877665', 6, '2025-07-05');

-- Dettagli Ordini
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (1, 1, '41', 10, 45.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (1, 5, '39', 5, 85.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (1, 12, '41', 8, 25.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (2, 2, '41', 7, 51.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (2, 6, '41', 4, 50.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (2, 11, '38', 3, 66.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (3, 4, '40', 6, 61.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (3, 8, '40', 5, 40.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (3, 13, '38', 2, 30.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (4, 3, '37', 9, 32.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (4, 7, '38', 4, 72.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (4, 15, '39', 3, 25.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (5, 8, '40', 6, 43.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (5, 12, '41', 7, 26.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (5, 16, '41', 5, 20.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (6, 1, '41', 12, 45.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (6, 17, '39', 2, 90.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (6, 23, '44', 4, 65.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (7, 2, '41', 8, 51.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (7, 6, '41', 3, 50.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (7, 11, '38', 2, 66.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (8, 4, '40', 5, 61.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (8, 8, '40', 4, 40.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (8, 13, '38', 3, 30.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (9, 3, '37', 10, 32.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (9, 7, '38', 2, 72.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (9, 15, '39', 4, 25.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (10, 8, '40', 7, 43.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (10, 12, '41', 6, 26.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (10, 16, '41', 3, 20.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (11, 5, '39', 6, 85.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (11, 10, '39', 2, 60.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (11, 14, '40', 5, 32.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (12, 6, '41', 7, 50.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (12, 11, '38', 3, 66.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (12, 17, '39', 2, 90.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (13, 4, '40', 8, 61.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (13, 8, '40', 4, 40.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (13, 13, '38', 2, 30.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (14, 3, '37', 9, 32.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (14, 7, '38', 5, 72.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (14, 15, '39', 3, 25.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (15, 8, '40', 6, 43.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (15, 12, '41', 7, 26.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (15, 16, '41', 4, 20.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (16, 1, '41', 11, 45.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (16, 5, '39', 4, 85.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (16, 12, '41', 6, 25.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (17, 2, '41', 8, 51.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (17, 6, '41', 5, 50.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (17, 11, '38', 2, 66.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (18, 4, '40', 7, 61.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (18, 8, '40', 3, 40.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (18, 13, '38', 2, 30.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (19, 3, '37', 10, 32.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (19, 7, '38', 4, 72.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (19, 15, '39', 3, 25.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (20, 8, '40', 7, 43.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (20, 12, '41', 5, 26.00);
INSERT INTO OrdineDettagli (idOrdine, idProdotto, taglia, quantità, prezzo) VALUES (20, 16, '41', 4, 20.00);

-- Inserimento utenti
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('francesco.corrado@shoepal.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'manager');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('valerio.bellandi@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('mario.rossi@gmail.com', '$2y$10$QhCPZtbgeoJTOseqevIRH.K3AnRVug4hj.GhIhlKQ1FAqAqV3mQHK', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('tommaso.bianchi@gmail.com', '$2y$10$YfPaFgTX7ddU1QHeWYaOUOA7JYccLKBImOcGg4umXkTKi./.riqoS', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('chiara.galletti@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('francesca.zermani@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('andrea.fraccaro@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('federico.gallo@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('elena.fazio@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('giorgia.pezzotta@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('lucia.grandi@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('laura.rossi@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('valeria.ferrari@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('gianni.bianchi@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('francesca.marti@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('simone.verdi@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('gallo.enrico@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('marco.bartoli@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('marta.santini@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('luigi.pellegrini@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('franco.luciani@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('paola.ricci@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('gianna.monti@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('lara.venturi@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('mario.caruso@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('giuseppe.blu@gmail.com', '$2y$10$NWgDkzkGWcxu3j9GvqeZ3OlD9xGiNWOsXMHr9feiqSrNqCLrqlJyW', 'cliente');
INSERT INTO Utente (email, passwordHash, tipoUtente) VALUES ('paolo.franchi@gmail.com', '$2y$10$MTVuwvgbI/8zr8RdcwkqU.ggs8pVNDXvBSHMixZus95XdEN4sMjla', 'cliente');

-- Inserimento clienti
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('BLLVLR79L03D142C', 'Valerio Bellandi', 'valerio.bellandi@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('RSSMRA77R21C933V', 'Mario Rossi', 'mario.rossi@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('BNCTMS04T13C933D', 'Tommaso Bianchi', 'tommaso.bianchi@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('GLLCHR04R70C933Q', 'Chiara Galletti', 'chiara.galletti@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('ZRMFNC05B63C933H', 'Francesca Zermani', 'francesca.zermani@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('FRCNDR04M02C933D', 'Andrea Fraccaro', 'andrea.fraccaro@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('GLLFRC04E11C933P', 'Federico Gallo', 'federico.gallo@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('FZALNE04H58C933X', 'Elena Fazio', 'elena.fazio@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('PZZGRG04R71C933U', 'Giorgia Pezzotta', 'giorgia.pezzotta@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('GRNLCU91E50F205Q', 'Lucia Grandi', 'lucia.grandi@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('RSSLRA89B41A662P', 'Laura Rossi', 'laura.rossi@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('FRRVLL87C60H501N', 'Valeria Ferrari', 'valeria.ferrari@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('BNCGNN94E45D612M', 'Gianni Bianchi', 'gianni.bianchi@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('MRTFNC85A01F205L', 'Francesca Marti', 'francesca.marti@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('VRDSMN92B12A662K', 'Simone Verdi', 'simone.verdi@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('NRCGLL90C41H501J', 'Gallo Enrico', 'gallo.enrico@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('BRTMRC91A01F205Z', 'Marco Bartoli', 'marco.bartoli@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('SNTMRA93C60D612X', 'Marta Santini', 'marta.santini@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('PLLLGI85M01H501Y', 'Luigi Pellegrini', 'luigi.pellegrini@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('FRNLCU90A41F205W', 'Franco Luciani', 'franco.luciani@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('RCCPLA92C60D612V', 'Paola Ricci', 'paola.ricci@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('MNTGNN94E45D612U', 'Gianna Monti', 'gianna.monti@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('VNTLRA89B41A662T', 'Lara Venturi', 'lara.venturi@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('CRSMRA85M01H501S', 'Mario Caruso', 'mario.caruso@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('BLUGPP80B12F205R', 'Giuseppe Blu', 'giuseppe.blu@gmail.com');
INSERT INTO Cliente (codiceFiscale, nome, email) VALUES ('FRNPLA86A01D612Q', 'Paolo Franchi', 'paolo.franchi@gmail.com');

-- Inserimento tessere fedeltà
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('ZRMFNC05B63C933H', '2023-04-05', 4, 500);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('BNCTMS04T13C933D', '2023-02-15', 2, 250);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('GLLCHR04R70C933Q', '2023-03-20', 3, 0);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('FRCNDR04M02C933D', '2023-05-12', 6, 75);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('FZALNE04H58C933X', '2023-07-22', 2, 60);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('GLLFRC04E11C933P', '2023-06-18', 1, 320);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('PZZGRG04R71C933U', '2023-08-30', 3, 410);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('RSSLRA89B41A662P', '2023-10-01', 5, 15);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('FRRVLL87C60H501N', '2022-12-12', 6, 120);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('GRNLCU91E50F205Q', '2023-09-14', 4, 340);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('BNCGNN94E45D612M', '2022-11-11', 2, 80);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('PLLLGI85M01H501Y', '2022-10-10', 3, 200);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('BLUGPP80B12F205R', '2023-04-22', 6, 90);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('FRNPLA86A01D612Q', '2023-05-05', 5, 180);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('BRTMRC91A01F205Z', '2023-12-01', 3, 350);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('SNTMRA93C60D612X', '2024-01-15', 5, 370);
INSERT INTO TesseraFedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) VALUES ('NRCGLL90C41H501J', '2023-06-06', 1, 60);

-- Storico tessere inizialmente è vuoto

-- Fatture
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('RSSMRA77R21C933V', 1, '2021-02-10', 50, 139.98, 139.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('RSSMRA77R21C933V', 2, '2022-03-11', 89.98, 89.98, 89.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, scontoPercentuale, totaleOriginale, totalePagato) VALUES ('RSSMRA77R21C933V', 3, '2023-04-12', 127, 15, 149.98, 127.48);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('BNCTMS04T13C933D', 2, '2020-01-13', 45, 119.98, 119.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('GLLCHR04R70C933Q', 3, '2020-02-14', 60, 144.98, 144.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, scontoPercentuale, totaleOriginale, totalePagato) VALUES ('ZRMFNC05B63C933H', 4, '2020-03-15', 85, 5, 89.98, 85.48);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('FRCNDR04M02C933D', 5, '2020-04-16', 20, 79.98, 79.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('GLLFRC04E11C933P', 1, '2020-05-17', 70, 179.98, 179.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, scontoPercentuale, totaleOriginale, totalePagato) VALUES ('FZALNE04H58C933X', 2, '2020-06-18', 99, 5, 104.98, 99.73);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('PZZGRG04R71C933U', 3, '2021-07-19', 35, 119.98, 119.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('GRNLCU91E50F205Q', 4, '2021-08-20', 154.98, 154.98, 154.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('RSSLRA89B41A662P', 5, '2021-09-21', 55, 104.98, 104.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('FRRVLL87C60H501N', 4, '2021-10-22', 40, 119.98, 119.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('BNCGNN94E45D612M', 3, '2022-11-23', 60, 154.98, 154.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('PLLLGI85M01H501Y', 2, '2022-12-24', 25, 104.98, 104.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('BLUGPP80B12F205R', 6, '2023-01-25', 80, 149.98, 149.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('FRNPLA86A01D612Q', 5, '2023-02-26', 50, 89.98, 89.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('NRCGLL90C41H501J', 1, '2024-03-27', 60, 104.98, 104.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('BRTMRC91A01F205Z', 2, '2024-04-28', 35, 89.98, 89.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('SNTMRA93C60D612X', 3, '2024-05-29', 45, 124.98, 124.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('PLLLGI85M01H501Y', 4, '2024-06-30', 70, 154.98, 154.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('FRNPLA86A01D612Q', 5, '2025-05-31', 80, 149.98, 149.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('BLUGPP80B12F205R', 6, '2025-06-01', 60, 104.98, 104.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('BLLVLR79L03D142C', 1, '2025-06-15', 80, 249.98, 249.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('RSSMRA77R21C933V', 2, '2025-06-16', 60, 199.98, 199.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('BNCTMS04T13C933D', 3, '2025-06-17', 90, 299.97, 299.97);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, scontoPercentuale, totaleOriginale, totalePagato) VALUES ('GLLCHR04R70C933Q', 4, '2025-06-18', 199, 5, 209.98, 199.48);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('ZRMFNC05B63C933H', 5, '2025-06-19', 70, 179.98, 179.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('FRCNDR04M02C933D', 6, '2025-06-20', 60, 149.98, 149.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('GLLFRC04E11C933P', 1, '2025-06-21', 100, 249.98, 249.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('FZALNE04H58C933X', 2, '2025-06-22', 80, 159.98, 159.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('PZZGRG04R71C933U', 3, '2025-06-23', 120, 299.97, 299.97);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('GRNLCU91E50F205Q', 4, '2025-06-24', 60, 149.98, 149.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('RSSLRA89B41A662P', 5, '2025-06-25', 90, 299.97, 299.97);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('FRRVLL87C60H501N', 6, '2025-06-26', 70, 179.98, 179.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('BNCGNN94E45D612M', 1, '2025-06-27', 80, 159.98, 159.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('PLLLGI85M01H501Y', 2, '2025-06-28', 120, 299.97, 299.97);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('BLUGPP80B12F205R', 3, '2025-06-29', 60, 149.98, 149.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('FRNPLA86A01D612Q', 4, '2025-06-30', 90, 299.97, 299.97);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('BRTMRC91A01F205Z', 5, '2025-07-01', 70, 179.98, 179.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('SNTMRA93C60D612X', 6, '2025-07-02', 80, 159.98, 159.98);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('NRCGLL90C41H501J', 1, '2025-07-03', 120, 299.97, 299.97);
INSERT INTO Fattura (codiceFiscale, idNegozio, dataAcquisto, puntiAccumulati, totaleOriginale, totalePagato) VALUES ('MRTFNC85A01F205L', 2, '2025-07-04', 60, 149.98, 149.98);

-- Fattura Dettagli
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (1, 1, '41', 1, 59.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (1, 2, '41', 1, 79.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (2, 3, '37', 1, 49.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (2, 4, '40', 1, 39.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (3, 2, '41', 1, 99.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (3, 3, '38', 1, 49.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (4, 4, '40', 1, 49.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (4, 5, '39', 1, 69.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (5, 5, '39', 1, 69.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (5, 6, '41', 1, 99.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (6, 7, '38', 1, 59.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (6, 8, '40', 1, 29.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (7, 8, '41', 1, 29.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (7, 9, '42', 1, 49.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (8, 9, '42', 1, 79.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (8, 10, '39', 1, 99.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (9, 10, '39', 1, 99.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (9, 11, '38', 1, 59.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (10, 11, '38', 1, 59.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (10, 12, '41', 1, 44.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (11, 12, '41', 1, 44.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (11, 13, '38', 1, 69.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (12, 13, '38', 1, 69.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (12, 14, '40', 1, 29.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (13, 14, '40', 1, 29.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (13, 15, '39', 1, 89.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (14, 15, '39', 1, 89.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (14, 16, '41', 1, 54.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (15, 16, '41', 1, 54.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (15, 17, '39', 1, 64.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (16, 17, '39', 1, 64.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (16, 18, '42', 1, 39.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (17, 18, '42', 1, 39.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (17, 19, '38', 1, 49.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (18, 19, '38', 1, 49.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (18, 20, '43', 1, 74.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (19, 20, '43', 1, 74.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (19, 21, '42', 1, 89.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (20, 21, '42', 1, 89.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (20, 22, '37', 1, 64.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (21, 22, '37', 1, 64.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (21, 23, '44', 1, 39.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (22, 23, '44', 1, 39.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (22, 24, '43', 1, 49.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (23, 24, '43', 1, 49.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (23, 25, '42', 1, 74.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (24, 5, '39', 2, 124.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (25, 6, '41', 2, 99.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (26, 7, '38', 3, 99.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (27, 8, '40', 2, 99.74);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (28, 9, '42', 2, 89.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (29, 10, '39', 2, 74.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (30, 11, '38', 2, 124.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (31, 12, '41', 2, 79.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (32, 13, '38', 3, 99.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (33, 14, '40', 2, 74.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (34, 15, '39', 3, 99.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (35, 16, '41', 2, 89.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (36, 17, '39', 2, 79.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (37, 18, '42', 3, 99.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (38, 19, '38', 2, 74.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (39, 20, '43', 3, 99.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (40, 21, '42', 2, 89.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (41, 22, '37', 2, 79.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (42, 23, '44', 3, 99.99);
INSERT INTO FatturaDettagli (idFattura, idProdotto, taglia, quantità, prezzoUnitario) VALUES (43, 24, '43', 2, 74.99);

-- FUNZIONALITà ATTIVE DEL DB

-- SCONTO E PUNTI

-- Calcolo dello sconto in base ai punti
CREATE OR REPLACE FUNCTION calcola_sconto(punti INT, totale DECIMAL) RETURNS DECIMAL AS $$
DECLARE
    sconto DECIMAL;
BEGIN
    IF punti >= 300 THEN
        sconto := LEAST(totale * 0.30, 100);
    ELSIF punti >= 200 THEN
        sconto := LEAST(totale * 0.15, 100);
    ELSIF punti >= 100 THEN
        sconto := LEAST(totale * 0.05, 100);
    ELSE
        sconto := 0;
    END IF;

    RETURN ROUND(sconto, 2);
END;
$$ LANGUAGE plpgsql;

-- TRIGGER

-- Trigger per aggiornare il saldo punti del cliente dopo l'inserimento di una fattura
-- Gestisce sia l'accumulo dei punti che la detrazione per gli sconti
CREATE OR REPLACE FUNCTION aggiorna_saldo_punti() RETURNS TRIGGER AS $$
DECLARE
    punti_da_scalare INTEGER := 0;
    punti_attuali INTEGER;
    punti_guadagnati INTEGER;
BEGIN
    -- Aggiorna solo se il cliente ha una tessera
    IF EXISTS (SELECT 1 FROM shoepal.tesserafedeltà WHERE codicefiscale = NEW.codicefiscale) THEN
        
        -- Ottiene i punti attuali
        SELECT saldopunti INTO punti_attuali 
        FROM shoepal.tesserafedeltà 
        WHERE codicefiscale = NEW.codicefiscale;
        
        -- Calcola i punti guadagnati da questo acquisto
        punti_guadagnati := FLOOR(NEW.puntiaccumulati);
        
        -- Determina i punti da scalare per lo sconto (se presente)
        IF NEW.scontoPercentuale IS NOT NULL AND NEW.scontoPercentuale > 0 THEN
            IF NEW.scontoPercentuale = 5 THEN
                punti_da_scalare := 100;
            ELSIF NEW.scontoPercentuale = 15 THEN
                punti_da_scalare := 200;
            ELSIF NEW.scontoPercentuale = 30 THEN
                punti_da_scalare := 300;
            ELSE
                -- Per eventuali altri sconti, calcola in base all'importo
                punti_da_scalare := CEIL((NEW.totaleOriginale - NEW.totalePagato));
            END IF;
            
            -- Verifica che ci siano abbastanza punti PRIMA dell'acquisto
            IF punti_attuali < punti_da_scalare THEN
                RAISE EXCEPTION 'Punti insufficienti per applicare lo sconto. Servono % punti ma ne hai solo %', 
                    punti_da_scalare, punti_attuali;
            END IF;
        END IF;
        
        -- Aggiorna il saldo: punti attuali + punti guadagnati - punti spesi per sconto
        UPDATE shoepal.tesserafedeltà
        SET saldopunti = punti_attuali + punti_guadagnati - punti_da_scalare
        WHERE codicefiscale = NEW.codicefiscale;
        
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_aggiorna_saldo_punti AFTER INSERT ON Fattura FOR EACH ROW EXECUTE FUNCTION aggiorna_saldo_punti();

-- TESSERE E GESTIONE TESSERE

-- Trigger per copiare le tessere in storico quando un negozio viene chiuso
CREATE OR REPLACE FUNCTION copia_tessere_in_storico() RETURNS TRIGGER AS $$
BEGIN
    IF OLD.attivo = true AND NEW.attivo = false THEN
        -- Copia tutte le tessere associate al negozio che sta venendo chiuso
        INSERT INTO shoepal.StoricoTessere (idTessera, codiceFiscale, dataRichiesta, idNegozio, saldoPunti, idNegozioTrasferito)
        SELECT idtessera, codicefiscale, datarichiesta, idnegozio, saldopunti, NULL
        FROM shoepal.tesserafedeltà
        WHERE idnegozio = OLD.idnegozio;

        -- Elimina le tessere copiate
        DELETE FROM shoepal.tesserafedeltà
        WHERE idnegozio = OLD.idnegozio;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_copia_tessere_in_storico BEFORE UPDATE ON Negozio FOR EACH ROW EXECUTE FUNCTION copia_tessere_in_storico();

-- Ripristino di una tessera dallo storico
CREATE OR REPLACE FUNCTION ripristina_tessera(p_idTessera INT, p_idNuovoNegozio INT) RETURNS TEXT AS $$
DECLARE
    r shoepal.StoricoTessere;
    tessera_attiva INT;
BEGIN
    -- Recupera la tessera dallo storico
    SELECT * INTO r
    FROM shoepal.StoricoTessere
    WHERE idtessera = p_idTessera;

    IF NOT FOUND THEN
        RETURN 'Tessera non trovata nello storico';
    END IF;

    -- Verifica se è già stata trasferita
    IF r.idnegoziotrasferito IS NOT NULL THEN
        RETURN 'Tessera già trasferita - punti già utilizzati';
    END IF;

    -- Controlla se il cliente ha una tessera attiva
    SELECT COUNT(*) INTO tessera_attiva
    FROM shoepal.tesserafedeltà
    WHERE codicefiscale = r.codicefiscale;

    IF tessera_attiva > 0 THEN
        -- Se ha già una tessera attiva, verifica se i punti di questa tessera storica sono già stati trasferiti
        -- controllando se esiste una tessera storica con saldopunti = 0 e stesso codicefiscale
        IF r.saldopunti = 0 THEN
            RETURN 'I punti di questa tessera sono già stati trasferiti alla tessera attiva';
        ELSE
            RETURN 'Il cliente ha già una tessera attiva. Per ripristinare questa tessera storica, deve prima eliminare quella attiva.';
        END IF;
    END IF;

    -- Inserisce una nuova tessera attiva (inizialmente con 0 punti per evitare che il trigger aggiunga punti doppi)
    INSERT INTO shoepal.tesserafedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti)
    VALUES (r.codicefiscale, CURRENT_DATE, p_idNuovoNegozio, 0);

    -- Segna che è stata trasferita PRIMA di aggiornare i punti
    UPDATE shoepal.StoricoTessere
    SET idnegoziotrasferito = p_idNuovoNegozio,
        saldopunti = 0
    WHERE idtessera = p_idTessera;
    
    -- Ora aggiorna i punti della tessera appena creata
    UPDATE shoepal.tesserafedeltà
    SET saldopunti = r.saldopunti
    WHERE codicefiscale = r.codicefiscale
      AND idnegozio = p_idNuovoNegozio
      AND datarichiesta = CURRENT_DATE;

    RETURN 'Tessera ripristinata con successo con ' || r.saldopunti || ' punti';
END;
$$ LANGUAGE plpgsql;

-- Trigger per trasferire automaticamente i punti da una tessera nello storico alla nuova tessera quando viene inserita una nuova tessera per lo stesso cliente
CREATE OR REPLACE FUNCTION trasferisci_punti_auto() RETURNS TRIGGER AS $$
DECLARE
    idTesseraStorica INT;
    punti INT;
    punti_trasferiti INT := 0;
BEGIN
    -- Cerca tessera storica non ancora trasferita con punti > 0
    SELECT idtessera, saldopunti INTO idTesseraStorica, punti
    FROM shoepal.StoricoTessere
    WHERE codicefiscale = NEW.codicefiscale
      AND idnegoziotrasferito IS NULL
      AND saldopunti > 0
    ORDER BY datarichiesta DESC  -- Prende la più recente
    LIMIT 1;

    -- Se trovata, trasferisce i punti
    IF FOUND AND punti > 0 THEN
        -- Aggiunge i punti alla nuova tessera
        UPDATE shoepal.tesserafedeltà
        SET saldopunti = saldopunti + punti
        WHERE idtessera = NEW.idtessera;

        -- Segna la vecchia come trasferita e azzera punti
        UPDATE shoepal.StoricoTessere
        SET idnegoziotrasferito = NEW.idnegozio,
            saldopunti = 0
        WHERE idtessera = idTesseraStorica;
        
        punti_trasferiti := punti;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_verifica_storico_tessere AFTER INSERT ON shoepal.tesserafedeltà FOR EACH ROW EXECUTE FUNCTION trasferisci_punti_auto();

-- FUNZIONE PER SPOSTARE PRODOTTI TRA NEGOZI
CREATE OR REPLACE FUNCTION sposta_prodotto_tra_negozi(
    p_idnegozio_origine INTEGER,
    p_idnegozio_dest INTEGER, 
    p_idprodotto INTEGER,
    p_taglia VARCHAR,
    p_quantita INTEGER
) RETURNS BOOLEAN AS $$
DECLARE
    v_disponibilita_origine RECORD;
    v_disponibilita_dest RECORD;
    v_nuova_quantita_origine INTEGER;
    v_nuova_quantita_dest INTEGER;
BEGIN
    -- Verifica che i parametri siano validi
    IF p_quantita <= 0 THEN
        RAISE EXCEPTION 'La quantità deve essere maggiore di zero';
    END IF;
    
    -- Ottieni disponibilità nel negozio di origine
    SELECT quantità, prezzo INTO v_disponibilita_origine
    FROM shoepal.disponibilità 
    WHERE idnegozio = p_idnegozio_origine 
      AND idprodotto = p_idprodotto 
      AND taglia = p_taglia;
    
    -- Calcola nuova quantità per il negozio di origine
    v_nuova_quantita_origine := v_disponibilita_origine.quantità - p_quantita;
    
    -- Aggiorna o rimuovi dal negozio di origine
    IF v_nuova_quantita_origine = 0 THEN
        -- Rimuovi completamente se quantità diventa zero
        DELETE FROM shoepal.disponibilità 
        WHERE idnegozio = p_idnegozio_origine 
          AND idprodotto = p_idprodotto 
          AND taglia = p_taglia;
    ELSE
        -- Aggiorna quantità nel negozio di origine
        UPDATE shoepal.disponibilità 
        SET quantità = v_nuova_quantita_origine
        WHERE idnegozio = p_idnegozio_origine 
          AND idprodotto = p_idprodotto 
          AND taglia = p_taglia;
    END IF;
    
    -- Verifica se il prodotto esiste già nel negozio di destinazione
    SELECT quantità INTO v_disponibilita_dest
    FROM shoepal.disponibilità 
    WHERE idnegozio = p_idnegozio_dest 
      AND idprodotto = p_idprodotto 
      AND taglia = p_taglia;
    
    IF FOUND THEN
        -- Il prodotto esiste già: aggiungi alla quantità esistente
        v_nuova_quantita_dest := v_disponibilita_dest.quantità + p_quantita;
        
        UPDATE shoepal.disponibilità 
        SET quantità = v_nuova_quantita_dest
        WHERE idnegozio = p_idnegozio_dest 
          AND idprodotto = p_idprodotto 
          AND taglia = p_taglia;
    ELSE
        -- Il prodotto non esiste: inserisci nuovo record
        INSERT INTO shoepal.disponibilità (idnegozio, idprodotto, taglia, quantità, prezzo) 
        VALUES (p_idnegozio_dest, p_idprodotto, p_taglia, p_quantita, v_disponibilita_origine.prezzo);
    END IF;
    
    RETURN true;
END;
$$ LANGUAGE plpgsql;

-- VISTE

-- CLIENTI CON PIÙ DI 300 PUNTI
CREATE VIEW TesserePremium AS
SELECT c.codiceFiscale, c.nome, t.idTessera, t.saldoPunti
FROM Cliente c
JOIN shoepal.tesserafedeltà t ON c.codiceFiscale = t.codiceFiscale
WHERE t.saldoPunti > 300;


--VENDITE EFFETTUATE IN UN GIORNO
CREATE MATERIALIZED VIEW StatisticheVenditePerGiorno AS
SELECT dataAcquisto, COUNT(*) AS numeroFatture, SUM(totalePagato) AS incassoTotale
FROM Fattura
GROUP BY dataAcquisto
ORDER BY dataAcquisto DESC;

-- STORICO ORDINI AI FORNITORI
CREATE OR REPLACE VIEW StoricoOrdiniFornitore AS
SELECT 
    o.idOrdine,
    o.dataConsegna,
    o.partitaIVA,
    f.indirizzo,
    d.idProdotto,
    d.taglia,
    d.quantità,
    d.prezzo
FROM shoepal.Ordine o
JOIN shoepal.Fornitore f ON o.partitaIVA = f.partitaIVA
JOIN shoepal.OrdineDettagli d ON o.idOrdine = d.idOrdine
ORDER BY o.dataConsegna DESC;

-- FUNZIONI UTILITÀ: NEGOZI

-- Ottiene tutti i negozi con informazioni complete per il manager. Include negozi attivi e chiusi con date di chiusura
CREATE OR REPLACE FUNCTION get_all_negozi_manager()
RETURNS TABLE (
    idnegozio INTEGER,
    responsabile VARCHAR(100),
    indirizzo TEXT,
    attivo BOOLEAN,
    datachiusura DATE
) AS $$
BEGIN
    RETURN QUERY
    SELECT n.idnegozio, n.responsabile, n.indirizzo, n.attivo, n.datachiusura 
    FROM shoepal.negozio n 
    ORDER BY n.idnegozio;
END;
$$ LANGUAGE plpgsql;

-- Ottiene solo i negozi attivi ordinati per responsabile
CREATE OR REPLACE FUNCTION get_negozi_attivi()
RETURNS TABLE (
    idnegozio INTEGER,
    responsabile VARCHAR(100),
    indirizzo TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT n.idnegozio, n.responsabile, n.indirizzo 
    FROM shoepal.negozio n 
    WHERE n.attivo = true 
    ORDER BY n.responsabile;
END;
$$ LANGUAGE plpgsql;

-- Ottiene gli orari di apertura di un negozio ordinati per giorno della settimana
CREATE OR REPLACE FUNCTION get_orari_negozio(p_idnegozio INTEGER)
RETURNS TABLE (
    giorno VARCHAR(10),
    orainizio TIME,
    orafine TIME
) AS $$
BEGIN
    RETURN QUERY
    SELECT o.giorno, o.orainizio, o.orafine 
    FROM shoepal.orario o 
    WHERE o.idnegozio = p_idnegozio 
    ORDER BY 
        CASE o.giorno 
            WHEN 'Lunedì' THEN 1 
            WHEN 'Martedì' THEN 2 
            WHEN 'Mercoledì' THEN 3 
            WHEN 'Giovedì' THEN 4 
            WHEN 'Venerdì' THEN 5 
            WHEN 'Sabato' THEN 6 
            WHEN 'Domenica' THEN 7 
        END;
END;
$$ LANGUAGE plpgsql;

-- FUNZIONI UTILITÀ: PRODOTTI E FORNITORI

-- Ottiene tutti i prodotti con informazioni aggiuntive per il manager.Include statistiche sui fornitori e disponibilità totali
CREATE OR REPLACE FUNCTION get_all_prodotti_manager()
RETURNS TABLE (
    idprodotto INTEGER,
    nome VARCHAR(100),
    descrizione TEXT,
    marca VARCHAR(30),
    sesso VARCHAR(10),
    tipologia VARCHAR(20),
    num_fornitori BIGINT,
    costo_minimo DECIMAL(8,2),
    costo_massimo DECIMAL(8,2),
    disponibilita_totale BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT p.idprodotto, p.nome, p.descrizione, p.marca, p.sesso, p.tipologia,
           COUNT(f.partitaiva) as num_fornitori,
           MIN(f.costo) as costo_minimo,
           MAX(f.costo) as costo_massimo,
           SUM(f.disponibilità) as disponibilita_totale
    FROM shoepal.prodotto p
    LEFT JOIN shoepal.fornitura f ON p.idprodotto = f.idprodotto
    GROUP BY p.idprodotto, p.nome, p.descrizione, p.marca, p.sesso, p.tipologia
    ORDER BY p.nome;
END;
$$ LANGUAGE plpgsql;

-- Ottiene il costo minimo di un prodotto dai fornitori
CREATE OR REPLACE FUNCTION get_costo_minimo_prodotto(p_idprodotto INTEGER)
RETURNS DECIMAL(8,2) AS $$
DECLARE
    costo_min DECIMAL(8,2);
BEGIN
    SELECT MIN(costo) INTO costo_min 
    FROM shoepal.fornitura 
    WHERE idprodotto = p_idprodotto AND disponibilità > 0;
    
    RETURN COALESCE(costo_min, 50.0);
END;
$$ LANGUAGE plpgsql;

-- Ottiene la disponibilità massima di un prodotto dai fornitori
CREATE OR REPLACE FUNCTION get_disponibilita_massima_prodotto(p_idprodotto INTEGER)
RETURNS INTEGER AS $$
DECLARE
    disp_max INTEGER;
BEGIN
    SELECT MAX(disponibilità) INTO disp_max 
    FROM shoepal.fornitura 
    WHERE idprodotto = p_idprodotto AND disponibilità > 0;
    
    RETURN COALESCE(disp_max, 0);
END;
$$ LANGUAGE plpgsql;

-- FUNZIONI UTILITÀ: DISPONIBILITÀ NEGOZI

-- Ottiene tutte le disponibilità organizzate per negozio con dettagli prodotto
CREATE OR REPLACE FUNCTION get_disponibilita_by_negozio()
RETURNS TABLE (
    idnegozio INTEGER,
    responsabile VARCHAR(100),
    idprodotto INTEGER,
    nome VARCHAR(100),
    taglia VARCHAR(10),
    prezzo DECIMAL(8,2),
    quantità INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT d.idnegozio, n.responsabile, d.idprodotto, p.nome, d.taglia, d.prezzo, d.quantità
    FROM shoepal.disponibilità d
    JOIN shoepal.negozio n ON d.idnegozio = n.idnegozio
    JOIN shoepal.prodotto p ON d.idprodotto = p.idprodotto
    ORDER BY d.idnegozio, p.nome, d.taglia;
END;
$$ LANGUAGE plpgsql;

-- FUNZIONI UTILITÀ: FORNITORI E FORNITURE

-- Ottiene tutti i fornitori
CREATE OR REPLACE FUNCTION get_all_fornitori()
RETURNS TABLE (
    partitaiva VARCHAR(11),
    indirizzo TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT f.partitaiva, f.indirizzo 
    FROM shoepal.fornitore f 
    ORDER BY f.partitaiva;
END;
$$ LANGUAGE plpgsql;

-- Ottiene un fornitore per partita IVA
CREATE OR REPLACE FUNCTION get_fornitore_by_piva(p_partitaiva VARCHAR(11))
RETURNS TABLE (
    partitaiva VARCHAR(11),
    indirizzo TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT f.partitaiva, f.indirizzo 
    FROM shoepal.fornitore f 
    WHERE f.partitaiva = p_partitaiva;
END;
$$ LANGUAGE plpgsql;

-- Ottiene i prodotti più ordinati da un fornitore specifico
CREATE OR REPLACE FUNCTION get_prodotti_piu_ordinati_fornitore(p_partitaiva VARCHAR(11), p_limit INTEGER DEFAULT 5)
RETURNS TABLE (
    idprodotto INTEGER,
    nome VARCHAR(100),
    marca VARCHAR(30),
    tipologia VARCHAR(20),
    quantita_totale_ordinata BIGINT,
    numero_ordini BIGINT,
    prezzo_medio DECIMAL
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        p.idprodotto,
        p.nome,
        p.marca,
        p.tipologia,
        SUM(s.quantità) as quantita_totale_ordinata,
        COUNT(DISTINCT s.idordine) as numero_ordini,
        AVG(s.prezzo) as prezzo_medio
    FROM shoepal.StoricoOrdiniFornitore s
    JOIN shoepal.prodotto p ON s.idprodotto = p.idprodotto
    WHERE s.partitaiva = p_partitaiva
    GROUP BY p.idprodotto, p.nome, p.marca, p.tipologia
    ORDER BY quantita_totale_ordinata DESC
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql;

-- Ottiene le forniture di un fornitore specifico
CREATE OR REPLACE FUNCTION get_forniture_by_fornitore(p_partitaiva VARCHAR(11))
RETURNS TABLE (
    idprodotto INTEGER,
    disponibilità INTEGER,
    costo DECIMAL(8,2),
    taglia VARCHAR(10),
    nome VARCHAR(100),
    marca VARCHAR(30)
) AS $$
BEGIN
    RETURN QUERY
    SELECT f.idprodotto, f.disponibilità, f.costo, f.taglia, p.nome, p.marca 
    FROM shoepal.fornitura f
    JOIN shoepal.prodotto p ON f.idprodotto = p.idprodotto
    WHERE f.partitaiva = p_partitaiva
    ORDER BY p.nome, f.taglia;
END;
$$ LANGUAGE plpgsql;

-- Ottiene statistiche di un fornitore specifico
CREATE OR REPLACE FUNCTION get_fornitore_statistiche(p_partitaiva VARCHAR(11))
RETURNS TABLE (
    prodotti_totali BIGINT,
    scorte_totali BIGINT,
    valore_totale NUMERIC,
    costo_medio DECIMAL,
    costo_minimo DECIMAL(8,2),
    costo_massimo DECIMAL(8,2),
    tipologie_prodotti BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COUNT(DISTINCT f.idprodotto) as prodotti_totali,
        SUM(f.disponibilità) as scorte_totali,
        SUM(f.costo * f.disponibilità) as valore_totale,
        AVG(f.costo) as costo_medio,
        MIN(f.costo) as costo_minimo,
        MAX(f.costo) as costo_massimo,
        COUNT(DISTINCT p.tipologia) as tipologie_prodotti
    FROM shoepal.fornitura f
    JOIN shoepal.prodotto p ON f.idprodotto = p.idprodotto
    WHERE f.partitaiva = p_partitaiva;
END;
$$ LANGUAGE plpgsql;

-- Ottiene forniture raggruppate per taglia di un fornitore
CREATE OR REPLACE FUNCTION get_forniture_per_taglia(p_partitaiva VARCHAR(11))
RETURNS TABLE (
    idprodotto INTEGER,
    nome VARCHAR(100),
    marca VARCHAR(30),
    tipologia VARCHAR(20),
    taglia VARCHAR(10),
    disponibilità INTEGER,
    costo DECIMAL(8,2)
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        f.idprodotto,
        p.nome,
        p.marca,
        p.tipologia,
        f.taglia,
        f.disponibilità,
        f.costo
    FROM shoepal.fornitura f
    JOIN shoepal.prodotto p ON f.idprodotto = p.idprodotto
    WHERE f.partitaiva = p_partitaiva
    ORDER BY p.nome, f.taglia;
END;
$$ LANGUAGE plpgsql;

-- Ottiene cronologia ordini per fornitore
CREATE OR REPLACE FUNCTION get_cronologia_ordini_fornitore(p_partitaiva VARCHAR(11), p_limit INTEGER DEFAULT 10)
RETURNS TABLE (
    idordine INTEGER,
    dataconsegna DATE,
    responsabile VARCHAR(100),
    negozio_indirizzo TEXT,
    num_prodotti BIGINT,
    valore_totale NUMERIC
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        s.idordine,
        s.dataconsegna,
        n.responsabile,
        s.indirizzo as negozio_indirizzo,
        COUNT(s.idprodotto) as num_prodotti,
        SUM(s.quantità * s.prezzo) as valore_totale
    FROM shoepal.StoricoOrdiniFornitore s
    LEFT JOIN shoepal.ordine o ON s.idordine = o.idordine
    LEFT JOIN shoepal.negozio n ON o.idnegozio = n.idnegozio
    WHERE s.partitaiva = p_partitaiva
    GROUP BY s.idordine, s.dataconsegna, n.responsabile, s.indirizzo
    ORDER BY s.dataconsegna DESC
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql;

-- FUNZIONI UTILITÀ: ORDINI CLIENTI

-- Ottiene preview di un ordine per validazione prima dell'acquisto
CREATE OR REPLACE FUNCTION get_order_preview(p_idprodotto INTEGER, p_quantita INTEGER, p_taglia VARCHAR(10))
RETURNS TABLE (
    available BOOLEAN,
    message TEXT,
    partitaiva VARCHAR(11),
    indirizzo TEXT,
    costo DECIMAL(8,2),
    disponibilità INTEGER,
    max_disponibile INTEGER,
    quantita_richiesta INTEGER
) AS $$
DECLARE
    result_record RECORD;
    max_info RECORD;
BEGIN
    -- Cerca fornitore con costo minimo e disponibilità sufficiente
    SELECT f.partitaiva, f.costo, f.disponibilità, fo.indirizzo
    INTO result_record
    FROM shoepal.fornitura f
    JOIN shoepal.fornitore fo ON f.partitaiva = fo.partitaiva
    WHERE f.idprodotto = p_idprodotto AND f.taglia = p_taglia AND f.disponibilità >= p_quantita
    ORDER BY f.costo ASC 
    LIMIT 1;
    
    IF FOUND THEN
        -- Prodotto disponibile
        RETURN QUERY SELECT 
            true as available,
            'Prodotto disponibile'::TEXT as message,
            result_record.partitaiva,
            result_record.indirizzo,
            result_record.costo,
            result_record.disponibilità,
            0 as max_disponibile,
            p_quantita as quantita_richiesta;
    ELSE
        -- Verifica disponibilità massima
        SELECT MAX(f.disponibilità) as max_disponibile, COUNT(*) as num_fornitori
        INTO max_info
        FROM shoepal.fornitura f
        WHERE f.idprodotto = p_idprodotto AND f.taglia = p_taglia AND f.disponibilità > 0;
        
        IF max_info.max_disponibile > 0 THEN
            RETURN QUERY SELECT 
                false as available,
                ('Quantità richiesta (' || p_quantita || ' paia) non disponibile per taglia ' || p_taglia || '. Massimo disponibile: ' || max_info.max_disponibile || ' paia')::TEXT as message,
                -- casting usato per evitare errori di tipo
                NULL::VARCHAR(11) as partitaiva,
                NULL::TEXT as indirizzo,
                NULL::DECIMAL(8,2) as costo,
                NULL::INTEGER as disponibilità,
                max_info.max_disponibile as max_disponibile,
                p_quantita as quantita_richiesta;
        ELSE
            RETURN QUERY SELECT 
                false as available,
                ('Prodotto non disponibile in taglia ' || p_taglia || ' presso nessun fornitore')::TEXT as message,
                NULL::VARCHAR(11) as partitaiva,
                NULL::TEXT as indirizzo,
                NULL::DECIMAL(8,2) as costo,
                NULL::INTEGER as disponibilità,
                0 as max_disponibile,
                p_quantita as quantita_richiesta;
        END IF;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Ottiene tutti gli ordini con informazioni aggregate
CREATE OR REPLACE FUNCTION get_ordini_raggruppati_per_ordine(p_filtrofornitore VARCHAR(11) DEFAULT '')
RETURNS TABLE (
    idordine INTEGER,
    dataconsegna DATE,
    partitaiva VARCHAR(11),
    idnegozio INTEGER,
    nome_negozio TEXT,
    fornitore_indirizzo TEXT,
    num_prodotti BIGINT,
    valore_totale NUMERIC
) AS $$
BEGIN
    IF p_filtrofornitore = '' THEN
        RETURN QUERY
        SELECT s.idordine, s.dataconsegna, s.partitaiva, o.idnegozio,
               CASE 
                   WHEN n.indirizzo IS NOT NULL THEN 
                       'ShoePal ' || UPPER(SUBSTRING(
                           TRIM(SUBSTRING(n.indirizzo FROM POSITION(',' IN n.indirizzo) + 1)) FROM 1 FOR 1
                       )) || LOWER(SUBSTRING(
                           TRIM(SUBSTRING(n.indirizzo FROM POSITION(',' IN n.indirizzo) + 1)) FROM 2
                       ))
                   ELSE 'Negozio Sconosciuto'
               END as nome_negozio,
               s.indirizzo as fornitore_indirizzo,
               COUNT(s.idprodotto) as num_prodotti,
               SUM(s.quantità * s.prezzo) as valore_totale
        FROM shoepal.StoricoOrdiniFornitore s
        LEFT JOIN shoepal.ordine o ON s.idordine = o.idordine
        LEFT JOIN shoepal.negozio n ON o.idnegozio = n.idnegozio
        GROUP BY s.idordine, s.dataconsegna, s.partitaiva, o.idnegozio, n.indirizzo, s.indirizzo
        ORDER BY s.idordine DESC;
    ELSE
        RETURN QUERY
        SELECT s.idordine, s.dataconsegna, s.partitaiva, o.idnegozio,
               CASE 
                   WHEN n.indirizzo IS NOT NULL THEN 
                       'ShoePal ' || UPPER(SUBSTRING(
                           TRIM(SUBSTRING(n.indirizzo FROM POSITION(',' IN n.indirizzo) + 1)) FROM 1 FOR 1
                       )) || LOWER(SUBSTRING(
                           TRIM(SUBSTRING(n.indirizzo FROM POSITION(',' IN n.indirizzo) + 1)) FROM 2
                       ))
                   ELSE 'Negozio Sconosciuto'
               END as nome_negozio,
               s.indirizzo as fornitore_indirizzo,
               COUNT(s.idprodotto) as num_prodotti,
               SUM(s.quantità * s.prezzo) as valore_totale
        FROM shoepal.StoricoOrdiniFornitore s
        LEFT JOIN shoepal.ordine o ON s.idordine = o.idordine
        LEFT JOIN shoepal.negozio n ON o.idnegozio = n.idnegozio
        WHERE s.partitaiva = p_filtrofornitore
        GROUP BY s.idordine, s.dataconsegna, s.partitaiva, o.idnegozio, n.indirizzo, s.indirizzo
        ORDER BY s.idordine DESC;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Ottiene i dettagli di un ordine specifico
CREATE OR REPLACE FUNCTION get_dettagli_ordine(p_idordine INTEGER)
RETURNS TABLE (
    idprodotto INTEGER,
    taglia VARCHAR(10),
    quantità INTEGER,
    prezzo DECIMAL(8,2),
    nome VARCHAR(100),
    marca VARCHAR(30),
    tipologia VARCHAR(20)
) AS $$
BEGIN
    RETURN QUERY
    SELECT s.idprodotto, COALESCE(s.taglia, '42') as taglia, s.quantità, s.prezzo,
           p.nome, p.marca, p.tipologia
    FROM shoepal.StoricoOrdiniFornitore s
    JOIN shoepal.prodotto p ON s.idprodotto = p.idprodotto
    WHERE s.idordine = p_idordine
    ORDER BY p.nome;
END;
$$ LANGUAGE plpgsql;

-- FUNZIONI UTILITÀ: CLIENTI E UTENTI

-- Ottiene tutti i clienti con informazioni complete
CREATE OR REPLACE FUNCTION get_all_clienti()
RETURNS TABLE (
    codicefiscale VARCHAR(16),
    nome VARCHAR(100),
    email VARCHAR(100),
    tipoutente VARCHAR(10)
) AS $$
BEGIN
    RETURN QUERY
    SELECT c.codicefiscale, c.nome, c.email, u.tipoutente
    FROM shoepal.cliente c
    LEFT JOIN shoepal.utente u ON c.email = u.email
    ORDER BY c.nome;
END;
$$ LANGUAGE plpgsql;

-- Ottiene tutti gli utenti del sistema
CREATE OR REPLACE FUNCTION get_all_utenti()
RETURNS TABLE (
    email VARCHAR(100),
    tipoutente VARCHAR(10)
) AS $$
BEGIN
    RETURN QUERY
    SELECT u.email, u.tipoutente 
    FROM shoepal.utente u 
    ORDER BY u.email;
END;
$$ LANGUAGE plpgsql;

-- Ottiene il tipo di utente per email
CREATE OR REPLACE FUNCTION get_user_type(p_email VARCHAR(100))
RETURNS VARCHAR(10) AS $$
DECLARE
    user_type VARCHAR(10);
BEGIN
    SELECT tipoutente INTO user_type 
    FROM shoepal.utente 
    WHERE email = p_email;
    
    RETURN user_type;
END;
$$ LANGUAGE plpgsql;

-- Conta il numero di manager nel sistema
CREATE OR REPLACE FUNCTION count_managers()
RETURNS INTEGER AS $$
DECLARE
    manager_count INTEGER;
BEGIN
    SELECT COUNT(*) INTO manager_count 
    FROM shoepal.utente 
    WHERE tipoutente = 'manager';
    
    RETURN manager_count;
END;
$$ LANGUAGE plpgsql;

-- FUNZIONI UTILITÀ: TESSERE FEDELTÀ

-- Ottiene tutte le tessere fedeltà attive
CREATE OR REPLACE FUNCTION get_tessere_attive()
RETURNS TABLE (
    idtessera INTEGER,
    codicefiscale VARCHAR(16),
    nome VARCHAR(100),
    datarichiesta DATE,
    idnegozio INTEGER,
    responsabile VARCHAR(100),
    saldopunti INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT t.idtessera, t.codicefiscale, c.nome, t.datarichiesta, t.idnegozio, n.responsabile, t.saldopunti
    FROM shoepal.tesserafedeltà t
    JOIN shoepal.cliente c ON t.codicefiscale = c.codicefiscale
    JOIN shoepal.negozio n ON t.idnegozio = n.idnegozio
    ORDER BY t.saldopunti DESC;
END;
$$ LANGUAGE plpgsql;

-- Ottiene lo storico delle tessere
CREATE OR REPLACE FUNCTION get_storico_tessere()
RETURNS TABLE (
    idtessera INTEGER,
    codicefiscale VARCHAR(16),
    nome VARCHAR(100),
    datarichiesta DATE,
    idnegozio INTEGER,
    saldopunti INTEGER,
    idnegoziotrasferito INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT s.idtessera, s.codicefiscale, c.nome, s.datarichiesta, s.idnegozio, s.saldopunti, s.idnegoziotrasferito
    FROM shoepal.StoricoTessere s
    JOIN shoepal.cliente c ON s.codicefiscale = c.codicefiscale
    ORDER BY s.datarichiesta DESC;
END;
$$ LANGUAGE plpgsql;

-- Ottiene clienti con tessere per negozio
CREATE OR REPLACE FUNCTION get_clienti_tessere_per_negozio()
RETURNS TABLE (
    codicefiscale VARCHAR(16),
    nome VARCHAR(100),
    idtessera INTEGER,
    idnegozio INTEGER,
    saldopunti INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT c.codicefiscale, c.nome, t.idtessera, t.idnegozio, t.saldopunti
    FROM shoepal.cliente c
    JOIN shoepal.tesserafedeltà t ON c.codicefiscale = t.codicefiscale
    ORDER BY t.idnegozio, t.saldopunti DESC;
END;
$$ LANGUAGE plpgsql;

-- FUNZIONI UTILITÀ: STATISTICHE, VENDITE E BILANCI

-- Ottiene statistiche vendite degli ultimi 30 giorni
CREATE OR REPLACE FUNCTION get_statistiche_vendite()
RETURNS TABLE (
    dataacquisto DATE,
    numerofatture BIGINT,
    incassototale NUMERIC
) AS $$
BEGIN
    RETURN QUERY
    SELECT s.dataacquisto, s.numerofatture, s.incassototale 
    FROM shoepal.statistichevenditepergiorno s
    ORDER BY s.dataacquisto DESC 
    LIMIT 30;
END;
$$ LANGUAGE plpgsql;

-- Ottiene le tessere premium
CREATE OR REPLACE FUNCTION get_tessere_premium()
RETURNS TABLE (
    codicefiscale VARCHAR(16),
    nome VARCHAR(100),
    idtessera INTEGER,
    saldopunti INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT t.codicefiscale, t.nome, t.idtessera, t.saldopunti 
    FROM shoepal.tesserepremium t
    ORDER BY t.saldopunti DESC;
END;
$$ LANGUAGE plpgsql;

-- Ottiene le fatture di vendita per periodo specificato
-- Utilizza un paramtetro dinamico per il periodo, con default a 30 giorni
CREATE OR REPLACE FUNCTION get_fatture_vendita_per_periodo(p_periodo TEXT DEFAULT '30 days')
RETURNS TABLE (
    idfattura INTEGER,
    dataacquisto DATE,
    totale DECIMAL(10,2),
    codicefiscale VARCHAR(16),
    idprodotto INTEGER,
    taglia VARCHAR(10),
    quantità INTEGER,
    prezzo DECIMAL(8,2),
    nome VARCHAR(100),
    marca VARCHAR(30),
    tipologia VARCHAR(20),
    nome_cliente VARCHAR(100)
) AS $$
BEGIN
    RETURN QUERY EXECUTE
    'SELECT f.idfattura, f.dataacquisto, f.totalepagato as totale, f.codicefiscale,
            fd.idprodotto, fd.taglia, fd.quantità, 
            CASE 
                WHEN f.totaleoriginale > 0 THEN 
                    ROUND(fd.prezzounitario * (f.totalepagato / f.totaleoriginale), 2)
                ELSE 
                    fd.prezzounitario 
            END as prezzo,
            p.nome, p.marca, p.tipologia,
            c.nome as nome_cliente
     FROM shoepal.fattura f
     JOIN shoepal.fatturadettagli fd ON f.idfattura = fd.idfattura
     JOIN shoepal.prodotto p ON fd.idprodotto = p.idprodotto
     LEFT JOIN shoepal.cliente c ON f.codicefiscale = c.codicefiscale
     WHERE f.dataacquisto >= CURRENT_DATE - INTERVAL ''' || p_periodo || '''
     ORDER BY f.dataacquisto DESC, f.idfattura DESC';
END;
$$ LANGUAGE plpgsql;

-- Ottiene le fatture di vendita per range di date specifico
CREATE OR REPLACE FUNCTION get_fatture_vendita_per_date(p_data_inizio DATE, p_data_fine DATE)
RETURNS TABLE (
    idfattura INTEGER,
    dataacquisto DATE,
    totale DECIMAL(10,2),
    codicefiscale VARCHAR(16),
    idprodotto INTEGER,
    taglia VARCHAR(10),
    quantità INTEGER,
    prezzo DECIMAL(8,2),
    nome VARCHAR(100),
    marca VARCHAR(30),
    tipologia VARCHAR(20),
    nome_cliente VARCHAR(100)
) AS $$
BEGIN
    RETURN QUERY
    SELECT f.idfattura, f.dataacquisto, f.totalepagato as totale, f.codicefiscale,
           fd.idprodotto, fd.taglia, fd.quantità, 
           CASE 
               WHEN f.totaleoriginale > 0 THEN 
                   ROUND(fd.prezzounitario * (f.totalepagato / f.totaleoriginale), 2)
               ELSE 
                   fd.prezzounitario 
           END as prezzo,
           p.nome, p.marca, p.tipologia,
           c.nome as nome_cliente
    FROM shoepal.fattura f
    JOIN shoepal.fatturadettagli fd ON f.idfattura = fd.idfattura
    JOIN shoepal.prodotto p ON fd.idprodotto = p.idprodotto
    LEFT JOIN shoepal.cliente c ON f.codicefiscale = c.codicefiscale
    WHERE f.dataacquisto >= p_data_inizio AND f.dataacquisto <= p_data_fine
    ORDER BY f.dataacquisto DESC, f.idfattura DESC;
END;
$$ LANGUAGE plpgsql;

-- Ottiene i rifornimenti magazzino per periodo specificato
CREATE OR REPLACE FUNCTION get_rifornimenti_magazzino(p_periodo TEXT DEFAULT '30 days')
RETURNS TABLE (
    idordine INTEGER,
    dataconsegna DATE,
    partitaiva VARCHAR(11),
    idnegozio INTEGER,
    idprodotto INTEGER,
    taglia VARCHAR(10),
    quantità INTEGER,
    prezzo DECIMAL(8,2),
    nome VARCHAR(100),
    marca VARCHAR(30),
    tipologia VARCHAR(20),
    nome_fornitore VARCHAR(11)
) AS $$
BEGIN
    RETURN QUERY EXECUTE
    'SELECT s.idordine, s.dataconsegna, s.partitaiva, o.idnegozio,
            s.idprodotto, s.taglia, s.quantità, s.prezzo,
            p.nome, p.marca, p.tipologia,
            s.partitaiva as nome_fornitore
     FROM shoepal.StoricoOrdiniFornitore s
     JOIN shoepal.ordine o ON s.idordine = o.idordine
     JOIN shoepal.prodotto p ON s.idprodotto = p.idprodotto
     WHERE s.dataconsegna >= CURRENT_DATE - INTERVAL ''' || p_periodo || '''
     ORDER BY s.dataconsegna DESC, s.idordine DESC';
END;
$$ LANGUAGE plpgsql;

-- Funzione per ottenere rifornimenti magazzino in un range di date specifico
CREATE OR REPLACE FUNCTION get_rifornimenti_magazzino_per_date(p_data_inizio DATE, p_data_fine DATE)
RETURNS TABLE (
    idordine INTEGER,
    dataconsegna DATE,
    partitaiva VARCHAR(11),
    idnegozio INTEGER,
    idprodotto INTEGER,
    taglia VARCHAR(10),
    quantità INTEGER,
    prezzo DECIMAL(8,2),
    nome VARCHAR(100),
    marca VARCHAR(30),
    tipologia VARCHAR(20),
    nome_fornitore VARCHAR(11)
) AS $$
BEGIN
    RETURN QUERY
    SELECT s.idordine, s.dataconsegna, s.partitaiva, o.idnegozio,
           s.idprodotto, s.taglia, s.quantità, s.prezzo,
           p.nome, p.marca, p.tipologia,
           s.partitaiva as nome_fornitore
    FROM shoepal.StoricoOrdiniFornitore s
    JOIN shoepal.ordine o ON s.idordine = o.idordine
    JOIN shoepal.prodotto p ON s.idprodotto = p.idprodotto
    WHERE s.dataconsegna >= p_data_inizio 
      AND s.dataconsegna <= p_data_fine
    ORDER BY s.dataconsegna DESC, s.idordine DESC;
END;
$$ LANGUAGE plpgsql;