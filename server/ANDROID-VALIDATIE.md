# Android / Health Connect — validatie 3 september 2026

## Aanvulling versie 1.3.0 — 14 september 2026

- Android unit tests en releasebuild geslaagd voor versiecode 8 / versie 1.3.0 met compile SDK 35.
- Health Connect-client blijft 1.1.0-alpha11, omdat de stabiele 1.1.0 compile SDK 36 vereist. De gebruikte recordtypen en aggregaties zijn door de compiler gecontroleerd.
- De release-APK is met hetzelfde certificaat ondertekend; SHA-256 certificaat: `6d0a8f5332c09805fdca8addd736727a7a5c702ddc0aa300310adfa4d6eb8768`.
- De ingebouwde overzichtssessie en nieuwe Health Connect-gegevens zijn nog niet op een fysiek toestel en productiehosting getest.

## Uitgevoerd

- Android debug- en release-build geslaagd met JDK 17, Gradle 8.13, AGP 8.10.1, Kotlin 2.1.20, compile SDK 36, target SDK 35 en Health Connect 1.1.0.
- Android release-lint: geen errors. Niet-blokkerende meldingen over nieuwere versies, Kotlin-stijl en Nederlandse teksten die niet als vertaalresources zijn opgeslagen.
- 9 Android-unit-tests geslaagd: HTTPS, weigeren credentials/query/http, QR-decodering, ongeldig token, kalenderbereik en zomertijd.
- APK ondertekend met een eigen RSA-4096-releasecertificaat en geverifieerd met Android apksigner (v2).
- APK-manifest gecontroleerd: pakket `nl.ritme.health`, versiecode 1, versie 1.0.0, min SDK 28, target 35, correcte Health Connect-leesrechten, geen Health Connect-schrijfrechten. Niet-debugbare release.
- Alle 32 PHP-bestanden gecontroleerd met PHP 8.4.1: geen syntaxfouten.
- 26 bestaande PHP-logische tests en 16 mobiele validatietests geslaagd.
- 9 lokale HTTP-controles geslaagd: JSON-responses, geen cookies op de mobiele API, weigeren ontbrekende/verkeerde toegang, afgeschermde telefoonpagina en private bestanden, lokale QR-bibliotheek.
- Eigen JavaScript-bestanden gecontroleerd op syntaxfouten.

APK SHA-256: `f2a94ae5e1663afc41700f052d4ecc6b2ded407393bfdefdff5dc20a9ab9a553`
Certificaat SHA-256: `6d0a8f5332c09805fdca8addd736727a7a5c702ddc0aa300310adfa4d6eb8768`

## Niet uitgevoerd / beperkingen

De APK is niet geïnstalleerd op een fysieke Android-telefoon of emulator. De echte Health Connect-permissiedialoog, apparaat-/bronafhankelijke aggregatie, camera en WorkManager-uitvoering zijn daarom nog niet op een toestel geverifieerd. Hiervoor is de installatiecheck op je eigen telefoon nodig.

Er is geen productiehosting of telefoon gekoppeld. Een tijdelijke MySQL 8.4-server is geprobeerd, maar zowel 8.4.0 als 8.4.6 crashten tijdens initialisatie in deze Mac-werkomgeving. Daardoor zijn de SQL-migratie, succesvolle pairing, echte opslag en intrekking niet end-to-end uitgevoerd. De unit- en HTTP-tests gebruiken uitsluitend fictieve input; ze vervangen deze integratietest niet.

Voor hostingvalidatie staan `tests/integration.php` en `tests/mobile_integration.php` klaar. Gebruik uitsluitend een lege, wegwerpbare database met een naam eindigend op `_test`. Deze tests schrijven en verwijderen fixturedata. Controleer daarna met je echte telefoon de volledige stappen uit `ANDROID-INSTALLATIE.md`.

De app vraagt niet om historische data buiten 28 dagen. Reeds geïmporteerde oudere gegevens blijven staan; wijzigingen/deleties buiten het herlezen bereik worden niet ontdekt. Backgroundsync is best effort volgens Android, geen gegarandeerde planning.
