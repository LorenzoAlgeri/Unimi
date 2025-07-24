nome: check_person_location
-- su inserimento o modifica su laction
-- verificare che la tupla non abbia già una tupla 
-- con il medesimo d_role



 -- 1. procedura
 -- 1.1 identificare SQL   ---> OK
 -- 1.2 scrivere header
 -- 1.3 defnire le variabili
 -- 1.4 eseguire SQL 
 -- 1.5 testare valori e tornare null o tupla
 -- 2. trigger
 -- 2.1 decidere se before o after
 -- 2.2 decidere se each row or statement
 -- 2.3 scrivere trigger
 -- 3 test 
 -- 3.1 scrivere queries di prova
 -- 3.2 esecuzione
 -- 3.3 validazione
 
 1.1 ---> 
 
 
 CREATE OR REPLACE FUNCTION check_person_location_setup() RETURNS boolean AS $$
 DECLARE
  BEGIN
 PERFORM a.person,a.d_role  FROM location As a INNER JOIN location AS b ON  (a.person = b.person AND a.d_role= b.d_role AND a.country<> b.country);
   
  IF FOUND THEN
          return FALSE; 
  ELSE
          return TRUE;
  END IF;      
 END;
 $$ language 'plpgsql';
 
 
 
 -- SELECT person , d_role FROM location GROUP BY  person , d_role HAVING COUNT(*) >1
 
 
 1.2 ---> 
CREATE OR REPLACE FUNCTION check_person_location() RETURNS  TRIGGER AS $$
 DECLARE
 the_person person.give_name%TYPE;
  BEGIN
  SELECT given_name INTO the_person FROM person WHERE person.id = NEW.person ; 
  
  RAISE INFO 'Sto inserendo la location per ' || the_person || 'di ruolo ' || NEW.d_role || 'il cui codice è ' || NEW.person;
  
  
   PERFORM *  FROM location (person = NEW.person AND d_role= NEW.d_role);
  IF FOUND THEN
        RAISE  INFO 'violata regola persona - d_role'|| the_person ;
           return NULL; 
 
  ELSE
          return NEW;
  END IF;
 END;
 $$ language 'plpgsql';
 
 
 ---> 2. scrivere il TRIGGER
 
 CREATE TRIGGER  IU_person_location_trigger  BEFORE INSERT OR  UPDATE ON location FOR EACH ROW EXECUTE  PROCEDURE check_person_location();
 
 
 ---> 3. scrivere set di test
 INSERT INTO
 
 INSERT INTO
 
 
 -----
 
 
 
 
-- TRIGGER 1 ricalcolare il valore di score quando si cambia la scala di rating
-- TRIGGER 2 si controlli che il valore di character  nella tabella crew sia vuoto se  il ruolo non è un attore
-- 
