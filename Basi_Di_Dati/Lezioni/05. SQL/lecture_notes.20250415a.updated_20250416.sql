-- query ricorsive 
-- data una pellicola specifica, suggerire le pellicole simili 
-- sono interessato alla pellicola 0013444
-- sono simili le pellicole che rispondono alla seguente query:
select movie2
from imdb.sim 
where movie1 = '0013444';
-- ma sono simili a 0013444 anche le pellicole simili a quelle restituite dalla query precedente
-- se 0018756 è una pellicola restituita dalla query sopra, anche le pellicole simili a 0018756 sono indirettamente simili a 0013444

-- possiamo risolvere l'esercizio con una query ricorsiva che esplora il contenuto della tabella sim partendo dai record di 0013444 e visitando in ampiezza la tabella sim come fosse la tabella di adiacenze di un grafo



-- esempio base di query ricorsiva
with recursive t(n) as (
select 1
union 
select n+1 from t
where n < 10)
select n from t;


-- esempio di ricorsione su una relazione uno-a-molti
-- voglio memorizzare i generi in una gerarchia
create table imdb.genre_taxonomy (
genre_name varchar primary key,
genre_parent varchar 
);

alter table imdb.genre_taxonomy add constraint parent_fk foreign key (genre_parent) references imdb.genre_taxonomy(genre_name);

insert into imdb.genre_taxonomy values ('Thriller', null);
insert into imdb.genre_taxonomy values ('Noir', 'Thriller');
insert into imdb.genre_taxonomy values ('Poliziesco', 'Noir');
insert into imdb.genre_taxonomy values ('Spionaggio', 'Poliziesco');
insert into imdb.genre_taxonomy values ('Cronaca nera', 'Poliziesco');
insert into imdb.genre_taxonomy values ('Splatter', 'Thriller');

Thriller
 - Noir
   - Poliziesco
     - Spionaggio
     - Cronaca nera
 - Splatter

-- restituire tutti i sopra-generi di Poliziesco
with recursive search_parent(the_genre, parent_genre) as (
select genre_name, genre_parent
from imdb.genre_taxonomy 
genre_name = 'Poliziesco'
union
select sp.the_genre, gt.genre_parent
from search_parent sp inner join imdb.genre_taxonomy gt on sp.parent_genre = gt.genre_name
)
select parent_genre
from search_parent
where parent_genre is not null;
 
-- restituire i primi due sopra-generi di Cronaca nera
with recursive search_parent(the_genre, parent_genre, distance) as (
select genre_name, genre_parent, 1
from imdb.genre_taxonomy 
where genre_name = 'Cronaca nera'
union
select sp.the_genre, gt.genre_parent, sp.distance+1
from search_parent sp inner join imdb.genre_taxonomy gt on sp.parent_genre = gt.genre_name
where distance < 2
)
select parent_genre, distance
from search_parent
where parent_genre is not null;
 

-- trovare le pellicole simili a 0013444 fino a una distanza 3 nella tabella sim
with recursive search_sim(movie, s_movie, distance) as (
select movie1, movie2, 1
from imdb.sim 
where movie1 = '0013444' and movie2 <> '0013444'
union  
select ss.movie, si.movie2, distance+1
from search_sim ss inner join imdb.sim si on ss.s_movie = si.movie1
where distance < 3
)
select s.*, m.official_title
from search_sim s inner join imdb.movie m on s.s_movie = m.id;