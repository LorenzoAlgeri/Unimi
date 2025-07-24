-- scopus relational schema
publication(id, title*, citedby*, issn*, pubdate*, pubname*, pubtype*) PK(id)

affiliation(afid, afname*, afcity*, afcountry*) PK(afid)

author(authid, authname*, authsurname*, given_name*) PK(authid)

pub_author(pubid, authid, afid) PK(pubid, authid, afid)

FK
pub_author.pubid -> publication.id
pub_author.authid -> author.authid
pub_author.afid -> affiliation.afid

abstract(pubid, language, content*) PK(pubid, language)

FK
abstract.pubid -> publication.id

keyword(pubid, keyword, language) PK(pubid, keyword)

FK
keyword.pubid -> publication.id

expertise(field, parent_field*) PK(field)

expertise.parent_field -> expertise.field 

author_expertise(authid, expertise) PK(authid, expertise)

FK
author_expertise.authid -> author.authid
author_expertise.expertise -> expertise.field 


-- esercizio A: derivare lo schema ER del db scopus4ds (reverse engineering)


-- esercizio B: algebra relazionale (e successivamente SQL)

-- trovare le keyword che non sono mai usate su pubblicazioni di tipo articolo 

A = (π(id)(𝛔(pubtype = 'article') (PUBLICATION)))
B = π(keyword)((KEYWORD) ⋈(pubid = id) A)
risultato = (π(keyword)(KEYWORD)) - B

with article_keyword as (
    select distinct keyword 
    from publications.keyword inner join publications.publication 
    on pubid = id
    where pubtype = 'article'
)
select keyword
from publications.keyword
except 
select *
from article_keyword;

-- soluzione con left join
with article_keyword as (
    select distinct keyword 
    from publications.keyword inner join publications.publication 
    on pubid <> id
    where pubtype = 'article'
)
select k.keyword 
from publications.keyword k left join article_keyword ak on k.keyword = ak.keyword
where ak.keyword is null;

-- soluzione con subquery
select distinct keyword 
from publications.keyword 
where keyword not in (
select distinct keyword 
    from publications.keyword inner join publications.publication 
    on pubid = id
    where pubtype = 'article');

-- soluzione errata
select distinct keyword 
    from publications.keyword inner join publications.publication 
    on pubid = id
    where pubtype <> 'article';

keyword
k1 - p1 
k2 - p2 
k1 - p3 

publication
p1 article
p2 book
p3 book  


-- trovare le pubblicazioni che hanno sia 'social networks' sia 'community detection' come keyword
-- due soluzioni:
--- intersect
--- self-join

-- trovare le keyword usate in tutte le pubblicazioni della rivista ' computer communications'
-- una keyword è nel risultato se non esiste una pubblicazione per la quale non esiste l'associazione della pubblicazione con la keyword
A = ρ(pubid <- id) (π(id)(𝛔(pubname='computer communications')(PUBLICATION)))
B = π(keyword, pubid)(KEYWORD)
-- numeratore: keyword associate a pubblicazioni
-- denominatore: le pubblicazioni di interesse (A)
risultato = B ÷ A

select k1.keyword
from publications.keyword k1 
where not exists (
    select *
    from publications.publication p
    where pubname = 'computer communications' and not exists (
        select *
        from publications.keyword k2 
        where k1.keyword = k2.keyword and p.id = k2.pubid
    )
)


-- esercizio C: SQL

-- trovare le pubblicazioni che hanno una keyword in comune
select k1.pubid, k2.pubid
from publications.keyword k1 inner join publications.keyword k2 on k1.keyword = k2.keyword and k1.pubid < k2.pubid 

-- soluzione alternativa ma estremamente inefficiente
select pubid 
from publications.keyword k1 
where exists (
    select * 
    from publications.keyword k2
    where k1.keyword = k2.keyword and k1.pubid < k2.pubid
)

-- trovare gli autori che partecipano a una pubblicazione con due affiliazioni diverse
select pa1.authid
from publications.pub_author pa1 inner join publications.pub_author pa2 on pa1.pubid =pa2.pubid and pa1.authid =pa2.authid and pa1.afid <> pa2.afid;

