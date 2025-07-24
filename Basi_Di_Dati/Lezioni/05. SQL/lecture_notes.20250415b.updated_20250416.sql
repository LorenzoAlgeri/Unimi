-- una base di dati contiene diverse tipologie di oggetti tra le quali:
-- tabelle
-- viste
-- stored procedure (function)
-- trigger
-- asserzioni

-- trigger (innesco) permettono di definire un comportamento automatico (attivo) della base di dati a fronte di un evento sui dati

-- voglio materializzare nel db i conteggi delle persone di ciascun film in ciascun ruolo
-- creare una tabella con i conteggi memorizzati in modo da evitare il calcolo della query ad ogni richiesta

create table movie_counts (
movie varchar references movie(id) on update cascade on delete cascade,
p_role varchar,
m_count integer,
primary key(movie, p_role)
);

-- popolare la tabella
-- per ogni movie e ruolo restituire il conteggio
insert into movie_counts (<<query>>);

<< query di inserimento >>
with roles as (
select distinct p_role
from imdb.crew
),
movie_roles as (
select id, p_role
from imdb.movie, roles)
select movie_roles.id, movie_roles.p_role, count(person)
from movie_roles left join imdb.crew on movie_roles.id = crew.movie and movie_roles.p_role = crew.p_role
group by movie_roles.id, movie_roles.p_role 
order by 3

-- come aggiorno la tabella materializzata?
-- trigger su inserimento di nuovo record su crew
-- ogni volta che si inserisce un record in crew, devo incrementare il counter del movie e del ruolo del record inserito

insert into imdb.crew (movie, person, p_role) values ('1670998', '000044', 'director');

update imdb.crew set movie = 'XXX' where movie = '0013444';

create trigger update_counts after insert on imdb.crew for each row execute procedure do_count_increment();
-- for each statement | for each row
-- before | after 

create function do_count_increment() returns trigger as $$
begin

	update movie_counts set m_count = m_count + 1 where movie = new.movie and p_role = new.p_role;

return new;

end;
$$ language 'plpgsql';

-- dovrò definire ulteriori trigger per gestire gli eventi di update e delete 

-- alcuni DBMS tra cui PostgreSQL supportano la nozione di materialized view 
-- https://www.postgresql.org/docs/current/sql-creatematerializedview.html

-- viste aggiornabili
-- https://www.postgresql.org/docs/current/sql-createview.html
-- si vedano le condizioni di aggiornabilità di una vista:
--- The view must have exactly one entry in its FROM list, which must be a table or another updatable view.
--- The view definition must not contain WITH, DISTINCT, GROUP BY, HAVING, LIMIT, or OFFSET clauses at the top level.
--- The view definition must not contain set operations (UNION, INTERSECT or EXCEPT) at the top level.
--- The view's select list must not contain any aggregates, window functions or set-returning functions.

create view imdb.thriller_movies as 
select * from imdb.genre
where genre = 'Thriller'
with check option;

drop view imdb.thriller_movies;

insert into imdb.thriller_movies values ('0044000', 'Thriller');
insert into imdb.thriller_movies values ('0044000', 'Super Horror'); -- not working with check option
insert into imdb.thriller_movies values ('0044000', 'Super Comedy'); -- not working with check option

select * 
from imdb.genre 
where movie = '0044000';
