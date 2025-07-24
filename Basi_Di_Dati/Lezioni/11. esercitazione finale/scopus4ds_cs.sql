CREATE TABLE publications.publication (
    id integer PRIMARY KEY,
    title character varying,
    citedby integer,
    issn character varying,
    pubdate date,
    pubname character varying,
    pubtype character varying
);

CREATE TABLE publications.affiliation (
    afid character varying PRIMARY KEY,
    afname character varying,
    afcity character varying,
    afcountry character varying
);

CREATE TABLE publications.author (
    authid character varying PRIMARY KEY,
    authname character varying,
    authsurname character varying,
    given_name character varying
);

CREATE TABLE publications.pub_author (
    pubid integer REFERENCES publications.publication(id),
    authid character varying REFERENCES publications.author(authid),
    afid character varying REFERENCES publications.affiliation(afid),
    PRIMARY KEY(pubid, authid, afid)
);

CREATE TABLE publications.abstract (
    pubid integer NOT NULL REFERENCES publications.publication(id),
    language character varying NOT NULL,
    content text,
    PRIMARY KEY(pubid, language)
);

CREATE TABLE publications.keyword (
    pubid integer NOT NULL REFERENCES publications.publication(id),,
    keyword character varying NOT NULL,
    language character varying NOT NULL,
    PRIMARY KEY(pubid, keyword)
);

CREATE TABLE publications.expertise (
field character varying PRIMARY KEY,
parent_field character varying
);

ALTER TABLE publications.expertise ADD CONSTRAINT expertise_fk FOREIGN KEY (parent_field) REFERENCES publications.expertise(field) ON UPDATE CASCADE ON DELETE NO ACTION;

INSERT INTO publications.expertise(field) values ('computer science');
INSERT INTO publications.expertise(field) values ('historical studies');
INSERT INTO publications.expertise(field, parent_field) values ('data management', 'computer science');
INSERT INTO publications.expertise(field, parent_field) values ('artificial intelligence', 'computer science');
INSERT INTO publications.expertise(field, parent_field) values ('algorithm complexity', 'computer science');
INSERT INTO publications.expertise(field, parent_field) values ('relational data model', 'data management');
INSERT INTO publications.expertise(field, parent_field) values ('conceptual design', 'relational data model');

CREATE TABLE publications.author_expertise (
    authid character varying NOT NULL REFERENCES publications.author(authid),
    expertise character varying REFERENCES publications.expertise(field),
    PRIMARY KEY(authid, expertise) 
);