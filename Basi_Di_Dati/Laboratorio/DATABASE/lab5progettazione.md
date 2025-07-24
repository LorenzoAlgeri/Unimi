# Progettazione lab

^ = unique  
\* = identificato come singolo

PERSONA
- nome VARCHAR(50)
- cognome VARCHAR(50)  
- email^ VARCHAR(100)  
- CF* CHAR(16) (codice fiscale, in italia è sempre fisso 16 cifre)

STUDENTE  
- CDL --> CDL.codice CHAR(6)
- matricola* CHAR(6) (in statale lungo 6 cifre)  
- persona --> persona.CF CHAR(16)

PROFESSORE  
- persona --> persona.CF CHAR(16)  
- ruolo (RTD- PA - PO)* VARCHAR(10)  
- MATRICOLA* CHAR(10)
- data_assunzione DATE
- cessazione_ruolo DATE

CDL (corso di laurea)
- nome  VARCHAR(100)  
- durata SMALLINT (anni, quindi numeri piccoli)
- tipologia VARCHAR(15) (triennale/magistrale)
- CODICE* CHAR(6)

INSEGNAMENTO  
- nome  VARCHAR(100)  
- CODICE* CHAR(6)  
- anno SMALLINT  

SEMESTRE
- CFU SMALLINT  
- CDL --> CDL.Codice CHAR(6)  

INSEGNATO  
- insegnamento.codice* CHAR(6)
- professore.matricola* CHAR(10)
- da DATE
- a DATE

ESAME  
- data DATE  
- tipo VARCHAR(30)    
- insegnamento.codice CHAR(6)   
- professore.matricola CHAR(10)  
- data_inizio TIME  
- data_fine TIME  
- aula VARCHAR(10)  
- codice* CHAR(6)  

ISCRIZIONI  
- studente.matricola* CHAR(10)  
- esame.codice* CHAR(6)   
- presenza BOOLEAN  
- ritirato BOOLEAN  
- voto SMALLINT

PROPEDEUTICITA  
ins1 --> insegnamento.codice CHAR(6)  
ins2 --> insegnamento.codice CHAR(6)