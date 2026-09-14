# Ritme Android installeren en koppelen

## 1. Website bijwerken

De Android-app heeft de nieuwe ontvangstpunten op je eigen website nodig. Deze staan in deze webupdate. Een oude v1-website zonder deze update kan de telefoon nog niet koppelen.

1. Maak een backup van je database en `config/config.php`.
2. Upload de bijgewerkte applicatiebestanden. **Behoud je eigen `config/config.php` en logs.** Alleen `public/` hoort publiek bereikbaar te zijn.
3. Bestaande installatie: importeer de nog niet uitgevoerde migraties op volgorde. Voor deze versie is `sql/migrations/007_extended_health_connect.sql` vereist. Nieuwe installatie: importeer het volledige `sql/install.sql`.
4. Controleer dat `APP_URL` exact het HTTPS-adres van de website bevat, inclusief een eventuele submap. Verwijzingen naar een ander adres worden door de app geweigerd. Stel `TIMEZONE` juist in (standaard Europe/Amsterdam).
5. Bij PHP-FPM moet de webserver de `Authorization`-header doorgeven. Apache: indien nodig `CGIPassAuth On` in de serverconfiguratie. Nginx: `fastcgi_param HTTP_AUTHORIZATION $http_authorization;`. Gebruik een publiek vertrouwd TLS-certificaat.

## 2. APK installeren

1. Open `Ritme-1.3.0.apk` op je Android-telefoon.
2. Android kan vragen of je deze browser/bestandsapp toestemming geeft om onbekende apps te installeren. Geef toestemming en installeer Ritme. Je kunt die installatietoestemming daarna weer uitzetten.
3. Vereist: Android 9 of hoger met Health Connect. Op Android 14+ is Health Connect onderdeel van het systeem; op oudere ondersteunde toestellen kan installatie uit Google Play nodig zijn. Beschikbaarheid hangt ook af van toestel/profiel en systeemupdates.
4. Zorg dat je bronapps hun gegevens daadwerkelijk naar Health Connect schrijven; Ritme kan alleen lezen wat daar beschikbaar is.

Deze APK is lokaal ondertekend als persoonlijke release (`nl.ritme.health`, versiecode 1). Hij staat niet in Google Play. Er is geen centrale Ritme-server en er zit geen account, wachtwoord of serveradres in de APK.

## 3. Koppelen

1. Log in op je website en open **Telefoon**.
2. Kies **Koppellink maken**. De QR/link verloopt na tien minuten en werkt één keer.
3. Open Ritme op de telefoon en scan de QR-code. Op dezelfde telefoon kun je ook de koppellink kopiëren en in de app plakken.
4. Controleer het websiteadres dat de app toont en bevestig.
5. Kies **Toestemming kiezen** en geef toestemming voor de gewenste gegevenstypen: stappen, gewicht, lichaamsvet, slaap, rusthartslag en totaal verbrande calorieën.
6. Tik op **Nu synchroniseren**. Controleer het coachoverzicht en de ruwe data op de website.

Er is één actieve telefoon tegelijk. Een nieuwe koppeling trekt de oude telefoon automatisch in. Een mislukte koppeling nadat de code al is gebruikt vraagt om een nieuwe QR-code; de app probeert een eenmalige code niet stilzwijgend opnieuw te gebruiken.

## 4. Automatisch bijwerken

Automatisch bijwerken staat standaard uit. Zet de schakelaar aan om aanvullend toestemming voor lezen op de achtergrond te vragen, als jouw Health Connect-versie deze mogelijkheid ondersteunt. Daarna plant WorkManager ongeveer elke zes uur een controle bij internetverbinding. Android/batterijbesparing bepaalt het exacte tijdstip. Er is geen garantie op een bepaald tijdstip. Handmatige synchronisatie blijft beschikbaar als de app open is.

## Welke gegevens?

