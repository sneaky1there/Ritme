# Ritme Android 1.1.1

Native Kotlin-app voor een persoonlijke Ritme/PHP-installatie. Leest stappen, gewicht en slaap uit Health Connect en verstuurt dagwaarden via HTTPS. De app bevat daarnaast een eigen voedingsdagboek met barcodescanner, productendatabase en berekening van calorieën en macro's. Minimaal Android 9; compile en target SDK 35. Geen Compose of centrale clouddienst.

## Installeren

Gebruik de meegeleverde ondertekende `Ritme-1.1.1.apk` en het installatiebestand in het serverpakket. Werk de website en database bij voordat je voeding registreert.

## Zelf bouwen

JDK 17, Android SDK platform 36 en build-tools 35, plus internet voor Maven/Gradle. De Gradle-wrapper staat in dit project.

```sh
./gradlew testDebugUnitTest assembleDebug
```

Voor een release moeten onderstaande omgevingsvariabelen ingesteld zijn:

- `RITME_KEYSTORE`: absoluut pad naar je eigen keystore.
- `RITME_STORE_PASSWORD`: wachtwoord van keystore en sleutel.
- Sleutelalias: `ritme`.

```sh
./gradlew assembleRelease lintRelease
```

De lokale release-keystore staat in de private, uitgesloten map `signing/`, met het wachtwoord in `signing/password.txt`. Deze map hoort niet in Git, het broncodearchief of je publieke webhosting. Maak zelf een veilige backup; dezelfde sleutel is nodig om toekomstige APK-updates over deze installatie heen te installeren. Een andere sleutel vereist verwijderen/herinstalleren en opnieuw koppelen.

## Structuur

- `HealthReader`: Health Connect-toestemmingen, aggregatie, gepagineerd gewicht, kalenderdagen/tijdzone.
- `Protocol`: QR-link en HTTPS-validatie, gedeelde periodeberekening.
- `SecureStore`: versleutelde koppelsleutel via Android Keystore.
- `ServerApi`: strikt HTTPS, geen redirects met tokens, begrensde responses en timeouts.
- `SyncEngine`/`SyncWorker`: handmatige/periodieke sync, onderlinge uitsluiting, beperkte retries.
- `MainActivity`: koppeling, toestemming, status, handmatig/automatisch bijwerken, ontkoppeling.
- `PrivacyActivity`: privacyverklaring en verplichte Health Connect-intents voor oude/nieuwe Android-versies.

Geen gezondheidsmetingen worden lokaal opgeslagen. Alleen koppeling, achtergrondvoorkeur en tekstuele status. Geen Health Connect-schrijfrechten of geschiedenis ouder dan 28 dagen aangevraagd. Camera wordt uitsluitend voor QR gebruikt. De app vraagt geen contacten, locatie of notificatierechten.

Voor uitbreiding naar hartslag/voeding zijn aanvullende expliciete Health Connect-toestemmingen, schemawijzigingen, validatie en privacytekst nodig; er zijn geen verborgen datatypes actief.

De app is Nederlands; Android lint meldt onder meer dat tekst niet voor vertaling is geëxternaliseerd en dat nieuwere bibliotheekversies beschikbaar zijn. Dat zijn geen buildfouten. Zie de validatie in het webproject voor de exacte uitgevoerde controles.
