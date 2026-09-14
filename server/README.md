# Ritme — persoonlijke fitness-check-in, versie 1.1

Een frameworkvrije PHP-app voor één eigenaar en meerdere alleen-lezen coachlinks. Dagelijkse invoer, weekchecks, twaalf weken grafieken, historische ruwe data en automatische Hevy-sync. Alle assets, inclusief Chart.js 4.5.1, zijn lokaal meegeleverd; bezoekers laden geen scripts, fonts of gezondheidsgegevens via derde partijen.

## Android / Health Connect

Zie `ANDROID-INSTALLATIE.md` voor de APK-koppeling. Bij een bestaande installatie: maak een databasebackup, vervang de applicatiebestanden met behoud van `config/config.php`, en importeer de nog niet uitgevoerde migraties op volgorde. Versie 1.3 vereist `sql/migrations/007_extended_health_connect.sql`. Bij een nieuwe installatie bevat `sql/install.sql` alles.

## Benodigd

- PHP 8.0+ met PDO MySQL, cURL, JSON, session en OpenSSL; gebruik op hosting een nog ondersteunde PHP 8-versie.
- MySQL 8+ of MariaDB 10.6+, InnoDB en utf8mb4.
- HTTPS, eigen database, cron via PHP CLI. Geen Composer of Node nodig op de server.
- Voor automatische synchronisatie: Hevy Pro en je persoonlijke API-key. Zonder Hevy blijft de handmatige app bruikbaar.

## Installatie

1. Maak in het hostingpaneel een database en een eigen databasegebruiker. Geef deze gebruiker toegang tot alleen deze database.
2. Selecteer die database in phpMyAdmin en importeer `sql/install.sql`, of gebruik:

   ```sh
   mysql -u fitness -p fitness < sql/install.sql
   ```

3. Kopieer `config/config.example.php` naar `config/config.php`. Vul databasegegevens, `ADMIN_USERNAME` en de definitieve `APP_URL` in, bijvoorbeeld `https://fitness.jouwdomein.nl`. Geen afsluitende slash nodig. `TIMEZONE` staat op `Europe/Amsterdam`.
4. Genereer een wachtwoordhash met `bin/password_hash.php`. Onderstaande Bash/Zsh-opdracht vraagt het wachtwoord onzichtbaar en zet het niet in de commandogeschiedenis. Gebruik een uniek wachtwoord van 12–72 bytes.

   ```sh
   read -s fitness_password
   printf '%s' "$fitness_password" | php bin/password_hash.php
   unset fitness_password
   ```

   Plak de uitgegeven hash tussen enkele aanhalingstekens bij `ADMIN_PASSWORD_HASH`. De app heeft geen standaardwachtwoord. Om het wachtwoord te resetten, genereer je een nieuwe hash en wijzig je deze configuratie.
