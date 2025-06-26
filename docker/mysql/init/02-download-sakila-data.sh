#!/bin/bash

# Script pour télécharger et installer les données Sakila
# Ce script s'exécute automatiquement lors de l'initialisation du conteneur MySQL

echo "Téléchargement des données Sakila..."

# URL des données Sakila
SAKILA_DATA_URL="https://downloads.mysql.com/docs/sakila-data.sql"

# Télécharger le fichier de données
curl -L -o /tmp/sakila-data.sql "$SAKILA_DATA_URL" 2>/dev/null || {
    echo "Échec du téléchargement, utilisation des données de base..."
    
    # Si le téléchargement échoue, créer des données de base
    cat > /tmp/sakila-data.sql << 'EOF'
-- Données de base pour Sakila (version simplifiée)
USE sakila;

-- Insertion des langues
INSERT INTO language (name) VALUES 
('English'), ('Italian'), ('Japanese'), ('Mandarin'), ('French'), ('German');

-- Insertion des catégories
INSERT INTO category (name) VALUES 
('Action'), ('Animation'), ('Children'), ('Classics'), ('Comedy'), 
('Documentary'), ('Drama'), ('Family'), ('Foreign'), ('Games'), 
('Horror'), ('Music'), ('New'), ('Sci-Fi'), ('Sports'), ('Travel');

-- Insertion des pays
INSERT INTO country (country) VALUES 
('Afghanistan'), ('Algeria'), ('American Samoa'), ('Angola'), ('Anguilla'),
('Argentina'), ('Armenia'), ('Australia'), ('Austria'), ('Azerbaijan'),
('Bahrain'), ('Bangladesh'), ('Belarus'), ('Belgium'), ('Bolivia'),
('Brazil'), ('Brunei'), ('Bulgaria'), ('Cambodia'), ('Cameroon'),
('Canada'), ('Chad'), ('Chile'), ('China'), ('Colombia'),
('Czech Republic'), ('Denmark'), ('Dominican Republic'), ('Ecuador'), ('Egypt'),
('Estonia'), ('Ethiopia'), ('Faroe Islands'), ('Finland'), ('France'),
('French Guiana'), ('French Polynesia'), ('Gambia'), ('Germany'), ('Greece'),
('Greenland'), ('Holy See (Vatican City State)'), ('Hong Kong'), ('Hungary'), ('India'),
('Indonesia'), ('Iran'), ('Iraq'), ('Israel'), ('Italy'),
('Japan'), ('Kazakhstan'), ('Kenya'), ('Kuwait'), ('Latvia'),
('Liechtenstein'), ('Lithuania'), ('Madagascar'), ('Malawi'), ('Malaysia'),
('Mexico'), ('Moldova'), ('Morocco'), ('Mozambique'), ('Myanmar'),
('Nauru'), ('Nepal'), ('Netherlands'), ('New Zealand'), ('Nigeria'),
('North Korea'), ('Norway'), ('Oman'), ('Pakistan'), ('Paraguay'),
('Peru'), ('Philippines'), ('Poland'), ('Puerto Rico'), ('Romania'),
('Russian Federation'), ('Saint Vincent and the Grenadines'), ('Saudi Arabia'), ('Senegal'), ('Slovakia'),
('South Africa'), ('South Korea'), ('Spain'), ('Sri Lanka'), ('Sudan'),
('Sweden'), ('Switzerland'), ('Taiwan'), ('Tanzania'), ('Thailand'),
('Tonga'), ('Tunisia'), ('Turkey'), ('Turkmenistan'), ('Tuvalu'),
('Ukraine'), ('United Arab Emirates'), ('United Kingdom'), ('United States'), ('Venezuela'),
('Vietnam'), ('Virgin Islands, U.S.'), ('Yemen'), ('Yugoslavia'), ('Zambia');

-- Insertion de quelques villes
INSERT INTO city (city, country_id) VALUES 
('A Corua (La Corua)', 87), ('Abha', 82), ('Abu Dhabi', 101), ('Acua', 60), ('Adana', 97),
('Addis Abeba', 31), ('Aden', 107), ('Agadir', 62), ('Ahmadnagar', 44), ('Akishima', 50),
('Akron', 103), ('al-Ayn', 101), ('al-Hawiya', 82), ('al-Manama', 11), ('al-Qadarif', 89),
('Aleppo', 94), ('Alexandria', 29), ('Algiers', 2), ('Allappuzha (Alleppey)', 44), ('Almaty', 51),
('Alvorada', 15), ('Ambattur', 44), ('Amersfoort', 67), ('Amroha', 44), ('Amsterdam', 67),
('Angra dos Reis', 15), ('Anpolis', 15), ('Antofagasta', 23), ('Aparecida de Goinia', 15), ('Apeldoorn', 67);

-- Insertion de quelques adresses
INSERT INTO address (address, district, city_id, postal_code, phone) VALUES 
('47 MySakila Drive', 'Alberta', 1, '', ''), ('28 MySQL Boulevard', 'QLD', 2, '', ''),
('23 Workhaven Lane', 'Alberta', 1, '', ''), ('1411 Lillydale Drive', 'QLD', 2, '', ''),
('1913 Hanoi Way', 'Nagasaki', 3, '35200', '28303384290'), ('1121 Loja Avenue', 'California', 4, '17886', '838635286649'),
('692 Joliet Street', 'Attika', 5, '83579', '448477190408'), ('1566 Inegl Manor', 'Mandalay', 6, '53561', '705814003527'),
('53 Idfu Parkway', 'Nantou', 7, '42399', '10655648674'), ('1795 Santiago de Compostela Way', 'Texas', 8, '18743', '860452626434');

-- Insertion de quelques magasins
INSERT INTO store (manager_staff_id, address_id) VALUES (1, 1), (2, 2);

-- Insertion du personnel
INSERT INTO staff (first_name, last_name, address_id, email, store_id, active, username, password) VALUES 
('Mike', 'Hillyer', 3, 'Mike.Hillyer@sakilastaff.com', 1, 1, 'Mike', '8cb2237d0679ca88db6464eac60da96345513964'),
('Jon', 'Stephens', 4, 'Jon.Stephens@sakilastaff.com', 2, 1, 'Jon', '8cb2237d0679ca88db6464eac60da96345513964');

-- Mise à jour des magasins avec les managers
UPDATE store SET manager_staff_id = 1 WHERE store_id = 1;
UPDATE store SET manager_staff_id = 2 WHERE store_id = 2;

-- Insertion de quelques clients
INSERT INTO customer (store_id, first_name, last_name, email, address_id, active, create_date) VALUES 
(1, 'MARY', 'SMITH', 'MARY.SMITH@sakilacustomer.org', 5, 1, '2006-02-14 22:04:36'),
(1, 'PATRICIA', 'JOHNSON', 'PATRICIA.JOHNSON@sakilacustomer.org', 6, 1, '2006-02-14 22:04:36'),
(1, 'LINDA', 'WILLIAMS', 'LINDA.WILLIAMS@sakilacustomer.org', 7, 1, '2006-02-14 22:04:36'),
(2, 'BARBARA', 'JONES', 'BARBARA.JONES@sakilacustomer.org', 8, 1, '2006-02-14 22:04:36'),
(2, 'ELIZABETH', 'BROWN', 'ELIZABETH.BROWN@sakilacustomer.org', 9, 1, '2006-02-14 22:04:36');

-- Insertion de quelques acteurs
INSERT INTO actor (first_name, last_name) VALUES 
('PENELOPE', 'GUINESS'), ('NICK', 'WAHLBERG'), ('ED', 'CHASE'), ('JENNIFER', 'DAVIS'), ('JOHNNY', 'LOLLOBRIGIDA'),
('BETTE', 'NICHOLSON'), ('GRACE', 'MOSTEL'), ('MATTHEW', 'JOHANSSON'), ('JOE', 'SWANK'), ('CHRISTIAN', 'GABLE');

-- Insertion de quelques films
INSERT INTO film (title, description, release_year, language_id, rental_duration, rental_rate, length, replacement_cost, rating) VALUES 
('ACADEMY DINOSAUR', 'A Epic Drama of a Feminist And a Mad Scientist who must Battle a Teacher in The Canadian Rockies', 2006, 1, 6, 0.99, 86, 20.99, 'PG'),
('ACE GOLDFINGER', 'A Astounding Epistle of a Database Administrator And a Explorer who must Find a Car in Ancient China', 2006, 1, 3, 4.99, 48, 12.99, 'G'),
('ADAPTATION HOLES', 'A Astounding Reflection of a Lumberjack And a Car who must Sink a Lumberjack in A Baloon Factory', 2006, 1, 7, 2.99, 50, 18.99, 'NC-17'),
('AFFAIR PREJUDICE', 'A Fanciful Documentary of a Frisbee And a Lumberjack who must Chase a Monkey in A Shark Tank', 2006, 1, 5, 2.99, 117, 26.99, 'G'),
('AFRICAN EGG', 'A Fast-Paced Documentary of a Pastry Chef And a Dentist who must Pursue a Forensic Psychologist in The Gulf of Mexico', 2006, 1, 6, 2.99, 130, 22.99, 'G');

-- Insertion de quelques inventaires
INSERT INTO inventory (film_id, store_id) VALUES 
(1, 1), (1, 1), (1, 1), (1, 1), (1, 2), (1, 2), (1, 2), (1, 2),
(2, 1), (2, 1), (2, 1), (2, 2), (2, 2), (2, 2),
(3, 1), (3, 1), (3, 2), (3, 2),
(4, 1), (4, 1), (4, 2), (4, 2),
(5, 1), (5, 1), (5, 2), (5, 2);

-- Insertion de quelques relations film-acteur
INSERT INTO film_actor (actor_id, film_id) VALUES 
(1, 1), (1, 23), (1, 25), (1, 106), (1, 140), (1, 166), (1, 277), (1, 361), (1, 438), (1, 499),
(2, 3), (2, 31), (2, 47), (2, 105), (2, 132), (2, 145), (2, 226), (2, 249), (2, 314), (2, 321);

-- Insertion de quelques relations film-catégorie
INSERT INTO film_category (film_id, category_id) VALUES 
(1, 6), (2, 11), (3, 6), (4, 11), (5, 8);

-- Insertion dans film_text
INSERT INTO film_text (film_id, title, description) VALUES 
(1, 'ACADEMY DINOSAUR', 'A Epic Drama of a Feminist And a Mad Scientist who must Battle a Teacher in The Canadian Rockies'),
(2, 'ACE GOLDFINGER', 'A Astounding Epistle of a Database Administrator And a Explorer who must Find a Car in Ancient China'),
(3, 'ADAPTATION HOLES', 'A Astounding Reflection of a Lumberjack And a Car who must Sink a Lumberjack in A Baloon Factory'),
(4, 'AFFAIR PREJUDICE', 'A Fanciful Documentary of a Frisbee And a Lumberjack who must Chase a Monkey in A Shark Tank'),
(5, 'AFRICAN EGG', 'A Fast-Paced Documentary of a Pastry Chef And a Dentist who must Pursue a Forensic Psychologist in The Gulf of Mexico');

-- Insertion de quelques locations
INSERT INTO rental (rental_date, inventory_id, customer_id, return_date, staff_id) VALUES 
('2005-05-24 22:53:30', 367, 130, '2005-05-26 22:04:30', 1),
('2005-05-24 22:54:33', 1525, 459, '2005-05-28 19:40:33', 1),
('2005-05-24 23:03:39', 1711, 408, '2005-06-01 22:12:39', 1),
('2005-05-24 23:04:41', 2452, 333, '2005-06-03 01:43:41', 2),
('2005-05-24 23:05:21', 2079, 222, '2005-06-02 04:33:21', 1);

-- Insertion de quelques paiements
INSERT INTO payment (customer_id, staff_id, rental_id, amount, payment_date) VALUES 
(130, 1, 1, 2.99, '2005-05-24 22:53:30'),
(459, 1, 2, 0.99, '2005-05-24 22:54:33'),
(408, 1, 3, 4.99, '2005-05-24 23:03:39'),
(333, 2, 4, 2.99, '2005-05-24 23:04:41'),
(222, 1, 5, 2.99, '2005-05-24 23:05:21');

EOF
}

echo "Installation des données Sakila..."
mysql -u root -p"$MYSQL_ROOT_PASSWORD" sakila < /tmp/sakila-data.sql

echo "Données Sakila installées avec succès!"