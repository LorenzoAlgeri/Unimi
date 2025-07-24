nome: check_person_location

-- su inserimento o modifica su laction
-- verificare che la tupla non abbia già una tupla con il medesimo d_role

-- 1. procedura
-- 1.1 identifcare SQL
-- 1.2 scrivere header
-- 1.3 definire le variabili
-- 1.4 eseguire SQL
-- 1.5 testare valori e tornare null o tupla
-- 2. trigger
-- 2.1 decidere se before o after
-- 2.2 decidere se row o statement
-- 2.3 scrivere trigger
-- 3. test
-- 3.1 scrivere queries di prova
-- 3.2 esecuzione
-- 3.3 validazione

-- 1.1 -->

PERFORM a.person, a.d_role 
FROM location AS a INNER JOIN location AS b ON (a.person = b.person AND a.d_role = b.d_role AND a.country <> b.country);

SELECT person, d_role FROM location GROUP BY person, d_role HAVING COUNT(*) > 1;

-- 1.2 -->
CREATE OR REPLACE FUNCTION check_person_location() RETURNS TRIGGER AS $$
DECLARE

BEGIN
    PERFORM * FROM location (person = NEW.person AND d_role = NEW.d_role);
    IF FOUND THEN
        return NULL;
    ELSE
        RETURN NEW;
    END IF;
END;
$$ LANGUAGE 'plpgsql';





-- 1.2 -->
CREATE OR REPLACE FUNCTION check_person_location() RETURNS TRIGGER AS $$
DECLARE
the_person person.give_name%TYPE;
    BEGIN
    PERFORM a.person, a.d_role FROM location AS a INNER JOIN location AS b ON (a.person = b.person AND a.d_role = b.d_role AND a.country <> b.country);
    IF FOUND THEN
        RAISE 'violata regola persona - d_role';
        RETURN NULL;
    ELSE
        RETURN TRUE;
    END IF;
END;
$$ LANGUAGE 'plpgsql';