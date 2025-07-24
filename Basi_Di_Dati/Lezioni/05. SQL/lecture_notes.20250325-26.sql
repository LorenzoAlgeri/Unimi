-- operatori di SQL per la selezione
-- booleani: AND, OR, NOT
-- uguaglianza/disuguaglianza:  = / <>
-- confronto: > (maggiore) >= (maggiore o uguale) < (minore) <= (minore o uguale)
-- BETWEEN
-- LIKE
-- IN 
-- IS (NOT) NULL

-- selezionare il titolo delle pellicole del 2010
select id, official_title 
from imdb.movie 
where year = '2010';

-- selezionare tutti gli attributi delle pellicole del 2010 di durata superiore all'ora
select * 
from imdb.movie 
where year = '2010' and length > 60;

-- selezionare tutti gli attributi delle pellicole del 2010 di durata compresa fra una e due ore
select * 
from imdb.movie 
where year = '2010' and length >= 60 and length <= 120;

-- selezionare tutti gli attributi delle pellicole di durata compresa fra una e due ore (estremi inclusi) realizzate in anni diversi dal 2010
select * 
from imdb.movie 
where year <> '2010' and length >= 60 and length <= 120;

-- sintassi alternativa
select * 
from imdb.movie 
where not(year = '2010') and length >= 60 and length <= 120;

-- versione con between
select * 
from imdb.movie 
where year <> '2010' and length between 60 and 120;

-- selezionare le pellicole del 2010 oppure di durata compresa fra una e due ore 
-- usare le parentesi per forzare la precedenza fra gli operatori
-- senza parentesi la precedenza degli operatori è da sinistra a destra
select * 
from imdb.movie 
where year = '2010' or (length >= 60 and length <= 120);

-- selezionare le pellicole di genere Drama, Thriller o Crime 
select *
from imdb.genre
where lower(genre) = 'drama' or genre ilike 'thriller' or lower(genre) = 'crime' ;

-- versione con operatore IN 
select movie
from imdb.genre
where lower(genre) in ('drama', 'thriller', 'crime');

-- uso della clausola DISTINCT per eliminare i duplicati
-- la clausola ORDER BY ordina il risultato in base all'attributo specificato
select distinct movie 
from imdb.genre
where lower(genre) in ('drama', 'thriller', 'crime')
order by movie ASC;

-- restituire le persone nate dopo il 1970 ordinando il risultato in base all'anno di nascita (crescente) e al nome (decrescente) 
select *
from imdb.person 
where birth_date > '1970-12-31'
-- where birth_date >= '1971-01-01'
order by birth_date, given_name DESC;

-- trovare le persone nate nel 1971
select *
from imdb.person 
-- where birth_date > '1970-12-31' and birth_date < '1972-01-01'
-- where birth_date between '1971-01-01' and '1971-12-31'
-- where birth_date like '1971-__-__'
where extract(year from birth_date) = '1971';

-- restituire le persone nate a partire dal 1970 ordinando il risulato in base all'anno e al nome
-- rinominare la prima colonna in birth_year
-- forzare il tipo di dato della prima colonna a char(4): stringa di lunghezza fissa a 4 caratteri (:: operatore di cast in postgres)
select extract(year from birth_date)::char(4) as "anno di nascita", given_name as "nome della persona"
from imdb.person 
where extract(year from birth_date) >= '1970'
order by extract(year from birth_date), given_name;


-- selezionare le persone delle quali si conosce la data di decesso
select *
from imdb.person  
where death_date is not null
-- la seguente è errata:
-- where death_date <> NULL;

-- selezionare le persone che sono ancora in vita
select *
from imdb.person  
where death_date is null;

-- trovare le persone delle quali conosciamo la data di nascita ma non conosciamo la data di decesso
select *
from imdb.person  
where birth_date is not null and death_date is null;

-- altri esempi con NULL
update imdb.person set bio = '' where id = '0080580';
update imdb.person set bio = '    ' where id = '0080580';

select *
from imdb.person  
-- where bio is null and id = '0080580'
where bio is not null and id = '0080580'

-- trovare le persone che non hanno bio (o perchè null o perchè stringa vuota o stringa di blank)
select * 
from imdb.person 
where bio is null or trim(bio) = ''; 


-- selezionare il titolo delle pellicole prodotte negli Stati Uniti
-- cardinality(movie) = 1033
-- cardinality(produced) = 1332
-- cardinality(produced x movie) = 1033 x 1332
select m.id, m.official_title 
from imdb.movie m, imdb.produced p
where m.id = p.movie and country = 'USA';

-- sintassi alternativa
select m.id, m.official_title 
from imdb.movie m inner join  imdb.produced p on  m.id = p.movie 
where country = 'USA';

