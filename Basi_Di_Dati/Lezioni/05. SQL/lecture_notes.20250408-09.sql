-- operatori aggregati di SQL
-- min/max
-- avg
-- sum
-- count 

-- selezionare il film di durata maggiore/minore
select max(length) as durata_massima, min(length) as durata_minima
from imdb.movie 

-- selezionare il film di durata maggiore o minore e restituire il titolo
-- explain analyze
select id, official_title
from imdb.movie
where length = 
(select max(length) as durata_massima
from imdb.movie) or length = 
(select min(length) as durata_minima
from imdb.movie)

-- soluzione alternativa
-- explain analyze
with mmax as (
select max(length) as durata_massima
from imdb.movie),
mmin as (select min(length) as durata_minima
from imdb.movie)
select m.id, m.official_title
from imdb.movie m, mmax, mmin
where m.length = mmax.durata_massima or m.length = mmin.durata_minima;


-- trovare il film di durata maggiore fra quelle prodotte nel 2010
select max(length) as durata_massima_2010, min(length) as durata_minima_2010
from imdb.movie 
where year = '2010';


-- soluzione senza utilizzare l'operatore max
--ai movie con durata non nulla tolgo i movie che hanno durata inferiore ad almeno un altro movie (si ricordi la soluzione in algebra a questo tipo di esercizi)


-- restituire la durata media delle pellicoleù
-- round arrotonda il risultato al numero di decimali specificato
select round(avg(length), 2)
from imdb.movie m 


-- restituire la durata complessiva delle pellicole del 2010
select sum(length)
from imdb.movie m 
where year = '2010'


-- restituire il numero di pellicole memorizzate 
select count(*)
from imdb.movie 

-- restituire il numero di pellicole per le quali è noto l'anno di produzione
select count(year)
from imdb.movie

select count(*)
from imdb.movie 
where year is not null;


-- restituire il numero di pellicole per le quali è noto il titolo
select count(official_title)
from imdb.movie

-- restituire il numero di titoli diversi delle pellicole
select count(distinct official_title)
from imdb.movie


-- trovare la durata media dei film del 2010
select avg(length), sum(length), count(length), sum(length)::numeric/count(length) as media_calcolata
from imdb.movie
where year = '2010'

-- i valori null non sono considerati dagli operatori aggregati
select * from 
imdb.movie m 
where length is null and year = '2010'



-- restituire il numero di pellicole per ogni anno disponibile (con ordinamento)
select year, count(*)
from imdb.movie
group by year 
order by 2 desc


-- restituire per ciascun film il numero di persone coinvolte per ciascun ruolo
select movie, p_role, count(*)
from imdb.crew
group by movie, p_role
order by movie

-- restituire anche il titolo della pellicola
select movie, official_title, p_role, count(*)
from imdb.crew inner join imdb.movie on movie.id = crew.movie
group by movie, p_role, official_title
order by movie

-- questa soluzione è equivalente alla precedente?
-- no, è cruciale includere la chiave degli oggetti nella clausola di raggruppamento, altrimenti si rischia di raggruppare pellicole diverse con titolo coincidente
select official_title, p_role, count(*)
from imdb.crew inner join imdb.movie on movie.id = crew.movie
group by p_role, official_title
order by movie


-- per ogni film trovare il numero di attori
select movie, count(person)
from imdb.crew 
where p_role = 'actor'
group by movie

-- restituire la durata media delle pellicole per ogni anno (con ordinamento)


-- restituire il numero di valutazioni per ogni film
select movie, count(*) as numero_recensioni
from imdb.rating 
group by movie
union 
(select id, 0
from imdb.movie 
except 
select movie, 0 
from imdb.rating)

-- c'è un modo più brillante?
select id, count(rating.movie), count(*) as numero_recensioni
from imdb.movie left join imdb.rating on movie.id = rating.movie
group by id;


-- restituire le pellicole che hanno più di 10 attori
select movie, count(person)
from imdb.crew 
where p_role = 'actor'
group by movie
having count(*) > 10


-- restituire le persone che hanno svolto più di un ruolo


-- restituire il miglior rating di ciascun film
select id, max(score/scale)
from imdb.movie left join imdb.rating on movie.id = rating.movie
group by id
order by 2 desc;