5. Heb je geen SSH? Upload tijdelijk `public/password-tool.php`, open `/password-tool.php` via HTTPS, voer het wachtwoord tweemaal in en kopieer de hash. Plaats de hash in de private configuratie en verwijder die publieke hulppagina direct. De pagina wijzigt geen bestanden en bewaart het wachtwoord niet.
7. Upload de volledige projectmap naar een privélocatie op je hosting en stel de **documentroot in op `fitness-checkin/public`**. Alleen `public/` mag via HTTP bereikbaar zijn. `config/`, `includes/`, `cron/`, `logs/`, `sql/`, `tests/` en `bin/` horen buiten de documentroot. Upload nooit de hele projectmap onbeveiligd als publieke root. Kan je hosting de documentroot niet instellen, laat de beheerder de publieke en private mappen correct scheiden voordat je gezondheidsgegevens invoert.
6. Laat HTTPS verplicht (`REQUIRE_HTTPS => true`). Bij TLS-terminatie door een reverse proxy moet de vertrouwde webserver PHP's `HTTPS` correct instellen. De app vertrouwt geen willekeurige `X-Forwarded-*`-headers. Het voorbeeld bevat Apache-regels; op Nginx moet de beheerder documentroot, PHP-handler en verbod op directory listing instellen. Een ontbrekende PHP-handler mag nooit broncode als tekst serveren.
8. Maak `logs/` schrijfbaar voor alleen de cron-gebruiker (bijvoorbeeld mode 700). Configuratie bij voorkeur mode 600, leesbaar voor de PHP-gebruiker. Geen 777-permissies. PHP's sessiemap moet voor de webserver schrijfbaar zijn.
9. Open `/login.php`, log in en sla een dag en weekcheck op. Ga naar **Delen**, maak een link per ontvanger en kopieer hem direct. Alleen een SHA-256-hash wordt opgeslagen; de originele link wordt eenmaal getoond. Je deelt de link zelf met je coach/diëtist. Intrekken blokkeert ook bestaande coachsessies.
10. Stel de Hevy-key in via `HEVY_API_KEY` in de private configuratie of een omgevingsvariabele. Haal hem uit [Hevy-instellingen](https://hevy.com/settings?developer). Controleer de eerste sync handmatig:

   ```sh
   php /volledig/pad/fitness-checkin/cron/hevy_sync.php
   ```

11. Voeg een cronjob toe (pas PHP-pad en projectpad aan):

    ```cron
    0 2 * * * /usr/bin/php /volledig/pad/fitness-checkin/cron/hevy_sync.php
    ```

    De cron gebruikt de tijdzone van je hosting voor de starttijd. De app zet trainingsdatums om naar de ingestelde tijdzone. Resultaten staan in `logs/hevy.log` en het dashboard toont de laatste succesvolle synchronisatie in UTC. Exitcode 1 betekent een fout. De log bevat alleen tijd, status, aantallen en foutklasse, geen key, medische waarden of API-responses. Stel logrotatie in op de hosting.

## Voedingsdagboek

Voor een bestaande installatie voer je `sql/migrations/004_nutrition.sql` uit als het coachrecht **Voeding** nog niet bestaat. Voer daarna `sql/migrations/005_manual_nutrition.sql` uit. De voedingspagina bewaart per datum calorieën, eiwit, koolhydraten en vet in de eigen database. Lege waarden blijven `NULL` en tellen niet mee in weekgemiddelden. Als alle vier velden leeg worden opgeslagen, wordt de dagregistratie verwijderd.

Het voedingsoverzicht bevat de gekozen dag, weektabel, weekgemiddelden en maandgrafiek. Nieuwe coachlinks kunnen afzonderlijk alleen-lezen toegang tot Voeding krijgen. Er is geen externe voedings-API of extra configuratiesleutel nodig.

Alle configuratiesleutels kunnen door gelijknamige omgevingsvariabelen worden overschreven. Voor een uitsluitend lokale test kan `REQUIRE_HTTPS=false` en `APP_URL=http://127.0.0.1:8080` worden gebruikt:

```sh
REQUIRE_HTTPS=false APP_URL=http://127.0.0.1:8080 php -S 127.0.0.1:8080 -t public
```

Er is nog steeds een geconfigureerde MySQL/MariaDB-database nodig om in te loggen en gegevens op te slaan.

## Overgenomen uit het Excel-bestand

Bron: `Exsel Rvdh 2026 New.xlsx`, tabblad `Blad1`; koppen B3:J4, uitleg M1:M23 en weekvragen B15:B20. Het bronbestand is uitsluitend gelezen en niet gewijzigd of naar de applicatie gekopieerd.

| Excel | Applicatie |
|---|---|
| Gewicht | Automatisch uit Health Connect; weekgemiddelde en verschil met vorige week |
| Rode gewichtsmarkering: maaltijd niet zelfgemaakt (M1) | Apart veld ‘Een maaltijd niet zelfgemaakt?’; onbekend / nee / ja |
| Stappen | Automatisch uit Health Connect; weektotaal en gemiddelde per meetdag |
| Navelhoogte / heuphoogte | Omtrek in cm, optioneel dagelijks; eenmaal per week invullen is voldoende |
| Gesport | Vrije sportnotitie naast automatisch geïmporteerde Hevy-trainingen |
| Slaap: aantal uren op bed (M6) | Automatische Health Connect-slaapduur per kalenderdag |
| Slaapkwaliteit | 1 = slecht tot 5 = uitgerust |
| Rusthartslag / bloeddruk | Rusthartslag automatisch uit Health Connect; bloeddruk handmatig met boven- én onderdruk |
| Libido | 1–5 schaal uit de gespreksspecificatie plus toelichting, bijvoorbeeld aantal ochtenderecties |
| Ontlasting | Vrije tekst voor frequentie, opgeblazen buik en gasvorming |
| Cravings (M17) | **1 = veel zoete trek, 5 = geen zoete trek** |
| Stress | 1 = laag, 5 = hoog plus oorspronkelijke vraag over uitgerust wakker worden en zin in de dag |
| Krachtsport | Vrije tekst over kracht/progressive overload, plus automatische vergelijking |
| Opmerking | Weekervaring, vragen en advieswensen |

De voorbeeldinstructie van 70.000 stappen en andere coachaanwijzingen uit het Excel worden niet als medisch advies of verplichte norm opgelegd. Dit is een registratie-app, geen beoordelings- of behandelsysteem. Versie 1 vraagt nog niet om calorieën of macro's; die stonden niet in het Excel. Er is geen historische Excel-import uitgevoerd.

## Cijfers en grafieken

- Maandag is de start van de week. De lopende week loopt tot vandaag. Vergelijking van het gewicht is met de vorige kalenderweek; beide aantallen meetdagen staan erbij.
- Leeg is `NULL`, niet nul. Gemiddelden en totalen tellen alleen ingevulde waarden mee. Een expliciete nul stappen telt wel mee.
- Health Connect levert daarnaast lichaamsvet en totaal verbrande calorieën. Voor gewicht en lichaamsvet geldt de laatste dagmeting; rusthartslag is het daggemiddelde; totaal verbrand omvat de dagsom van actief en basaal energieverbruik.
- Het gewichtsgemiddelde beslaat zeven **kalenderdagen**, inclusief de dag zelf, niet de laatste zeven metingen. De grafiek haalt zes extra dagen op voor het eerste punt.
- Grafieken tonen twaalf kalenderweken inclusief de lopende week. Stappen zijn weektotalen; tijd in bed is een weekgemiddelde in uren. Eerste/laatste weken kunnen onvolledig zijn.
- Trainingsaantal omvat alle geïmporteerde Hevy-sessies, dus ook een cardiosessie. Handmatige sportnotities worden niet opgeteld om dubbel tellen te voorkomen.
- Volume = som van niet-negatief gewicht × reps van sets, exclusief warming-up. Cardio en ontbrekend gewicht zijn geen nulvolume; externe belasting bij lichaamsgewicht en negatieve assistentie zijn niet volledig vergelijkbaar. Originele gegevens blijven in `raw_json` bewaard.
- Progressive overload vergelijkt de zwaarste niet-warming-up-set per oefening in de twee laatste sessies binnen twaalf weken; bij gelijk gewicht de meeste reps. Meer gewicht met minstens evenveel reps, of meer reps met minstens hetzelfde gewicht, is progressie. Gemengde veranderingen heten ‘andere belasting’. Dit is geen schatting van 1RM of medische analyse.
- **Ruwe data** toont alle historie via een instelbare periode van maximaal 366 dagen per aanvraag, inclusief alle weekvragen en sets. Datumbereik kan onbeperkt terug worden verplaatst.

## Hevy en uitbreidbaarheid

Gebaseerd op de [officiële Hevy OpenAPI-documentatie](https://api.hevyapp.com/docs/), gecontroleerd op 3 september 2026. Authenticatie: `api-key`-header; basis-URL `https://api.hevyapp.com/v1/`.

Eerste import leest alle pagina's van `GET /workouts` (`pageSize=10`). Daarna leest `GET /workouts/events?since=…` wijzigingen en verwijderingen. Een overlap van vijf minuten en een uniek `(source, external_id)` maken herhalingen veilig. Nieuwste event per workout wint; verwijderingen verwijderen ook sets. De sync verzamelt alle pagina's voordat een database-transactie start. De voortgang wordt alleen samen met de gegevens vastgelegd. Een database-lock voorkomt gelijktijdige imports. Bij 429, netwerkfouten en 5xx zijn er maximaal vier pogingen met korte wachttijd; 401/403 worden niet eindeloos herhaald.

De API gebruikt paginanummers en levert geen consistente snapshot over alle pagina's. Wijzigingen tijdens een eerste volledige import worden via de volgende event-sync met overlap opnieuw gelezen. Voor zeer grote accounts staan fetches tijdelijk in geheugen en geldt een veiligheidsgrens van 10.000 pagina's.

`WorkoutProvider` maakt providers uitbreidbaar. `normalize_workout()` zet Hevy-data om; `save_workout()` en `sync_provider()` verzorgen opslag. Toekomstige MyFitnessPal/Health Connect-data horen in aparte providers/tabellen met bron, externe identifier, tijdzone, gemeten tijd en expliciete conflictregels voor handmatige invoer. Er zijn nu geen fictieve koppelingen of API-keys voor deze diensten. Voeg nieuwe databasevelden toe via aparte SQL-migraties; `install.sql` is voor een nieuwe installatie, niet voor upgrades.

## Beveiliging en beheer

Prepared PDO-statements, servervalidatie, output escaping, CSRF op alle POST-acties inclusief uitloggen en tokenbeheer, sessie-ID-vernieuwing, HttpOnly/SameSite-cookies, HTTPS, geen caching, CSP zonder externe scripts en geen directory listing. Inloggen is begrensd op tien pogingen per IP per kwartier, atomair opgeslagen in de database. Sessies verlopen na 30 minuten inactiviteit. Coachautorisatie wordt op iedere aanvraag opnieuw tegen de actieve tokenstatus gecontroleerd.

Een coachlink is een toegangssleutel tot alle opgeslagen gezondheidsdata. Hij wordt na openen ingeruild voor een sessie en uit de adresbalk verwijderd. De eerste URL kan wel in webserver-/proxylogs en browsergeschiedenis staan: configureer toegangslogs zonder querystrings, beperk logtoegang en trek een uitgelekte link in. Gebruik geen analytics of derde-partij scripts op deze pagina's. Er is geen aparte versleuteling van databasevelden; gebruik versleutelde hostopslag en backups, beperkt databasebeheer en een passend bewaarbeleid. PHP- en webserverfoutmeldingen mogen geen stacktraces publiek tonen.

Maak vóór updates een backup van database en private configuratie. Herstel in een afgeschermde omgeving en controleer eerst login en coachlinks. De app is voor één eigenaar; accounts voor meerdere cliënten vergen een expliciete gebruikersscheiding in alle tabellen en queries.

## Controle

Zonder database:

```sh
find . -name '*.php' -exec php -l {} \;
php tests/run.php
```

Databasecontrole, **alleen met een aparte lege testdatabase waarvan de naam op `_test` eindigt**:

```sh
DB_NAME=fitness_test php tests/integration.php
```

De integratietest maakt tabellen aan en schrijft/verwijdert vaste testrecords. Gebruik nooit een database met echte data. Hij controleert herhaald opslaan, workout-updates, sets, transacties, verwijdering en tokenintrekking.

Acceptatie op de hosting:

1. Zonder login moeten invoerpagina's naar login gaan; `/coach.php` en `/raw.php` zonder toegang mogen niets tonen.
2. Sla dezelfde datum/week tweemaal op; er blijft één record met de laatste waarden.
3. Probeer ongeldige datum, schaal buiten 1–5 en POST zonder CSRF; deze worden geweigerd.
4. Maak een coachlink, open deze in een privévenster, probeer een invoerpagina en trek de link in. Verversen van het coachoverzicht moet daarna toegang weigeren.
5. Voer de Hevy-sync tweemaal uit. Controleer dat workout- en setaantallen niet verdubbelen. Wijzig of verwijder een testworkout in Hevy en controleer de volgende sync.
6. Controleer op telefoon daginvoer, historische datum, weekcheck, grafieken en horizontaal scrollende datatabellen.
7. Controleer dat configuratie, logs, SQL en cron niet via HTTP toegankelijk zijn.

Zie `VALIDATION.md` voor de werkelijk uitgevoerde controles en resterende installatiechecks.

## Bestanden

```text
public/       PHP-pagina's, POST-endpoints, lokale CSS/JS
includes/     authenticatie, validatie, statistiek, providers en opslag
config/       voorbeeld + eigen private configuratie
sql/          installatie van MySQL/MariaDB-tabellen
cron/         uitsluitend CLI Hevy-sync
bin/          wachtwoordhash-helper
logs/         private synchronisatielogs
 tests/       logica- en database-integratietests
```
