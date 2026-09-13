package nl.ritme.health

import android.app.AlertDialog
import android.content.Intent
import android.content.ActivityNotFoundException
import android.graphics.Color
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.net.Uri
import android.os.Bundle
import android.text.InputType
import android.view.View
import android.view.WindowManager
import android.widget.*
import androidx.activity.ComponentActivity
import androidx.health.connect.client.HealthConnectClient
import androidx.health.connect.client.PermissionController
import androidx.lifecycle.lifecycleScope
import com.journeyapps.barcodescanner.ScanContract
import com.journeyapps.barcodescanner.ScanOptions
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.launch
import kotlinx.coroutines.sync.withLock
import org.json.JSONObject

class MainActivity : ComponentActivity() {
    private val ink = Color.rgb(35,60,50)
    private val green = Color.rgb(36,105,79)
    private val muted = Color.rgb(97,115,104)
    private lateinit var content: LinearLayout
    private lateinit var status: TextView
    private lateinit var pairingText: EditText
    private var message: String? = null
    private var busy = false
    private var requestBackground = false
    private var pendingPair: String? = null
    private val store by lazy { SecureStore(this) }
    private val permissions = registerForActivityResult(PermissionController.createRequestPermissionResultContract()) { granted ->
        if(requestBackground) {
            val enabled = HealthReader.background in granted
            store.background = enabled
            SyncEngine.schedule(this,enabled)
            message = if(enabled) "Automatisch bijwerken staat aan." else "Geen achtergrondtoegang. Handmatig bijwerken blijft mogelijk."
            requestBackground=false
        } else message = "Toestemmingen bijgewerkt. Je kunt nu synchroniseren."
        render()
    }
    private val scan = registerForActivityResult(ScanContract()) { result ->
        result.contents?.let { pendingPair=it; render(); confirmPair(it) }
    }
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        window.setFlags(WindowManager.LayoutParams.FLAG_SECURE,WindowManager.LayoutParams.FLAG_SECURE)
        pendingPair=savedInstanceState?.getString("pair") ?: intent.data?.toString()
        render()
    }
    override fun onSaveInstanceState(outState: Bundle) {
        // Do not persist a one-time pairing secret in saved instance state.
        super.onSaveInstanceState(outState)
    }
    override fun onResume() {super.onResume();if(::content.isInitialized && !busy) render()}
    private fun dp(v:Int)=(v*resources.displayMetrics.density).toInt()
    private fun shape(color:Int,radius:Int=18)=GradientDrawable().apply{setColor(color);cornerRadius=dp(radius).toFloat()}
    private fun label(parent:LinearLayout,text:String,size:Float=16f,color:Int=ink,bold:Boolean=false):TextView {
        return TextView(this).apply {
            this.text=text;textSize=size;setTextColor(color);if(bold)setTypeface(typeface,Typeface.BOLD)
            setPadding(0,dp(5),0,dp(9));parent.addView(this)
        }
    }
    private fun card(color:Int=Color.WHITE):LinearLayout = LinearLayout(this).apply {
        orientation=LinearLayout.VERTICAL;background=shape(color);setPadding(dp(22),dp(18),dp(22),dp(18))
        content.addView(this,LinearLayout.LayoutParams(-1,-2).apply{bottomMargin=dp(18)})
    }
    private fun button(parent:LinearLayout,text:String,secondary:Boolean=false,action:()->Unit):Button = Button(this).apply {
        this.text=text;isAllCaps=false;textSize=16f;minHeight=dp(50);setPadding(dp(12),dp(8),dp(12),dp(8))
        background=shape(if(secondary)Color.rgb(232,239,226)else green,12);setTextColor(if(secondary)ink else Color.WHITE)
        isEnabled=!busy;alpha=if(busy)0.5f else 1f
        parent.addView(this,LinearLayout.LayoutParams(-1,-2).apply{topMargin=dp(10)})
        setOnClickListener{action()}
    }
    private fun render() {
        val scroll=ScrollView(this).apply {isFillViewport=true;setBackgroundColor(Color.rgb(245,246,240))}
        content=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;setPadding(dp(22),dp(24),dp(22),dp(28))}
        scroll.addView(content)
        scroll.setOnApplyWindowInsetsListener {v,i->v.setPadding(i.systemWindowInsetLeft,i.systemWindowInsetTop,i.systemWindowInsetRight,i.systemWindowInsetBottom);i}
        setContentView(scroll)
        label(content,"r.  ritme",32f,green,true)
        label(content,"JOUW HEALTH CONNECT-KOPPELING",11f,muted,true)
        label(content,"Minder invullen.\nMeer overzicht.",30f,ink,true)
        label(content,"Stappen, gewicht en slaap veilig naar je eigen fitnessoverzicht.",16f,muted)
        val connection=store.connection()
        val state=card(Color.rgb(222,234,205))
        label(state,if(connection==null)"Nog niet gekoppeld" else "Verbonden met je website",19f,ink,true)
        connection?.let {label(state,it.server,14f,green)}
        status=label(state,message ?: store.status,15f,muted)
        if(busy) state.addView(ProgressBar(this).apply{isIndeterminate=true},LinearLayout.LayoutParams(dp(34),dp(34)))
        if(connection==null) {
            val connect=card()
            label(connect,"1  Koppel je website",20f,ink,true)
            label(connect,"Log in op je Ritme-website. Kies Telefoon → Koppellink maken. Scan die QR-code of plak de koppellink hieronder.",15f,muted)
            button(connect,"QR-code scannen") {
                if(packageManager.hasSystemFeature("android.hardware.camera.any")) scan.launch(ScanOptions().setDesiredBarcodeFormats(ScanOptions.QR_CODE).setPrompt("Scan de koppellink uit Ritme → Telefoon").setBeepEnabled(false).setOrientationLocked(false))
                else {message="Geen camera gevonden. Plak de koppellink hieronder.";render()}
            }
            pairingText=EditText(this).apply {
                hint="ritme://pair?…";setText(pendingPair.orEmpty());setTextColor(ink);textSize=14f
                inputType=InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_FLAG_NO_SUGGESTIONS
                maxLines=4;minHeight=dp(56);importantForAutofill=View.IMPORTANT_FOR_AUTOFILL_NO
                connect.addView(this,LinearLayout.LayoutParams(-1,-2).apply{topMargin=dp(12)})
            }
            button(connect,"Website koppelen",true){pendingPair=pairingText.text.toString();confirmPair(pendingPair.orEmpty())}
        }
        val health=card()
        label(health,"${if(connection==null)"2" else "1"}  Kies je gegevens",20f,ink,true)
        label(health,"Je kiest in Health Connect zelf welke gegevens Ritme mag lezen. Alleen dagwaarden worden doorgestuurd.",15f,muted)
        val availability=HealthConnectClient.getSdkStatus(this)
        if(availability==HealthConnectClient.SDK_AVAILABLE) {
            val grantedLabel=label(health,"Toestemmingen controleren…",14f,green)
            lifecycleScope.launch {
                try {
                    val granted=HealthReader(this@MainActivity).client.permissionController.getGrantedPermissions()
                    grantedLabel.text=listOf(HealthReader.steps to "Stappen",HealthReader.weight to "Gewicht",HealthReader.sleep to "Slaap").joinToString("  ·  "){(p,n)->"$n ${if(p in granted)"✓" else "—"}"}
                }catch(_:Exception){grantedLabel.text="Open Health Connect om je toestemming te controleren."}
            }
            button(health,"Toestemming kiezen",true){requestBackground=false;permissions.launch(HealthReader.permissions)}
            button(health,"Health Connect openen",true){openHealth()}
        } else {
            label(health,if(availability==HealthConnectClient.SDK_UNAVAILABLE)"Health Connect is niet beschikbaar op dit toestel of profiel." else "Installeer of werk Health Connect bij om verder te gaan.",15f,muted)
            button(health,"Health Connect installeren",true){openLink("https://play.google.com/store/apps/details?id=com.google.android.apps.healthdata")}
        }
        if(connection!=null) {
            val sync=card()
            label(sync,"2  Alles bijwerken",20f,ink,true)
            label(sync,"Controleert de laatste 28 dagen opnieuw, inclusief wijzigingen. Handmatige invoer blijft behouden. Je website gebruikt ${connection.timezone} voor de dagindeling.",15f,muted)
            button(sync,"Nu synchroniseren") {runTask {SyncEngine.sync(this@MainActivity,false){status.text=it}}}
            val toggle=Switch(this).apply {
                text="Automatisch, ongeveer elke 6 uur";textSize=15f;setTextColor(ink);minHeight=dp(60);isChecked=store.background;isEnabled=!busy
                sync.addView(this,LinearLayout.LayoutParams(-1,-2).apply{topMargin=dp(12)})
            }
            toggle.setOnCheckedChangeListener{_,enabled->
                if(!enabled) {store.background=false;SyncEngine.schedule(this,false)}
                else if(availability!=HealthConnectClient.SDK_AVAILABLE || !HealthReader(this).backgroundAvailable()) {
                    toggle.isChecked=false;message="Achtergrondtoegang is op dit toestel niet beschikbaar. Gebruik Nu synchroniseren.";render()
                } else {requestBackground=true;permissions.launch(setOf(HealthReader.background))}
            }
            label(sync,"Android bepaalt het precieze moment. Zonder achtergrondtoestemming werkt de knop hierboven als de app open is.",13f,muted)
            button(sync,"Mijn fitnessoverzicht openen",true){openLink(connection.server)}
            button(sync,"Voeding registreren",true){startActivity(Intent(this,NutritionActivity::class.java))}
            button(sync,"Telefoon ontkoppelen",true){
                AlertDialog.Builder(this).setTitle("Telefoon ontkoppelen?").setMessage("Synchronisatie stopt. Eerder verstuurde gegevens blijven op je website staan.")
                    .setNegativeButton("Annuleren",null).setPositiveButton("Ontkoppelen"){_,_->
                        runTask {
                            SyncEngine.mutex.withLock {
                                SyncEngine.schedule(this@MainActivity,false)
                                val revoked=try{ServerApi.post(connection.server,"revoke",JSONObject(),connection.token);true}catch(_:Exception){false}
                                store.clear();pendingPair=null
                                if(revoked)"Telefoon ontkoppeld." else "Lokaal ontkoppeld. Trek de telefoon ook op je website in: de server was niet bereikbaar."
                            }
                        }
                    }.show()
            }
        }
        button(content,"Privacy & gegevensgebruik",true){startActivity(Intent(this,PrivacyActivity::class.java))}
        label(content,"Ritme 1.0 · jouw gegevens, jouw website",12f,muted)
    }
    private fun confirmPair(value:String) {
        if(store.connection()!=null){message="Ontkoppel eerst je huidige website.";render();return}
        val pair=try{Protocol.pairing(value)}catch(_:Exception){message="Ongeldige koppellink. Maak een nieuwe link op je website onder Telefoon.";render();return}
        AlertDialog.Builder(this).setTitle("Is dit jouw website?")
            .setMessage("${pair.first}\n\nJe gekozen Health Connect-gegevens worden bij synchronisatie naar dit adres gestuurd en zijn daar zichtbaar voor je coaches. Controleer het adres.")
            .setNegativeButton("Annuleren",null).setPositiveButton("Koppelen"){_,_->
                runTask {
                    val response=ServerApi.post(pair.first,"pair",JSONObject().put("code",pair.second).put("name","Android-telefoon"))
                    val token=response.getString("token");require(token.matches(Regex("[a-f0-9]{64}")))
                    val timezone=response.getString("timezone");Protocol.zone(timezone)
                    store.save(Connection(pair.first,token,timezone));pendingPair=null
                    "Gekoppeld. Kies toestemming en tik op Nu synchroniseren."
                }
            }.show()
    }
    private fun runTask(action:suspend()->String) {
        if(busy)return
        busy=true;message="Even bezig…";render()
        lifecycleScope.launch {
            try {message=action()}
            catch(e:CancellationException){throw e}
            catch(e:Exception) {
                message=when(e) {
                    is ApiException -> e.message
                    is SecurityException -> "Health Connect-toestemming ontbreekt of is ingetrokken. Kies je toestemming opnieuw."
                    is java.io.IOException -> "Verbinding niet gelukt. Controleer internet en het HTTPS-adres van je website."
                    else -> "Niet gelukt. Controleer Health Connect, je toestemming en de installatie van de website. Probeer opnieuw."
                }
            } finally {busy=false;render()}
        }
    }
    private fun openHealth() {
        try {startActivity(Intent(HealthConnectClient.ACTION_HEALTH_CONNECT_SETTINGS))}
        catch(_:ActivityNotFoundException){openLink("https://play.google.com/store/apps/details?id=com.google.android.apps.healthdata")}
    }
    private fun openLink(link:String) {
        try{startActivity(Intent(Intent.ACTION_VIEW,Uri.parse(link)))}catch(_:ActivityNotFoundException){message="Geen browser beschikbaar.";render()}
    }
}