-- restituire gli anni nei quali ci sono più di 10 film a partire dal 2010


-- selezionare l'attore che ha recitato nel maggior numero di film
-- questa soluzione è scorretta: non considera eventuali attori con il medesimo numero di partecipazioni al valore massimo
select person, count(distinct movie)
from imdb.crew
where p_role = 'actor'
group by person 
order by 2 desc

-- soluzione corretta
with recitazioni as (
select person, count(distinct movie) as n_partecipazioni
from imdb.crew
where p_role = 'actor'
group by person),
max_recitazioni as (
select max(n_partecipazioni) as max_partecipazioni
from recitazioni)
select id, given_name, n_partecipazioni
from imdb.person inner join recitazioni on person.id = recitazioni.person, max_recitazioni
where n_partecipazioni = max_partecipazioni;

-- soluzione alternativa
select person, count(distinct movie) as n_partecipazioni
from imdb.crew
where p_role = 'actor'
group by person
having count(distinct movie) >= all (
select count(distinct movie)
from imdb.crew
where p_role = 'actor'
group by person
);


-- selezionare i film con cast più numeroso della media


-- selezionare i film nel cui cast non figurano attori nati in paesi dove il film è stato prodotto


-- selezionare il titolo dei film che hanno valutazioni superiori alla media delle valutazioni dei film prodotti nel medesimo anno


-- selezionare i film con cast più numeroso della media dei film del medesimo genere
-- consideriamo come cast i ruoli di actor e director
-- consideriamo una sola volta le partecipazioni ai film con ruoli diversi
-- cte: 
-- A. trovare la numerosità del cast per ogni pellicola
-- B. trovare la media della numerosità di cast per genere
with movie_cast as (
select movie, count(distinct person) as n_person
from imdb.crew
where p_role in ('actor', 'director')
group by movie),
avg_genre as (
select genre, avg(n_person) as avg_cast
from imdb.movie left join imdb.genre on movie.id = genre.movie left join movie_cast on movie.id = movie_cast.movie
group by genre
)
select m.id, m.official_title, g.genre, n_person
from imdb.movie m left join imdb.genre g on m.id = g.movie left join movie_cast on m.id = movie_cast.movie
where movie_cast.n_person > 
(select avg_cast 
from avg_genre 
where g.genre = avg_genre.genre);

-- controprova su Crime
-- trovo la media dei cast per le pellicole Crime
with movie_cast(movie, n_person) as (
select movie, count(distinct person)
from imdb.crew
where p_role in ('actor', 'director')
group by movie)
select genre, avg(n_person) as avg_cast
from imdb.movie left join imdb.genre on movie.id = genre.movie left join movie_cast on movie.id = movie_cast.movie
where genre = 'Crime'
group by genre



movie_cast
movie | n_person
m1 	 		34
m2	 		25
m3   		50
m4	 		10

genre
movie | genre
m1   	crime
m2 	 	drama
m3	 	crime
m1 		drama

movie left join movie_cast left join genre 
id |	movie 	|  n_person  |  movie   | genre 
m1	 m1 	 		34			m1			crime
m1	 m1			34			m1			drama
m2	 m2	 		25			m2			drama
m3	 m3   		50			m3			crime
m4	 m4			10			null			null		


-- selezionare i film che sono stati distribuiti in tutti i paesi
-- un film m è nel risultato se non esiste un paese p per il quale non esiste la distribuzione di m in p


-- selezionare le persone che hanno recitato in tutti i film di genere Crime
crew(person, movie)
genre(movie, genre)

A = π(person,movie) CREW
B = π(movie) (σ(genre='Crime') GENRE)
risultato = A / B
-- data la persona A, non esiste un film di genere Crime in cui A non abbia recitato
-- se A ha recitato in tutti i movie Crime, non deve esistere un movie Crime per il quale non esiste la partecipazione di A
select id, given_name
from imdb.person p
where not exists (
select *
from imdb.genre g
where g.genre = 'Fantastic' and not exists (
select *
from imdb.crew
where p_role = 'actor' and p.id = crew.person and g.movie = crew.movie));

-- controesempio
update genre set genre = 'Fantastic' where genre ='Drama' and movie = '0398883';