-- trovare le pellicole prodotte in due paesi diversi
-- in algebra si risolve con self-join dove cerco 2 record aventi il medesimo valore di produced.movie e diversi valori di produced.country
select p1.movie, p1.country as country1, p2.country as country2
from imdb.produced p1, imdb.produced p2
where p1.movie = p2.movie and p1.country < p2.country 


-- selezionare i paesi nei quali sono state distribuite le pellicole del 2010 (si restituisca anche il titolo della pellicola - sia quello ufficiale, sia quello usato nella distribuzione dove presente)


-- selezionare le pellicole per le quali non è noto il titolo di distribuzione in Italia


-- selezionare il nome degli attori che hanno recitato nel film Inception
select person.*
from imdb.movie inner join imdb.crew on movie.id = crew.movie inner join imdb.person on person.id = crew.person 
where official_title ilike 'inception' and p_role = 'actor';

-- uso delle viste 
-- vediamo una soluzione all'esercizio precedente che usa il concetto di vista
create view imdb.movie_person as (
select *
from imdb.movie inner join imdb.crew on movie.id = crew.movie inner join imdb.person on person.id = crew.person);

-- trovare le persone di inception
select *
from imdb.movie_person 
where official_title ilike 'inception' and p_role = 'actor';


-- selezionare gli attori che hanno recitato in pellicole del 2010


-- selezionare le persone che sono decedute in un paese diverso da quello di nascita
-- location(person, country, d_role)
select l_birth.person, p.given_name, l_birth.country as birth_country, l_death.country as death_country 
from imdb.location l_birth, imdb.location l_death, imdb.person p 
where l_birth.person = l_death.person and l_birth.d_role = 'B' and l_death.d_role = 'D' and l_birth.country <> l_death.country and l_birth.person = p.id; 

-- sintassi alternativa
select l_birth.person, p.given_name, l_birth.country as birth_country, l_death.country as death_country 
from imdb.location l_birth inner join imdb.location l_death on l_birth.person = l_death.person inner join imdb.person p on l_birth.person = p.id
where l_birth.d_role = 'B' and l_death.d_role = 'D' and l_birth.country <> l_death.country; 


-- selezionare i film che non hanno materiali associati
-- movie(id, official_title,...)
-- material(id, description, language, movie)
-- possiamo usare la set difference (except) A-B

-- A: tutti i record di movie
select movie.id 
from imdb.movie 
except
-- B: tutti i movie presenti in material
select distinct material.movie 
from imdb.material; 


-- analisi del costo di una query
-- explain 
explain analyze 
select movie.id 
from imdb.movie 
except
-- B: tutti i movie presenti in material
select distinct material.movie 
from imdb.material;

-- selezionare i paesi nei quali non sono prodotti film


-- trovare le pellicole che sono prodotte in ITA e USA
-- produced(movie, country)
select movie
from imdb.produced 
where country = 'ITA'
intersect
select movie 
from imdb.produced 
where country = 'USA';

-- restituire i titoli delle pellicole prodotte in ITA e USA
-- explain analyze (per valutare il costo della soluzione)
select id, official_title 
from imdb.produced inner join imdb.movie on movie = id 
where country = 'ITA'
intersect
select id, official_title 
from imdb.produced inner join imdb.movie on movie = id
where country = 'USA';

-- soluzione alternativa
explain analyze
select id, official_title 
from imdb.movie 
where id in 
(select movie 
from imdb.produced 
where country = 'ITA'
intersect
select movie
from imdb.produced 
where country = 'USA');

-- uso di viste
create view movie_production as (
select *
from imdb.produced inner join imdb.movie on movie = id);

select id, official_title 
from movie_production
where country = 'ITA'
intersect
select id, official_title 
from movie_production
where country = 'USA';

-- uso di cte (common table expressions)
-- https://www.postgresql.org/docs/current/queries-with.html
with mp as (
select *
from imdb.produced inner join imdb.movie on movie = id)
select id, official_title 
from mp
where country = 'ITA'
intersect
select id, official_title 
from mp
where country = 'USA';

-- soluzione on subquery (o query innestata)
explain analyze
select id, official_title 
from imdb.produced inner join imdb.movie on movie = id
where country = 'ITA' and id in (
select movie 
from produced
where country = 'USA');


-- soluzione con self-join
explain analyse
select usa.movie, official_title
from produced usa inner join produced ita on usa.movie = ita.movie inner join imdb.movie on usa.movie = id
where usa.country = 'USA' and ita.country = 'ITA';

explain analyse
select usa.movie, official_title
from produced usa, produced ita, imdb.movie  
where usa.country = 'USA' and ita.country = 'ITA' and usa.movie = ita.movie and usa.movie = id ;

-- selezionare i film per i quali esistono materiali multimediali di tipo immagine o materiali testuali di qualche genere


-- selezionare i film per i quali esistono materiali multimediali di tipo immagine e materiali testuali di qualche genere