-- trovare le pubblicazioni con citazioni superiori alla media considerando le pubblicazioni della rivista 'information and computer security'
with journal_citations as (
select avg(citedby) as avg_citations
from publications.publication
where pubname = 'information and computer security')
select id, title, pubname, citedby, avg_citations
from publications.publication, journal_citations
where pubname = 'information and computer security' and citedby > avg_citations;

-- per ogni pubblicazione X sulla rivista Y trovare le pubblicazioni di Y  con citazioni superiori alla media di Y
with journal_avg as (
    select pubname, avg(citedby) as cit_avg
    from publications.publication
    where pubtype = 'article'
    group by pubname 
 )
 select *
 from publications.publication p
 where p.citedby > (
    select cit_avg 
    from journal_avg
    where pubname = p.pubname 
 )

 -- soluzione alternativa con join
 with journal_avg as (
    select pubname, avg(citedby) as cit_avg
    from publications.publication
    where pubtype = 'article'
    group by pubname 
 )
 select *
 from publications.publication p inner join journal_avg ja on p.pubname = ja.pubname and p.citedby > ja.cit_avg;

-- trovare i co-autori di montanelli stefano (autori di pubblicazioni in cui montanelli stefano è autore)
select distinct a2.*
from publications.author a1 inner join publications.pub_author pa1 on a1.authid = pa1.authid inner join publications.pub_author pa2 on pa1.pubid = pa2.pubid inner join publications.author a2 on pa2.authid = a2.authid\
where a1.authname = 'montanelli s.' and a2.given_name <> 'montanelli s.';

-- trovare le keyword che non sono mai usate su pubblicazioni di tipo articolo
with keywords_on_articles as (
select distinct keyword
from publications.publication inner join publications.keyword on id = pubid 
where pubtype = 'article') 
select distinct keyword
from publications.keyword
except 
select keyword
from keywords_on_articles;

-- trovare le keyword usate solo sulla rivista 'information and computer security'
select distinct keyword
from publications.keyword inner join publications.publication on pubid = id
where pubname = 'information and computer security'
except
select distinct keyword
from publications.keyword inner join publications.publication on pubid = id
where pubname <> 'information and computer security';

-- Trovare la pubblicazione con il maggior numero di keyword associate
with p_count as (
    select pubid, count(keyword) as k_count
    from publications.keyword
    group by pubid 
), p_max as (
    select max(k_count) as k_max
    from p_count 
)
select *
from p_count inner join p_max on p_count.k_count = p_max.k_max;

-- soluzione alternativa
with p_count as (
    select pubid, count(keyword) as k_count
    from publications.keyword
    group by pubid 
)
select *
from p_count
where k_count = (
    select max(k_count) as k_max
    from p_count 
);

-- soluzione alternativa
select pubid, count(keyword) as k_count
from publications.keyword
group by pubid 
having count(keyword) = (
    select count(keyword)
    from publications.keyword
    group by pubid 
    order by 1 desc 
    limit 1
);

-- Trovare la keyword usata il maggior numero di volte 
with keyword_count as (
select keyword, count(distinct pubid) as count_p
from publications.keyword
group by keyword), max_keyword as (
select max(count_p) as max_p
from keyword_count)
select keyword, max_p
from keyword_count inner join max_keyword on count_p = max_p;

-- trovare tutte le expertise da cui discende conceptual design
WITH RECURSIVE search_parent(field, parent_field, distance) AS (
SELECT field, parent_field, 1
FROM publications.expertise
UNION
SELECT sp.field, pe.parent_field, distance + 1
FROM search_parent sp, publications.expertise pe
WHERE sp.parent_field = pe.field
)
SELECT parent_field, distance
FROM search_parent
WHERE field = 'conceptual design' and parent_field is not null;

-- trovare gli autori che non pubblicano con persone appartenenti alla medesima affiliazione
select authid
from publications.pub_author pa1
where not exists (
select *
from publications.pub_author pa2
where pa1.pubid = pa2.pubid and pa1.afid = pa2.afid and pa1.authid = pa2.authid);
