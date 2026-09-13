# Ritme

Ritme is een zelf te hosten fitness-, trainings- en voedingsdagboek met een PHP/MySQL-webapp en een Android-app voor Health Connect en voedingsregistratie.

## Onderdelen

- `server/`: PHP 8+ webapp, MySQL/MariaDB-schema, coachdashboards, Hevy-sync en mobiele API.
- `android/`: native Kotlin-app voor Health Connect, barcode-scanning en het voedingsdagboek.

## Installatie

Zie [`server/README.md`](server/README.md) voor de webapp en [`android/README.md`](android/README.md) voor de Android-build. Kopieer `server/config/config.example.php` naar `config.php` buiten de publieke webmap en vul lokale geheimen alleen daar in.

## Beveiliging

Commit nooit `config.php`, API-sleutels, databasewachtwoorden of Android-keystores. Meld beveiligingsproblemen privé aan de maintainer in plaats van via een openbaar issue.

## Status

Ritme is in actieve ontwikkeling. Databasewijzigingen staan in `server/sql/migrations/`.
