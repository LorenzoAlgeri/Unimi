--Database created for the "Basi di dati" Lab course

CREATE DATABASE database_uni;

-- CDL TABLE
CREATE TABLE cdl (
    codice CHAR(6) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    durata SMALLINT NOT NULL,
    tipologia VARCHAR(15) NOT NULL
);

-- PERSONA TABLE
CREATE TABLE persona (
    cf CHAR(16) PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
);

-- STUDENTE TABLE
CREATE TABLE studente (
    matricola CHAR(6) PRIMARY KEY,
    persona CHAR(16) REFERENCES persona(cf),
    cdl CHAR(6) REFERENCES cdl(codice)
);

-- PROFESSORE TABLE
CREATE TABLE professore (
    matricola CHAR(10) PRIMARY KEY,
    persona CHAR(16) REFERENCES persona(cf),
    ruolo VARCHAR(10) NOT NULL,
    data_assunzione DATE NOT NULL,
    cessazione_ruolo DATE
);

-- INSEGNAMENTO TABLE
CREATE TABLE insegnamento (
    codice CHAR(6) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    anno SMALLINT NOT NULL
);

-- SEMESTRE TABLE
CREATE TABLE semestre (
    cdl CHAR(6) REFERENCES cdl(codice),
    cfu SMALLINT NOT NULL,
    PRIMARY KEY (cdl, cfu)
);

-- INSEGNATO TABLE
CREATE TABLE insegnato (
    insegnamento CHAR(6) REFERENCES insegnamento(codice),
    professore CHAR(10) REFERENCES professore(matricola),
    PRIMARY KEY (insegnamento, professore),
    da DATE NOT NULL,
    a DATE
);

-- ESAME TABLE
CREATE TABLE esame (
    codice CHAR(6) PRIMARY KEY,
    data DATE NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    codice_insegnamento CHAR(6) REFERENCES insegnamento(codice),
    professore_matricola CHAR(10) REFERENCES professore(matricola),
    data_inizio TIME,
    data_fine TIME,
    aula VARCHAR(10)
);

-- ISCRIZIONI TABLE
CREATE TABLE iscrizioni (
    matricola CHAR(6),  -- Aggiunto il tipo CHAR(6) per la colonna matricola
    codice CHAR(6),  -- Aggiunto il tipo CHAR(6) per la colonna codice
    presenza BOOLEAN,
    ritirato BOOLEAN,
    voto SMALLINT,
    PRIMARY KEY (matricola, codice),  -- Definizione corretta della PRIMARY KEY
    FOREIGN KEY (matricola) REFERENCES studente(matricola),  -- Definizione corretta della foreign key
    FOREIGN KEY (codice) REFERENCES esame(codice)  -- Definizione corretta della foreign key
);

INSERT INTO cdl (codice, nome, durata, tipologia) VALUES
('CDL01', 'Ingegneria Informatica', 3, 'Triennale'),
('CDL02', 'Ingegneria Elettronica', 3, 'Triennale'),
('CDL03', 'Ingegneria Meccanica', 3, 'Triennale'),
('CDL04', 'Ingegneria Gestionale', 3, 'Triennale'),
('CDL05', 'Ingegneria Civile', 3, 'Triennale');

INSERT INTO persona (cf, nome, cognome, email) VALUES
('HDEDJT97B64I469D', 'Mario', 'Rossi', 'mario.rossi@gmail.com'),
('PLMNJK85C12F839K', 'Luigi', 'Verdi', 'luigi.verdi@gmail.com'),
('QWERUI76D45G123H', 'Anna', 'Bianchi', 'anna.bianchi@gmail.com'),
('ZXCVBN65E78H456J', 'Giulia', 'Neri', 'giulia.neri@gmail.com'),
('ASDFGH54F90J789K', 'Marco', 'Gialli', 'marco.gialli@gmail.com'),
('LKJHGF43G21K012L', 'Elena', 'Blu', 'elena.blu@gmail.com'),
('POIUYT32H34L345M', 'Francesco', 'Viola', 'francesco.viola@gmail.com'),
('MNBVCX21J56M678N', 'Chiara', 'Rosa', 'chiara.rosa@gmail.com'),
('QAZWSX10K78N901O', 'Alessandro', 'Marrone', 'alessandro.marrone@gmail.com'),
('EDCRFV09L90O234P', 'Federica', 'Grigio', 'federica.grigio@gmail.com'),
('GHJKLO98P12Q345R', 'Luca', 'Azzurri', 'luca.azzurri@gmail.com'),
('BNMPLK87R34S678T', 'Sara', 'Violini', 'sara.violini@gmail.com'),
('TYUIOP76T56U901V', 'Davide', 'Argento', 'davide.argento@gmail.com'),
('ZXCVBN65U78V234W', 'Martina', 'Oro', 'martina.oro@gmail.com'),
('ASDFGH54V90W567X', 'Giorgio', 'Bronzo', 'giorgio.bronzo@gmail.com'),
('LKJHGF43W12X890Y', 'Valeria', 'Verde', 'valeria.verde@gmail.com'),
('POIUYT32X34Y123Z', 'Simone', 'Bianco', 'simone.bianco@gmail.com'),
('MNBVCX21Y56Z456A', 'Claudia', 'Nero', 'claudia.nero@gmail.com');

