# club Coco – website

PHP + MySQL-website voor club Coco (Kapellen), met adminpagina's waar de eigenaar zelf
de kaart, openingsuren, foto's en teksten aanpast. Zonder code of GitHub.

## Mappen

```
index.php             de website (haalt alles uit de database)
config.sample.php     voorbeeld-instellingen → kopieer naar config.php
assets/css/           style.css (site) en admin.css (adminpagina's)
images/               vaste foto's en logo's
uploads/              foto's die via admin geüpload worden
includes/             databaseverbinding, hulpfuncties, login-check
admin/                alle adminpagina's
database/schema.sql   tabellen + de echte kaart als startdata
```

## Lokaal draaien (XAMPP of Laragon)

1. Zet deze map in `C:\xampp\htdocs\club-coco-website` (of `C:\laragon\www\...`).
2. Start Apache en MySQL.
3. Open phpMyAdmin (http://localhost/phpmyadmin), maak een database `clubcoco`
   (utf8mb4_unicode_ci) en importeer `database/schema.sql`.
4. Kopieer `config.sample.php` naar `config.php` en controleer de gegevens.
5. Surf naar http://localhost/club-coco-website/admin/ en maak het eerste account aan.
6. De site zelf: http://localhost/club-coco-website/

## Online zetten (bv. Combell, one.com, Hostinger)

1. Maak bij je host een MySQL-database aan en importeer `database/schema.sql` via phpMyAdmin.
2. Upload alle bestanden (via FTP of de bestandsbeheerder), **behalve** `.git`.
3. Maak op de server een `config.php` met de databasegegevens van je host en zet `'debug' => false`.
4. Zet SSL (HTTPS) aan bij je host en haal de hekjes weg bij de HTTPS-regels in `.htaccess`.
5. Ga naar `jouwdomein.be/admin/` en maak het account voor de eigenaar aan.
   Daarna is `install.php` automatisch uitgeschakeld.
6. Zorg dat de map `uploads/` schrijfbaar is (rechten 755).

## Beveiliging (wat er al in zit)

- Login met `password_hash` / `password_verify`, nieuwe sessie-id na inloggen
- Elke adminpagina begint met `require '../includes/auth.php'` → niet ingelogd = naar login
- Na 5 foute pogingen 15 minuten geblokkeerd; automatisch uitgelogd na 2 uur niets doen
- CSRF-token op elk formulier, prepared statements (PDO) voor elke query
- Alles uit de database wordt met `h()` getoond (tegen XSS)
- Uploads: enkel echte JPG/PNG/WEBP tot 8 MB, nieuwe willekeurige bestandsnaam,
  en in `uploads/` kan geen PHP uitgevoerd worden
- `includes/`, `database/` en `config.php` zijn niet bereikbaar via de browser
- Adminpagina's hebben `noindex` (niet in Google) en er staat nergens een link naar

## Database aanpassen

Wil je de startdata opnieuw? Importeer `schema.sql` opnieuw. **Let op:** dit wist alles,
ook accounts en aanpassingen van de eigenaar. Doe dat dus nooit op de live site.
