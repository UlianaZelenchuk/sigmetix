CREATE DATABASE sigmetix;

CREATE TABLE users
(
    uid int NOT NULL PRIMARY KEY,
    firstName varchar(128),
    lastName varchar(128),
    birthDay date,
    dateChange datetime,
    description text
);
