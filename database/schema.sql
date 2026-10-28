-- club Coco – database
-- Importeer dit bestand in phpMyAdmin (tab "Importeren") of via:
--   mysql -u root -p clubcoco < database/schema.sql
-- Let op: dit maakt alle tabellen opnieuw aan en wist bestaande data.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS opening_hours;
DROP TABLE IF EXISTS photos;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Accounts voor de adminpagina's (geen registratie: eerste account via admin/install.php)
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  naam          VARCHAR(100) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mislukte loginpogingen (om raden van wachtwoorden te blokkeren)
CREATE TABLE login_attempts (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip           VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorieën van de kaart. kolom = in welke kolom (1, 2 of 3) ze op de site staan
CREATE TABLE categories (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  naam      VARCHAR(100) NOT NULL,
  kolom     TINYINT UNSIGNED NOT NULL DEFAULT 1,
  volgorde  INT NOT NULL DEFAULT 0,
  notitie   VARCHAR(255) NULL,
  zichtbaar TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Items op de kaart. prijs is tekst zodat ook "7,90 / 32" of "+0,50" kan
CREATE TABLE menu_items (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  naam        VARCHAR(150) NOT NULL,
  beschrijving VARCHAR(255) NULL,
  prijs       VARCHAR(30) NULL,
  favoriet    TINYINT(1) NOT NULL DEFAULT 0,
  zichtbaar   TINYINT(1) NOT NULL DEFAULT 1,
  volgorde    INT NOT NULL DEFAULT 0,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Openingsuren. dag: 1 = maandag ... 6 = zaterdag, 0 = zondag (zoals JavaScript/PHP)
CREATE TABLE opening_hours (
  dag      TINYINT UNSIGNED PRIMARY KEY,
  gesloten TINYINT(1) NOT NULL DEFAULT 0,
  open     TIME NULL,
  sluit    TIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foto's. plek = waar op de site; formaat bepaalt de grootte in de galerij
CREATE TABLE photos (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pad       VARCHAR(255) NOT NULL,
  alt       VARCHAR(255) NOT NULL DEFAULT '',
  label     VARCHAR(60) NULL,
  plek      ENUM('galerij','kaart','verhaal') NOT NULL DEFAULT 'galerij',
  formaat   ENUM('normaal','breed','hoog') NOT NULL DEFAULT 'normaal',
  volgorde  INT NOT NULL DEFAULT 0,
  zichtbaar TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Losse teksten en gegevens (adres, contact, pastry van de week, ...)
CREATE TABLE settings (
  sleutel VARCHAR(60) PRIMARY KEY,
  waarde  TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Startdata: de echte kaart van club Coco
-- ---------------------------------------------------------------

INSERT INTO categories (id, naam, kolom, volgorde, notitie) VALUES
 (1,  'Specialty coffee',     1, 1, NULL),
 (2,  'Extra''s',             1, 2, NULL),
 (3,  'Chai latte & matcha',  1, 3, NULL),
 (4,  'Verse thee',           1, 4, NULL),
 (5,  'Warme chocomelk',      2, 1, NULL),
 (6,  'Homemade ice tea',     2, 2, NULL),
 (7,  'Homemade lemonades',   2, 3, NULL),
 (8,  'Cava & wijn',          2, 4, NULL),
 (9,  'Bieren',               2, 5, NULL),
 (10, 'Frisdranken',          3, 1, NULL),
 (11, 'Pastries & snacks',    3, 2, NULL),
 (12, 'Ontbijt & lunch',      3, 3, 'Ons ontbijt- en lunchmenu staat binnenkort online.');

INSERT INTO menu_items (category_id, naam, beschrijving, prijs, favoriet, volgorde) VALUES
 (1, 'Espresso (dubbel)', NULL, '3,00', 0, 1),
 (1, 'Americano', NULL, '3,40', 1, 2),
 (1, 'Filter', NULL, '4,00', 0, 3),
 (1, 'Cortado', NULL, '3,60', 0, 4),
 (1, 'Cappuccino', NULL, '4,00', 0, 5),
 (1, 'Flat white', NULL, '4,80', 1, 6),
 (1, 'Latte macchiato', NULL, '4,50', 0, 7),

 (2, 'Kannetje koemelk', NULL, '+0,30', 0, 1),
 (2, 'Plantaardige melk', 'haver · kokos · soya · amandel ♥', '+0,50', 0, 2),
 (2, 'Iced', NULL, '+0,50', 0, 3),
 (2, 'Vanille', NULL, '+1,00', 0, 4),
 (2, 'Caramel', NULL, '+1,00', 0, 5),

 (3, 'Chai latte massala', NULL, '4,60', 0, 1),
 (3, 'Chai latte unsweetened', NULL, '4,60', 0, 2),
 (3, 'Matcha latte', NULL, '5,50', 0, 3),
 (3, 'Matcha Coco', 'iced met kokosmelk en mangopuree', '6,50', 1, 4),

 (4, 'Earl grey', NULL, '3,40', 0, 1),
 (4, 'Groene thee', NULL, '3,40', 0, 2),
 (4, 'Kamille', NULL, '3,40', 0, 3),
 (4, 'Rode vruchtenmix', NULL, '3,40', 1, 4),
 (4, 'Verse munt', NULL, '4,00', 0, 5),
 (4, 'Verse gemberthee', NULL, '4,00', 1, 6),

 (5, 'Melkchocolade', NULL, '4,00', 0, 1),
 (5, 'Pure chocolade', NULL, '4,00', 0, 2),
 (5, 'Slagroom', NULL, '+1,00', 0, 3),

 (6, 'Framboos – zwarte thee', NULL, '4,50', 1, 1),
 (6, 'Granaatappel – munt', NULL, '4,50', 0, 2),
 (6, 'Cold brewed tea – rode vruchten', NULL, '4,50', 0, 3),

 (7, 'Gember – citroen', NULL, '4,50', 0, 1),
 (7, 'Vlierbloesem – citroen', NULL, '4,50', 0, 2),

 (8, 'Cava', 'GM Brut · glas / fles', '7,90 / 32', 1, 1),
 (8, 'Witte wijn', 'glas / fles', '6,90 / 32', 0, 2),
 (8, 'Rode wijn', 'glas / fles', NULL, 1, 3),
 (8, 'Rosé', 'Ilagares, Spaans 2025 · glas / fles', NULL, 0, 4),
 (8, 'Oranje wijn', 'Materia Prima Orange 2025 · glas / fles', '9 / 45', 0, 5),

 (9, 'Stella – Stella 0%', NULL, '3,20', 0, 1),
 (9, 'Fourchette', NULL, '5,00', 0, 2),
 (9, 'Liefmans – Liefmans 0%', NULL, '3,90', 0, 3),
 (9, 'Triple d''Anvers', NULL, '4,00', 0, 4),
 (9, 'Duvel', NULL, '4,80', 0, 5),

 (10, 'Plat water – bruis water', NULL, '3,20', 0, 1),
 (10, 'Chaudfontaine 0,5 L', NULL, '5,50', 0, 2),
 (10, 'Coco Cola – Cola zero', NULL, '3,20', 0, 3),
 (10, 'Lipton Ice Tea – Ice Tea Green', NULL, '3,20', 0, 4),
 (10, 'Vers appelsap', NULL, '3,90', 1, 5),
 (10, 'Vers appelsiensap', NULL, '3,90', 0, 6),
 (10, 'Almdudler', NULL, '4,60', 0, 7),
 (10, 'Jarr kombucha peach – yuzu', NULL, '4,60', 0, 8),
 (10, 'Jarr kombucha ginger lemon', NULL, '4,60', 0, 9),
 (10, 'Big Tom tomatensap', NULL, '4,50', 0, 10),
 (10, 'Schweppes Selection Tonic', NULL, '3,40', 0, 11),
 (10, 'Schweppes Ginger Beer – chili', NULL, '3,40', 0, 12),

 (11, 'Croissant', NULL, '3,00', 0, 1),
 (11, 'Croissant confituur', NULL, '4,50', 0, 2),
 (11, 'Chocoladebroodje', NULL, '3,00', 0, 3),
 (11, 'Cinnamon bun', NULL, '4,50', 0, 4),
 (11, 'Kardemom bun', NULL, '5,00', 1, 5),
 (11, 'Chocolade bun', NULL, '5,00', 0, 6);

INSERT INTO opening_hours (dag, gesloten, open, sluit) VALUES
 (1, 0, '08:00', '17:00'),
 (2, 0, '08:00', '17:00'),
 (3, 0, '08:00', '17:00'),
 (4, 0, '08:00', '17:00'),
 (5, 0, '08:00', '17:00'),
 (6, 0, '09:00', '16:00'),
 (0, 1, NULL, NULL);

INSERT INTO photos (pad, alt, label, plek, formaat, volgorde) VALUES
 ('images/interieur.jpg', 'Interieur van club Coco met houten stoelen, stalen tafeltjes en een bank op bordeaux tegels', NULL, 'verhaal', 'normaal', 1),
 ('images/ontbijt.jpg', 'Pancakes met banaan en rood fruit, en een bagel met zalm en avocado', 'Ontbijt & lunch', 'kaart', 'normaal', 1),
 ('images/latte-art.jpg', 'Cappuccino met latte art in een bordeaux kopje', 'Specialty coffee', 'kaart', 'normaal', 2),
 ('images/buns.jpg', 'Versgebakken cinnamon en kardemom buns op een bakplaat', 'Huisgemaakte buns', 'kaart', 'normaal', 3),
 ('images/bar-avond.jpg', 'De bar van club Coco ''s avonds, met het logo op de muur en de Rocket-espressomachine', NULL, 'galerij', 'breed', 1),
 ('images/gevel-raam.jpg', 'Het raam van club Coco met schapenvachten en houten krukjes', NULL, 'galerij', 'hoog', 2),
 ('images/menukaart.jpg', 'De menukaart voor de gevel met het club Coco-logo', NULL, 'galerij', 'hoog', 3),
 ('images/cappuccino-menu.jpg', 'Cappuccino en menukaart op een stalen tafeltje', NULL, 'galerij', 'hoog', 4),
 ('images/bekers.jpg', 'Stapels koffiebekers op de toog, met het logo op het raam', NULL, 'galerij', 'hoog', 5),
 ('images/menu-tafel.jpg', 'Menukaart op een stalen tafeltje naast de bordeaux tegels', NULL, 'galerij', 'breed', 6);

INSERT INTO settings (sleutel, waarde) VALUES
 ('melding', ''),
 ('badge', 'Nieuw in Kapellen'),
 ('hero_titel', 'Specialty coffee, ontbijt en lunch'),
 ('hero_tekst', 'Neem plaats aan een van onze stalen tafeltjes of in het raam, en geniet van een goeie koffie met iets lekkers erbij.'),
 ('verhaal_titel', 'Welkom bij de club'),
 ('verhaal_tekst', 'In oktober 2026 openden we de deuren van club Coco in Kapellen. Stephany droomde al lang van een eigen plek: warm, een beetje retro, en met koffie waar je echt voor stilstaat.\n\nBinnen vind je bordeaux tegels, stalen tafeltjes en een Rocket-espressomachine die de hele dag draait. Buiten zit je in het raam op een zachte vacht. Voor koffie, ontbijt of lunch ben je hier op de juiste plek.'),
 ('verhaal_kenmerken', 'Specialty coffee, Ontbijt, Lunch, Homemade ice tea'),
 ('kaart_intro', 'Specialty coffee by Crossroast, huisgemaakte ice tea en verse pastries. Met een ♥ zijn favorieten van het huis.'),
 ('pastry_van_de_week', ''),
 ('adres_straat', 'Stationsstraat 22'),
 ('adres_gemeente', '2950 Kapellen'),
 ('email', ''),
 ('telefoon', ''),
 ('instagram', 'coffeeclubcoco'),
 ('zenchef_id', '');
