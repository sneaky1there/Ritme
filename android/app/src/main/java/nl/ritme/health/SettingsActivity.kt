package nl.ritme.health

import android.app.AlertDialog
import android.content.ActivityNotFoundException
import android.content.Intent
import android.graphics.Color
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.net.Uri
import android.os.Bundle
import android.widget.*
import androidx.activity.ComponentActivity
import androidx.health.connect.client.HealthConnectClient
import androidx.health.connect.client.PermissionController
import androidx.lifecycle.lifecycleScope
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.launch
import kotlinx.coroutines.sync.withLock
import org.json.JSONObject

class SettingsActivity : ComponentActivity() {
    private val ink=Color.rgb(35,60,50)
    private val green=Color.rgb(36,105,79)
    private val muted=Color.rgb(97,115,104)
    private val store by lazy{SecureStore(this)}
    private lateinit var content:LinearLayout
    private lateinit var status:TextView
    private var message:String?=null
    private var busy=false
    private var requestBackground=false

    private val permissions=registerForActivityResult(PermissionController.createRequestPermissionResultContract()){granted->
        if(requestBackground){
            val enabled=HealthReader.background in granted
            store.background=enabled
            SyncEngine.schedule(this,enabled)
            message=if(enabled)"Automatische synchronisatie staat aan." else "Achtergrondtoegang is niet gegeven."
            requestBackground=false
        }else message="Health Connect-toestemmingen bijgewerkt."
        render()
    }

    override fun onCreate(savedInstanceState:Bundle?){super.onCreate(savedInstanceState);render()}
    override fun onResume(){super.onResume();if(::content.isInitialized&&!busy)render()}
    private fun dp(v:Int)=(v*resources.displayMetrics.density).toInt()
    private fun shape(color:Int,radius:Int=18)=GradientDrawable().apply{setColor(color);cornerRadius=dp(radius).toFloat()}
    private fun label(parent:LinearLayout,text:String,size:Float=16f,color:Int=ink,bold:Boolean=false)=TextView(this).apply{
        this.text=text;textSize=size;setTextColor(color);if(bold)setTypeface(typeface,Typeface.BOLD)
        setPadding(0,dp(5),0,dp(9));parent.addView(this)
    }
    private fun card():LinearLayout=LinearLayout(this).apply{
        orientation=LinearLayout.VERTICAL;background=shape(Color.WHITE);setPadding(dp(22),dp(18),dp(22),dp(18))
        content.addView(this,LinearLayout.LayoutParams(-1,-2).apply{bottomMargin=dp(18)})
    }
    private fun button(parent:LinearLayout,text:String,secondary:Boolean=false,action:()->Unit)=Button(this).apply{
        this.text=text;isAllCaps=false;textSize=16f;minHeight=dp(50)
        background=shape(if(secondary)Color.rgb(232,239,226)else green,12);setTextColor(if(secondary)ink else Color.WHITE)
        isEnabled=!busy;alpha=if(busy)0.5f else 1f
        parent.addView(this,LinearLayout.LayoutParams(-1,-2).apply{topMargin=dp(10)});setOnClickListener{action()}
    }

    private fun render(){
        val connection=store.connection() ?: run{finish();return}
        val scroll=ScrollView(this).apply{isFillViewport=true;setBackgroundColor(Color.rgb(245,246,240))}
        content=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;setPadding(dp(22),dp(24),dp(22),dp(28))}
        scroll.addView(content)
        scroll.setOnApplyWindowInsetsListener{v,i->v.setPadding(i.systemWindowInsetLeft,i.systemWindowInsetTop,i.systemWindowInsetRight,i.systemWindowInsetBottom);i}
        setContentView(scroll)

        val top=LinearLayout(this).apply{orientation=LinearLayout.HORIZONTAL;gravity=android.view.Gravity.CENTER_VERTICAL;content.addView(this,LinearLayout.LayoutParams(-1,-2))}
        button(top,"‹ Terug",true){finish()}.also{it.layoutParams=LinearLayout.LayoutParams(-2,-2)}
        label(top,"Instellingen",27f,ink,true).also{it.setPadding(dp(16),0,0,0)}
        message?.let{label(content,it,14f,muted)}

