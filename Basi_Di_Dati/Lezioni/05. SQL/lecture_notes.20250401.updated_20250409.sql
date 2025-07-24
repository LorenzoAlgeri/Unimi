-- restituire il titolo dei film con durata superiore alla durata di Inception
-- versione con subquery
-- considerare gli operatori ANY e ALL
-- il caso con ANY
SELECT id, official_title, length
FROM imdb.movie 
WHERE length > ANY (
SELECT length
FROM imdb.movie
WHERE official_title = 'Inception');


-- valori restituiti dalla subquery 90, 100
-- con > ALL i record restituiti sono quelli con id = 2, 4
-- con > ANY i record restituiti sono quelli con id = 2, 4, 5

-- altra soluzione
SELECT distinct m1.id, m1.official_title, m1.length
-- , m2.id, m2.official_title, m2.length
FROM imdb.movie m1 INNER JOIN imdb.movie m2 
	ON m1.length > m2.length
WHERE  m2.official_title = 'Inception'
order by m1.official_title; 

-- è possibile ottenere il risultato della query con ALL anche con self-join?


-- restituire le coppie di attori che hanno recitato insieme in almeno due film diversi


-- selezionare le persone che hanno recitato in film nei quali erano registi


-- selezionare le pellicole prodotte in Italia e Stati Uniti



-- selezionare le pellicole prodotte solo in Italia
select p.movie
from imdb.produced p 
where p.country = 'ITA' and movie not in (
select movie
from imdb.produced 
where country <> 'ITA');

-- altra soluzione
-- pellicole italiane e non di altri paesi
select p.movie
from imdb.produced p 
where p.country = 'ITA' 
except
select movie
from imdb.produced 
where country <> 'ITA';

-- soluzione con join 
WITH itamovies AS (
select p.movie
from imdb.produced p 
where p.country = 'ITA' ),
nonitamovies AS (
select movie
from imdb.produced 
where country <> 'ITA')
SELECT DISTINCT itamovies.*
FROM itamovies LEFT JOIN nonitamovies ON itamovies.movie = nonitamovies.movie
WHERE nonitamovies.movie IS NULL;

-- soluzione senza with
SELECT DISTINCT itamovies.*
FROM imdb.produced itamovies LEFT JOIN imdb.produced nonitamovies ON itamovies.movie = nonitamovies.movie and nonitamovies.country <> 'ITA' 
WHERE nonitamovies.movie IS null and itamovies.country = 'ITA';



-- soluzione con join esterno
-- considerare esempio dei seguenti record:
-- 001 - ITA
-- 002 - ITA
-- 002 - USA


-- join esterni
-- restituire il titolo di tutti i film con i relativi generi
select id, official_title, genre
from imdb.movie inner join imdb.genre on movie.id = genre.movie

-- A join B on c1
-- il join interno (inner join) restituisce i record di A e B nel prodotto cartesiano che soddisfano c1

-- come possiamo includere nel risultato anche i record di A che non soddisfano mai la condizione di join c1?
-- uso left join
select id, official_title, genre
from imdb.movie left join imdb.genre on movie.id = genre.movie

-- equivalente con right join
select id, official_title, genre
from imdb.genre right join imdb.movie on movie.id = genre.movie

-- esiste un terzo tipo di join esterno: full join
select id, official_title, genre
from imdb.genre full join imdb.movie on movie.id = genre.movie

movie 
=====
1   	| m1
2	| m2
3	| m3

genre
1  | thriller
1  | drama
2  | comics

movie join genre on movie.id = genre.movie
1  |  m1 |  1   | thriller
1  |  m1 |  1	| drama
2  |  m2 |  2 	| comics


movie left genre on movie.id = genre.movie
1  |  m1 |  1    | thriller
1  |  m1 |  1	 | drama
2  |  m2 |  2 	 | comics
3  |  m3 |  null | null

-- questa versione cosa restituisce?
-- è equivalente a inner join perchè non esistono tuple spurie di genre
select id, official_title, genre
from imdb.movie right join imdb.genre on movie.id = genre.movie

-- per ogni persona mostrare il nome e il country dove è deceduto incluse le persone per le quali non abbiamo un country di decesso
-- questa soluzione non è corretta: i record spuri di person non soddisfano mai la condizione di selezione d_role = 'D' e quindi vengono esclusi dal risultato
select id, given_name, country, d_role
from imdb.person left join imdb.location on person.id = location.person
where d_role = 'D'
order by country 

-- come evitare il problema di tuple spurie di person eliminate per una condizione di selezione sulla tabella location?
with deaths as (
select *
from imdb.location  
where d_role = 'D')
select id, given_name, country, d_role
from imdb.person left join deaths on person.id = deaths.person
order by country ;

-- soluzione alternativa
select id, given_name, country, d_role
from imdb.person left join imdb.location on person.id = location.person and d_role = 'D'
order by country 


-- trovare le coppie di pellicole che non hanno generi in comune
select
from imdb.genre g1 join imdb.genre g2 on g1.movie <> g2.movie


-- soluzione con set difference
-- A. trovo il totale delle coppie  
-- B: trovo le coppie di pellicole con generi in comune

select distinct g1.movie, g2.movie
from imdb.genre g1 inner join imdb.genre g2 on g1.movie > g2.movie
except
select distinct g1.movie, g2.movie
from imdb.genre g1 inner join imdb.genre g2 on g1.movie > g2.movie
where g1.genre = g2.genre

genre
movie   |   genre
=================
001			thriller
002         drama
003         drama
003         thriller

002, 001

001, 003 


g1 x g2
g1.movie   |  g1.genre   |  g2.movie   | g2.genre
==================================================
001				thriller			001 			thriller     x
001				thriller			002 			drama		 x
001				thriller			003 			drama		 x
001				thriller			003 			thriller		x
002				drama			001 			thriller			x
002				drama			002  		drama		x
002				drama			003   		drama		x
002				drama			003  		thriller		x
003				drama			001 			thriller			x
003				drama			002 			drama
003				drama			003  		drama		x
003				drama			003 			thriller		x 
003				thriller			001 			thriller
003				thriller			002 			drama			x
003				thriller			003 			drama		x
003				thriller			003  		thriller		x


-- altra soluzione con operatore exists
-- una coppia m1, m2 è nel risultato se non esiste una coppia di record con m1, m2 dove i generi coincidono
with couples as (
select distinct g1.movie as movie1, g2.movie as movie2
from imdb.genre g1 inner join imdb.genre g2 on g1.movie > g2.movie
)
select *
from couples c
where not exists (
select *
from imdb.genre g1, imdb.genre g2 
where g1.movie = c.movie1 and g2.movie = c.movie2 and g1.genre = g2.genre);


-- selezionare i film che non sono stati distribuiti nei paesi nei quali sono stati prodotti
-- un movie m viene inserito nel risultato se non esiste un record della tabella produced p per il quale esiste un record di release relativo allo stesso movie e paese di p
-- explain analyse
select id, official_title
from imdb.movie m
where not exists (
select *
from produced p
where p.movie = m.id and exists (
select * 
from imdb.released r
where r.movie = m.id and p.country = r.country));

produced
movie  |  country
001			ITA  
001			USA
002			GBR 
002			USA 


released
movie  |  country
001			FRA
001 			ITA
002			ITA
002			FRA

-- soluzione alternativa
-- explain analyse
select id, official_title
from imdb.movie m
where not exists (
select *
from produced p inner join imdb.released r on p.movie = r.movie and  p.country = r.country
where p.movie = m.id );

produced inner join released
p.movie 	|	p.country  |  r.movie   | r.country
001			ITA  			001 			ITA

