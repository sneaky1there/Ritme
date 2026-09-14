package nl.ritme.health

import android.app.Activity
import android.os.Bundle
import android.widget.ScrollView
import android.widget.TextView

class PrivacyActivity: Activity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val text = TextView(this).apply {
            textSize=17f; setPadding(28,70,28,60)
            text="""Ritme · privacy & gegevens

Ritme leest alleen de gegevens waarvoor jij in Health Connect toestemming geeft: stappen, gewicht, lichaamsvet, slaapregistraties, rusthartslag en totaal verbrande calorieën. Er worden geen gegevens in Health Connect geschreven.

Bij synchronisatie worden de laatste 28 kalenderdagen gelezen. Stappen, slaap, rusthartslag en verbrande calorieën worden via Health Connect per dag geaggregeerd; voor gewicht en lichaamsvet telt de laatste meting per dag. Er worden dagwaarden en bronappnamen verstuurd, geen routes of losse slaapstadia.

Bestemming is uitsluitend de HTTPS-website die je zelf koppelt. Controleer het adres voordat je bevestigt. Die website bewaart de gegevens in jouw database en maakt ze zichtbaar voor de coaches aan wie jij toegang hebt gegeven. Handmatige historie blijft bewaard; beschikbare Health Connect-waarden worden in het overzicht gebruikt.

Mijn overzicht opent binnen de app. De versleutelde koppelsleutel wordt via HTTPS eenmalig ingeruild voor een beperkte, alleen-lezen websitesessie. De sleutel staat niet in een URL en geeft geen toegang tot invoer- of beheerpagina's.

Automatisch synchroniseren staat standaard uit. Je kunt het activeren als je toestel en Health Connect achtergrondtoegang ondersteunen. Android bepaalt het precieze moment. Zonder die toestemming werkt de knop Nu synchroniseren als de app openstaat.

De koppelsleutel wordt versleuteld met Android Keystore. Er zijn geen advertenties, analytics of externe trackingdiensten. De app bewaart lokaal geen kopie van je gezondheidsmetingen. Camera-toegang wordt alleen gebruikt voor het scannen van je koppellink; camerabeelden worden niet verstuurd.

Je kunt toestemming intrekken in Health Connect en synchronisatie stoppen in deze app. Ontkoppelen trekt zo mogelijk ook de serversleutel in. Bestaande gegevens blijven op je website staan. Je kunt de sleutel altijd via Telefoon op je website intrekken en geïmporteerde Health Connect-gegevens daar verwijderen.

Voor vragen of verwijdering gebruik je het beheer van je eigen website. Deze persoonlijke app heeft geen centrale Ritme-cloud of externe gegevensbeheerder.

Versie 1.3.0 · 14 september 2026"""
        }
        val scroll=ScrollView(this).apply{addView(text)}
        scroll.setOnApplyWindowInsetsListener {v,i->v.setPadding(i.systemWindowInsetLeft,i.systemWindowInsetTop,i.systemWindowInsetRight,i.systemWindowInsetBottom);i}
        setContentView(scroll)
    }
}