INSERT INTO studente (matricola, persona, cdl) VALUES
('123456', 'HDEDJT97B64I469D', 'CDL01'),
('234567', 'PLMNJK85C12F839K', 'CDL02'),
('345678', 'QWERUI76D45G123H', 'CDL03'),
('456789', 'ZXCVBN65E78H456J', 'CDL04'),
('567890', 'ASDFGH54F90J789K', 'CDL05'),
('678901', 'LKJHGF43G21K012L', 'CDL01'),
('789012', 'POIUYT32H34L345M', 'CDL02'),
('890123', 'MNBVCX21J56M678N', 'CDL03'),
('901234', 'GHJKLO98P12Q345R', 'CDL04'),
('012345', 'BNMPLK87R34S678T', 'CDL05');

INSERT INTO professore (matricola, persona, ruolo, data_assunzione, cessazione_ruolo) VALUES
('PROF001', 'TYUIOP76T56U901V', 'Docente', '2020-01-15', NULL),
('PROF002', 'ZXCVBN65U78V234W', 'Ricercator', '2019-03-10', '2023-01-01'),
('PROF003', 'ASDFGH54V90W567X', 'Assoc.Prof', '2018-05-20', NULL),
('PROF004', 'LKJHGF43W12X890Y', 'Ord.Prof', '2017-07-25', '2022-12-31'),
('PROF005', 'POIUYT32X34Y123Z', 'Ricercator', '2021-09-30', NULL),
('PROF006', 'MNBVCX21Y56Z456A', 'Docente', '2020-11-05', NULL),
('PROF007', 'QAZWSX10K78N901O', 'Ord.Prof', '2016-12-15', '2023-06-30'),
('PROF008', 'EDCRFV09L90O234P', 'Assoc.Prof', '2019-02-28', NULL);

INSERT INTO insegnamento(codice, nome, anno) VALUES
('IN01', 'Programmazione I', 1),
('IN02', 'Algoritmi e Strutture Dati', 1),
('IN03', 'Basi di Dati', 2),
('IN04', 'Sistemi Operativi', 2),
('IN05', 'Reti di Calcolatori', 3),
('IN06', 'Ingegneria del Software', 3),
('IN07', 'Sicurezza Informatica', 3),
('IN08', 'Machine Learning', 3);

INSERT INTO semestre (cdl, cfu) VALUES
('CDL01', 6),
('CDL02', 9),
('CDL03', 12),
('CDL04', 12),
('CDL05', 6);

INSERT INTO insegnato (insegnamento, professore, da, a) VALUES
('IN01', 'PROF001', '2020-01-15', NULL),
('IN02', 'PROF002', '2019-03-10', '2023-01-01'),
('IN03', 'PROF003', '2018-05-20', NULL),
('IN04', 'PROF004', '2017-07-25', '2022-12-31'),
('IN05', 'PROF005', '2021-09-30', NULL),
('IN06', 'PROF006', '2020-11-05', NULL),
('IN07', 'PROF007', '2016-12-15', '2023-06-30'),
('IN08', 'PROF008', '2019-02-28', NULL);

INSERT INTO esame (codice, data, tipo, codice_insegnamento, professore_matricola, data_inizio, data_fine, aula) VALUES
('ES01', '2023-06-15', 'Orale', 'IN01', 'PROF001', '09:00:00', '12:00:00', 'Aula 101'),
('ES02', '2023-06-20', 'Scritto', 'IN02', 'PROF002', '14:00:00', '17:00:00', 'Aula 102'),
('ES03', '2023-06-25', 'Orale', 'IN03', 'PROF003', '10:00:00', '13:00:00', 'Aula 103'),
('ES04', '2023-06-30', 'Scritto', 'IN04', 'PROF004', '15:00:00', '18:00:00', 'Aula 104'),
('ES05', '2023-07-05', 'Orale', 'IN05', 'PROF005', NULL, NULL, 'Aula 105'),
('ES06', '2023-07-10', 'Scritto', 'IN06', 'PROF006', NULL, NULL, 'Aula 106'),
('ES07', '2023-07-15', 'Orale', 'IN07', 'PROF007', NULL, NULL, 'Aula 107'),
('ES08', '2023-07-20', 'Scritto', 'IN08', 'PROF008', NULL, NULL, 'Aula 108');

INSERT INTO iscrizioni(matricola, codice, presenza, ritirato, voto) VALUES
('123456', 'ES01', TRUE, FALSE, 28),
('234567', 'ES02', TRUE, FALSE, 30),
('345678', 'ES03', TRUE, FALSE, 27),
('456789', 'ES04', TRUE, TRUE, NULL),
('567890', 'ES05', FALSE, FALSE, NULL),
('678901', 'ES06', TRUE, FALSE, 25),
('789012', 'ES07', FALSE, TRUE, NULL),
('890123', 'ES08', FALSE, FALSE, NULL);

