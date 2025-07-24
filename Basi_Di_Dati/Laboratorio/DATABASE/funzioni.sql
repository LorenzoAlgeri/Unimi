
select *
from imdb.movie inner join imdb.ratings on movie.id = ratings.movie
where movie.official_title = 'Inception'
