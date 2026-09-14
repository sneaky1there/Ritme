# Ritme Android 1.3.0

Native Kotlin-app voor een persoonlijke Ritme/PHP-installatie. Leest stappen, gewicht, lichaamsvet, slaap, rusthartslag en totaal verbrande calorieën uit Health Connect en verstuurt dagwaarden via HTTPS. De app bevat daarnaast een eigen voedingsdagboek en een ingebouwd alleen-lezen overzicht. Minimaal Android 9; compile en target SDK 35.

## Installeren

Gebruik de ondertekende `Ritme-1.3.0.apk`. Upload eerst de serverupdate en voer migratie `007_extended_health_connect.sql` uit.

## Zelf bouwen

JDK 17, Android SDK platform 35 en build-tools 35, plus internet voor Maven/Gradle. De Gradle-wrapper staat in dit project.

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
- `MainActivity`: eenvoudige startpagina met voeding als primaire actie en toegang tot het fitnessoverzicht.
- `OverviewActivity`: ingebouwde WebView die het apparaat-token via HTTPS inruilt voor een beperkte alleen-lezen sessie.
- `SettingsActivity`: Health Connect-toestemming, handmatige en automatische synchronisatie, privacy en ontkoppeling.
- `PrivacyActivity`: privacyverklaring en verplichte Health Connect-intents voor oude/nieuwe Android-versies.

Geen gezondheidsmetingen worden lokaal opgeslagen. Alleen koppeling, achtergrondvoorkeur en tekstuele status. Geen Health Connect-schrijfrechten of geschiedenis ouder dan 28 dagen aangevraagd. Camera wordt uitsluitend voor QR gebruikt. De app vraagt geen contacten, locatie of notificatierechten.

Er zijn geen verborgen Health Connect-datatypes actief; ieder type staat als afzonderlijke leesmachtiging in Android.

De app is Nederlands; Android lint meldt onder meer dat tekst niet voor vertaling is geëxternaliseerd en dat nieuwere bibliotheekversies beschikbaar zijn. Dat zijn geen buildfouten. Zie de validatie in het webproject voor de exacte uitgevoerde controles.
