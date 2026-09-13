# Uitgevoerde controles — 3 september 2026

- Alle **24 PHP-bestanden** gecontroleerd met de PHP 8.4.1 CLI (`php -l`): geen syntaxfouten.
- **26 logische tests geslaagd** (`tests/run.php`): datums/weekgrenzen, komma-invoer, nul versus ontbrekende gegevens, bloeddrukvalidatie, schalen, voortschrijdend kalendergemiddelde, progressive overload, tijdzoneconversie, trainingsvolume zonder warming-up, cardiogegevens, Hevy-paginering, incrementale events en nieuwste verwijdering versus oudere update.
- JavaScript-syntax van `dashboard.js` gecontroleerd met Node: geslaagd.
- **16 lokale HTTP-controles geslaagd** met de ingebouwde PHP-server en `public/` als documentroot: login bereikbaar, vijf persoonlijke routes redirecten zonder login, coach/ruwe data zonder toegang geweigerd, fout token geweigerd, login-POST zonder CSRF geweigerd, uitloggen via GET geweigerd, vier private paden niet bereikbaar, lokale Chart.js bereikbaar. Cache-, CSP- en HttpOnly-headers gecontroleerd.
- Officiële Hevy OpenAPI-documentatie gelezen; endpoints, header, paginering, setvelden en update-/delete-events vergeleken met de implementatie. Testverkeer voor import gebruikte uitsluitend fictieve gegevens en een gesimuleerde transportfunctie.
- Excel-reference gelezen: `Exsel Rvdh 2026 New.xlsx`, `Blad1`, koppen en alle unieke tekstlabels. Richting van cravings, definitie van tijd in bed, maaltijdmarkering en toelichtingen verwerkt. Bronbestand niet gewijzigd.

## Nog op de eigen hosting uitvoeren

Er is in deze werkomgeving geen MySQL/MariaDB-server of echte Hevy-key ingesteld. Daarom zijn SQL-installatie, succesvolle login, daadwerkelijke opslag/transacties, coachlink-uitgifte/intrekking en live API-synchronisatie nog niet end-to-end uitgevoerd. `tests/integration.php` en de acceptatiestappen in README staan klaar voor een aparte testdatabase en de hostinginstallatie.

Geen visuele browsertest of test op een echte telefoon uitgevoerd. De vormgeving bevat responsive layouts, maar moet bij ingebruikname nog op de gewenste apparaten worden bekeken. Geen hostingdeployment, echte accounts, cronjob of gezondheidsdata aangemaakt.