        val health=card()
        label(health,"Health Connect",20f,ink,true)
        label(health,"Ritme kan stappen, gewicht, lichaamsvet, slaap, rusthartslag en verbrande calorieën lezen waarvoor jij toestemming geeft.",15f,muted)
        val availability=HealthConnectClient.getSdkStatus(this)
        if(availability==HealthConnectClient.SDK_AVAILABLE){
            val grantedLabel=label(health,"Toestemmingen controleren…",14f,green)
            lifecycleScope.launch{
                try{
                    val granted=HealthReader(this@SettingsActivity).client.permissionController.getGrantedPermissions()
                    grantedLabel.text=HealthReader.permissionLabels.joinToString("  ·  "){(p,n)->"$n ${if(p in granted)"✓" else "—"}"}
                }catch(_:Exception){grantedLabel.text="Open Health Connect om de toestemming te controleren."}
            }
            button(health,"Toestemmingen beheren",true){requestBackground=false;permissions.launch(HealthReader.permissions)}
            button(health,"Health Connect openen",true){openHealth()}
        }else label(health,"Health Connect is niet beschikbaar op dit toestel.",15f,muted)

        val sync=card()
        label(sync,"Synchronisatie",20f,ink,true)
        status=label(sync,message?:store.status,14f,muted)
        button(sync,"Nu synchroniseren"){runTask{SyncEngine.sync(this@SettingsActivity,false){status.text=it}}}
        val toggle=Switch(this).apply{
            text="Automatisch ongeveer elke 6 uur";textSize=15f;setTextColor(ink);minHeight=dp(60);isChecked=store.background;isEnabled=!busy
            sync.addView(this,LinearLayout.LayoutParams(-1,-2).apply{topMargin=dp(12)})
        }
        toggle.setOnCheckedChangeListener{_,enabled->
            if(!enabled){store.background=false;SyncEngine.schedule(this,false)}
            else if(availability!=HealthConnectClient.SDK_AVAILABLE||!HealthReader(this).backgroundAvailable()){
                toggle.isChecked=false;message="Achtergrondtoegang is op dit toestel niet beschikbaar.";render()
            }else{requestBackground=true;permissions.launch(setOf(HealthReader.background))}
        }

        val account=card()
        label(account,"Website en privacy",20f,ink,true)
        label(account,"Verbonden met\n${connection.server}",14f,muted)
        button(account,"Privacy & gegevensgebruik",true){startActivity(Intent(this,PrivacyActivity::class.java))}
        button(account,"Telefoon ontkoppelen",true){confirmDisconnect(connection)}
        label(content,"Ritme 1.3.0",12f,muted)
    }

    private fun confirmDisconnect(connection:Connection){
        AlertDialog.Builder(this).setTitle("Telefoon ontkoppelen?")
            .setMessage("Synchronisatie stopt. Eerder verstuurde gegevens blijven op je website staan.")
            .setNegativeButton("Annuleren",null).setPositiveButton("Ontkoppelen"){_,_->
                runTask{
                    SyncEngine.mutex.withLock{
                        SyncEngine.schedule(this@SettingsActivity,false)
                        val revoked=try{ServerApi.post(connection.server,"revoke",JSONObject(),connection.token);true}catch(_:Exception){false}
                        store.clear()
                        runOnUiThread{startActivity(Intent(this@SettingsActivity,MainActivity::class.java).addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_NEW_TASK));finish()}
                        if(revoked)"Telefoon ontkoppeld." else "Lokaal ontkoppeld. Trek de telefoon ook op je website in."
                    }
                }
            }.show()
    }

    private fun runTask(action:suspend()->String){
        if(busy)return
        busy=true;message="Even bezig…";render()
        lifecycleScope.launch{
            try{message=action()}
            catch(e:CancellationException){throw e}
            catch(e:Exception){message=when(e){
                is ApiException->e.message
                is SecurityException->"Health Connect-toestemming ontbreekt of is ingetrokken."
                is java.io.IOException->"Verbinding niet gelukt. Controleer internet en je website."
                else->"Niet gelukt. Probeer het opnieuw."
            }}finally{busy=false;if(!isFinishing)render()}
        }
    }
    private fun openHealth(){try{startActivity(Intent(HealthConnectClient.ACTION_HEALTH_CONNECT_SETTINGS))}catch(_:ActivityNotFoundException){openLink("https://play.google.com/store/apps/details?id=com.google.android.apps.healthdata")}}
    private fun openLink(link:String){try{startActivity(Intent(Intent.ACTION_VIEW,Uri.parse(link)))}catch(_:ActivityNotFoundException){message="Geen browser beschikbaar.";render()}}
}
