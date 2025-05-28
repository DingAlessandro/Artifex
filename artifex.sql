create database artifex;
use artifex;

create table Lingua(
lingua varchar(30) primary key
);

create table Utente(
username varchar(255) primary key,
pwd varchar(255) not null,
nome varchar(30) not null,
email varchar(64) not null,
nazionalita varchar(30),
telefono varchar(30),
lingua varchar(30),
tipo enum("turista", "amministratore"),
foreign key (lingua) references Lingua(lingua)
);

CREATE TABLE Guida(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(30),
    cognome VARCHAR(30),
    data_nascita DATE NOT NULL,
    titolo_studio VARCHAR(30) NOT NULL,
    luogo_nascita VARCHAR(30) NOT NULL
);

CREATE TABLE Lingua_Guida(
    id_guida INT,
    lingua VARCHAR(30),
    livello ENUM("normale", "avanzato", "madre lingua"),
    FOREIGN KEY (id_guida) REFERENCES Guida(id),
    FOREIGN KEY (lingua) REFERENCES Lingua(lingua),
    PRIMARY KEY (id_guida, lingua)
);

create table Evento(
   id INT AUTO_INCREMENT PRIMARY KEY,
   lingua varchar(30),
   prezzo double(10,2),
   guida int,
   foreign key(guida) references Guida(id),
   foreign key(lingua) references Lingua(lingua)
)

create table Evento_prenotato(
	id_evento int,
	utente varchar(255),
	stato enum("prenotato", "pagato"),
	foreign key(id_evento) references Evento(id),
	foreign key(utente) references Utente(username),
	primary key(id_evento, utente)
);

create table  Visita(
	titolo varchar(30) primary key,
	durata int,
	luogo varchar(30),
	img varchar(255) not null
);

create table Evento_Visita(
	visita varchar(30),
	id_evento int,
	foreign key(visita) references Visita(titolo),
	foreign key(id_evento) references Evento(id),
	primary key(visita, id_evento)
);


INSERT INTO Lingua (lingua) VALUES 
('Italiano'), 
('Inglese'), 
('Francese'), 
('Spagnolo'), 
('Tedesco');

-- Popolamento tabella Utente
INSERT INTO Utente (username, pwd, nome, email, nazionalita, telefono, lingua, tipo) VALUES
('admin', 'admin', 'Marco', 'marco@email.com', 'Italiana', '+39123456789', 'Italiano', 'turista'),
('admin1', 'admin123', 'Admin', 'admin@artifex.com', 'Italiana', '+39987654321', 'Italiano', 'amministratore');
-- Popolamento tabella Guida
INSERT INTO Guida (nome, cognome, data_nascita, titolo_studio, luogo_nascita) VALUES
('Paolo', 'Rossi', '1985-05-15', 'Laurea in Storia dell''Arte', 'Roma'),
('Francesca', 'Bianchi', '1990-08-22', 'Laurea in Archeologia', 'Firenze'),
('Giuseppe', 'Verdi', '1982-11-10', 'Laurea in Architettura', 'Milano'),
('Maria', 'Conti', '1988-03-27', 'Laurea in Lettere', 'Napoli');

-- Popolamento tabella Lingua_Guida
INSERT INTO Lingua_Guida (id_guida, lingua, livello) VALUES
(1, 'Italiano', 'madre lingua'),
(1, 'Inglese', 'avanzato'),
(2, 'Italiano', 'madre lingua'),
(2, 'Francese', 'avanzato'),
(2, 'Inglese', 'normale'),
(3, 'Italiano', 'madre lingua'),
(3, 'Tedesco', 'avanzato'),
(4, 'Italiano', 'madre lingua'),
(4, 'Spagnolo', 'madre lingua');

-- Popolamento tabella Visita
INSERT INTO Visita (titolo, durata, luogo, img) VALUES
('Colosseo', 120, 'Roma', 'img/colosseo.jpg' ),
('Galleria degli Uffizi', 180, 'Firenze','img/galleriaUffizi.jpg'),
('Duomo di Milano', 90, 'Milano','img/duomoM.jpg'),
('Pompei', 240, 'Napoli','img/pompei.jpg'),
('Musei Vaticani', 210, 'Roma', 'img/museiV.jpg');

-- Popolamento tabella Evento
INSERT INTO Evento (lingua, prezzo, guida) VALUES
('Italiano', 25.00, 1),
('Inglese', 30.00, 1),
('Francese', 30.00, 2),
('Tedesco', 28.00, 3),
('Italiano', 20.00, 4),
('Spagnolo', 30.00, 4);

-- Popolamento tabella Evento_Visita
INSERT INTO Evento_Visita (visita, id_evento) VALUES
('Colosseo', 1),
('Colosseo', 2),
('Galleria degli Uffizi', 3),
('Duomo di Milano', 4),
('Pompei', 5),
('Musei Vaticani', 6);

-- Popolamento tabella Evento_prenotato
INSERT INTO Evento_prenotato (id_evento, utente, stato) VALUES
(1, 'admin', 'pagato'),
(5, 'admin', 'prenotato');