- Laatste 28 kalenderdagen in de **tijdzone van de website**, opnieuw gelezen bij iedere sync.
- Stappen via de officiële aggregatie, om verschillende bronnen niet zelf dubbel op te tellen.
- Gewicht: laatste meting per kalenderdag.
- Slaap: geaggregeerde duur van Health Connect-slaapsessies binnen iedere kalenderdag, zonder losse stadia. Een nacht kan over twee dagen verdeeld zijn. Dit is **slaapregistratie**, geen nieuwe definitie van de handmatige tijd in bed uit het Excel.
- Rusthartslag: de gemiddelde rusthartslagmeting per kalenderdag.
- Totaal verbrande calorieën: de Health Connect-dagsom van actief en basaal energieverbruik.
- Lichaamsvet: de laatste meting per kalenderdag.
- Bronappnamen en controletijd per meetveld. Bij stappen/slaap vermeldt de API de gezamenlijke bronapps van de aggregatie.

De app verstuurt pas nadat de complete periode succesvol is gelezen. De server slaat de hele upload in één transactie op. Dag + meetveld is herhaalbaar: er ontstaan geen dubbele dagrecords. Een expliciete lege waarde wist de oude import voor dat toegestane meetveld binnen de herlezen periode. Niet-toegestane meetvelden worden niet gewijzigd; eerder opgeslagen historie blijft staan. Wijzigingen ouder dan 28 dagen worden niet automatisch opnieuw gelezen.

Health Connect heeft in het overzicht voorrang bij automatisch gesynchroniseerde velden. Oude handmatige waarden blijven in `daily_metrics` bewaard en gelden als terugval als Health Connect voor die dag geen waarde heeft.

## Toegang stoppen / gegevens verwijderen

- App: **Telefoon ontkoppelen** stopt automatische updates, verwijdert de lokale sleutel en probeert de serversleutel in te trekken. Bij een netwerkfout geeft de app aan dat intrekken op de website nog nodig is.
- Website: **Telefoon → Intrekken** blokkeert die telefoon direct.
- Website: **Health Connect-data verwijderen** wist alleen geïmporteerde Health Connect-data en trekt alle telefoontoegang en open koppellinks in. Typ VERWIJDER om dit te bevestigen. Handmatige invoer en Hevy blijven behouden.
- Health Connect: je kunt leesrechten altijd weer intrekken. Rechten intrekken wist niet automatisch eerder naar je eigen website verstuurde gegevens.

De app bewaart geen lokale kopie van gezondheidsmetingen. De toegangssleutel is met Android Keystore versleuteld; cloudbackup en toesteloverdracht zijn uitgesloten. De website slaat alleen hashes van codes/tokens op. Voor het ingebouwde overzicht wordt de sleutel via HTTPS ingeruild voor een tijdelijke, alleen-lezen sessie zonder toegang tot invoer of beheer.

## Fouten

- **HTTP 404 / website niet beschikbaar:** webupdate/migratie en publiek pad controleren.
- **401/403:** sleutel ingetrokken, ongeldige code of Authorization-header ontbreekt. Koppel opnieuw of controleer de serverinstelling.
- **409:** code verlopen/gebruikt, of tijdzone gewijzigd. Maak een nieuwe koppellink.
- **429:** wacht minstens een minuut. De server accepteert maximaal één geslaagde upload per telefoon per 30 seconden.
- **Geen metingen:** controleer eerst de bronapp en Health Connect zelf; een lege meting wordt niet als nul weergegeven.
- **Geen achtergrondoptie:** gebruik handmatige synchronisatie en controleer beschikbare systeemupdates.

## Referenties

Officiële Android-documentatie gebruikt bij implementatie:
- https://developer.android.com/health-and-fitness/health-connect/get-started
- https://developer.android.com/health-and-fitness/health-connect/read-data
- https://developer.android.com/health-and-fitness/health-connect/aggregate-data
- https://developer.android.com/health-and-fitness/health-connect/experiences/sleep

Zie `ANDROID-VALIDATIE.md` voor uitgevoerde controles en beperkingen.
