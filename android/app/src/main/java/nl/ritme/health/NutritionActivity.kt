package nl.ritme.health

import android.graphics.Color
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.os.Bundle
import android.text.InputType
import android.view.View
import android.view.WindowManager
import android.widget.*
import androidx.activity.ComponentActivity
import androidx.core.view.WindowCompat
import androidx.lifecycle.lifecycleScope
import com.journeyapps.barcodescanner.ScanContract
import com.journeyapps.barcodescanner.ScanOptions
import kotlinx.coroutines.launch
import org.json.JSONArray
import org.json.JSONObject
import java.time.LocalDate
import java.time.LocalTime
import java.time.format.DateTimeFormatter
import java.util.Locale

class NutritionActivity : ComponentActivity() {
    private val ink=Color.rgb(35,60,50);private val green=Color.rgb(36,105,79);private val muted=Color.rgb(97,115,104)
    private lateinit var content:LinearLayout
    private lateinit var scroll:ScrollView
    private val store by lazy{SecureStore(this)}
    private var date=LocalDate.now();private var day:JSONObject?=null;private var products=JSONArray();private var selected:JSONObject?=null
    private var message:String?=null;private var busy=false;private var createProduct=false;private var scannedBarcode="";private var searchText=""
    private val scan=registerForActivityResult(ScanContract()){result->result.contents?.let{barcode->scannedBarcode=barcode.trim();findBarcode(scannedBarcode)}}
    override fun onCreate(savedInstanceState:Bundle?){super.onCreate(savedInstanceState);WindowCompat.setDecorFitsSystemWindows(window,true);window.setSoftInputMode(WindowManager.LayoutParams.SOFT_INPUT_ADJUST_PAN);if(store.connection()==null){finish();return};loadDay()}
    private fun dp(v:Int)=(v*resources.displayMetrics.density).toInt()
    private fun shape(color:Int,radius:Int=18)=GradientDrawable().apply{setColor(color);cornerRadius=dp(radius).toFloat()}
    private fun label(parent:LinearLayout,text:String,size:Float=16f,color:Int=ink,bold:Boolean=false)=TextView(this).apply{this.text=text;textSize=size;setTextColor(color);if(bold)setTypeface(typeface,Typeface.BOLD);setPadding(0,dp(4),0,dp(8));parent.addView(this)}
    private fun card(color:Int=Color.WHITE)=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;background=shape(color);setPadding(dp(20),dp(17),dp(20),dp(17));content.addView(this,LinearLayout.LayoutParams(-1,-2).apply{bottomMargin=dp(16)})}
    private fun button(parent:LinearLayout,text:String,secondary:Boolean=false,action:()->Unit)=Button(this).apply{this.text=text;isAllCaps=false;textSize=15f;minHeight=dp(48);background=shape(if(secondary)Color.rgb(232,239,226)else green,12);setTextColor(if(secondary)ink else Color.WHITE);isEnabled=!busy;parent.addView(this,LinearLayout.LayoutParams(-1,-2).apply{topMargin=dp(9)});setOnClickListener{action()}}
    private fun input(parent:LinearLayout,hint:String,decimal:Boolean=false,value:String="")=EditText(this).apply{this.hint=hint;setText(value);setTextColor(ink);textSize=16f;inputType=if(decimal)InputType.TYPE_CLASS_NUMBER or InputType.TYPE_NUMBER_FLAG_DECIMAL else InputType.TYPE_CLASS_TEXT;minHeight=dp(50);parent.addView(this,LinearLayout.LayoutParams(-1,-2).apply{topMargin=dp(8)})}
    private fun render(){
        scroll=ScrollView(this).apply{isFillViewport=true;setBackgroundColor(Color.rgb(245,246,240));clipToPadding=false};content=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;setPadding(dp(20),dp(22),dp(20),dp(160))};scroll.addView(content);setContentView(scroll)
        val head=LinearLayout(this).apply{orientation=LinearLayout.HORIZONTAL;gravity=android.view.Gravity.CENTER_VERTICAL;content.addView(this,LinearLayout.LayoutParams(-1,-2))}
        button(head,"←",true){finish()};head.getChildAt(0).layoutParams=LinearLayout.LayoutParams(dp(58),dp(48)).apply{rightMargin=dp(12)}
        label(head,"Voeding",28f,green,true);label(content,"CALORIEËN EN MACRO’S",11f,muted,true)
        label(content,if(date==LocalDate.now())"Vandaag" else date.format(DateTimeFormatter.ofPattern("EEEE d MMMM",Locale("nl","NL"))),16f,ink,true)
        message?.let{label(card(Color.rgb(224,236,217)),it,14f,ink)}

        val add=card();label(add,"Product toevoegen",19f,ink,true);button(add,"Barcode scannen"){scan.launch(ScanOptions().setCaptureActivity(PortraitCaptureActivity::class.java).setDesiredBarcodeFormats(ScanOptions.PRODUCT_CODE_TYPES).setPrompt("Scan de barcode op de verpakking").setBeepEnabled(false).setOrientationLocked(true))}
        val search=input(add,"Zoek in je eigen producten",false,searchText);button(add,"Zoeken",true){searchText=search.text.toString().trim();searchProducts(searchText)};button(add,"Nieuw product invoeren",true){scannedBarcode="";createProduct=true;selected=null;render()}
        if(products.length()>0){for(i in 0 until products.length()){val product=products.getJSONObject(i);button(add,listName(product),true){selected=product;createProduct=false;render()}}}
        if(createProduct)renderProductForm()
        selected?.let{renderEntryForm(it)}

        if(busy)content.addView(ProgressBar(this).apply{isIndeterminate=true},LinearLayout.LayoutParams(dp(40),dp(40)))
        label(content,"Producten en dagboekregels worden uitsluitend op je eigen website opgeslagen.",12f,muted)
        val nav=card();label(nav,"Andere dag bekijken",16f,ink,true)
        val navRow=LinearLayout(this).apply{orientation=LinearLayout.HORIZONTAL;gravity=android.view.Gravity.CENTER_VERTICAL;nav.addView(this,LinearLayout.LayoutParams(-1,-2))}
        button(navRow,"‹",true){changeDate(date.minusDays(1))}.also{it.layoutParams=LinearLayout.LayoutParams(dp(56),dp(48))}
        label(navRow,date.format(DateTimeFormatter.ofPattern("EEE d MMM",Locale("nl","NL"))),16f,ink,true).also{it.gravity=android.view.Gravity.CENTER;it.layoutParams=LinearLayout.LayoutParams(0,-2,1f)}
        button(navRow,"›",true){changeDate(date.plusDays(1))}.also{it.layoutParams=LinearLayout.LayoutParams(dp(56),dp(48));it.isEnabled=date<LocalDate.now();it.alpha=if(it.isEnabled)1f else .35f}
    }
    private fun changeDate(newDate:LocalDate){if(newDate>LocalDate.now())return;date=newDate;selected=null;products=JSONArray();createProduct=false;loadDay()}
    private fun renderProductForm(){val form=card();label(form,if(scannedBarcode.isEmpty())"Nieuw product" else "Onbekende barcode",19f,ink,true);val barcode=input(form,"Barcode (optioneel)",false,scannedBarcode).apply{inputType=InputType.TYPE_CLASS_NUMBER};val name=input(form,"Productnaam");val brand=input(form,"Merk (optioneel)");label(form,"Voedingswaarden per 100 gram",14f,muted,true);val kcal=input(form,"Calorieën (kcal)",true);val protein=input(form,"Eiwit (g)",true);val carbs=input(form,"Koolhydraten (g)",true);val fat=input(form,"Vet (g)",true);button(form,"Product opslaan"){try{val barcodeValue=barcode.text.toString().trim();require(barcodeValue.isEmpty()||barcodeValue.matches(Regex("[0-9]{6,32}"))){"Ongeldige barcode"};val payload=JSONObject().put("action","create_product").put("barcode",barcodeValue).put("name",name.text.toString().trim()).put("brand",brand.text.toString().trim()).put("calories_per_100g",decimal(kcal)).put("protein_per_100g",decimal(protein)).put("carbohydrates_per_100g",decimal(carbs)).put("fat_per_100g",decimal(fat));call(payload){selected=it.getJSONObject("product");createProduct=false;message="Product opgeslagen."}}catch(_:IllegalArgumentException){message="Vul een geldige barcode, productnaam en waarden per 100 gram in.";render()}}}
    private fun renderEntryForm(product:JSONObject){val form=card(Color.rgb(245,249,242));label(form,listName(product),19f,ink,true);label(form,"Per 100 g: ${number(product,"calories_per_100g",0)} kcal · ${number(product,"protein_per_100g",1)} g eiwit",13f,muted);val grams=input(form,"Hoeveel gram?",true);label(form,"Dagdeel",13f,muted,true);val meal=Spinner(this).apply{adapter=ArrayAdapter(this@NutritionActivity,android.R.layout.simple_spinner_dropdown_item,listOf("Ontbijt","Lunch","Diner","Dessert"));setSelection(suggestedMeal());form.addView(this,LinearLayout.LayoutParams(-1,dp(56)).apply{topMargin=dp(4)})};button(form,"Toevoegen"){try{val keys=listOf("breakfast","lunch","dinner","other");call(JSONObject().put("action","add_entry").put("date",date.toString()).put("product_id",product.getInt("id")).put("amount_g",decimal(grams)).put("meal",keys[meal.selectedItemPosition])){day=it;selected=null;message="Toegevoegd. Bekijk je dagboek via Mijn overzicht."}}catch(_:IllegalArgumentException){message="Vul een geldige hoeveelheid groter dan nul in.";render()}}}
    private fun suggestedMeal():Int{val time=LocalTime.now();return when{time>=LocalTime.of(3,0)&&time<LocalTime.NOON->0;time>=LocalTime.NOON&&time<LocalTime.of(17,0)->1;time>=LocalTime.of(17,0)&&time<=LocalTime.of(20,0)->2;else->3}}
    private fun decimal(field:EditText):Double{val value=field.text.toString().trim().replace(',','.').toDoubleOrNull();require(value!=null&&value>=0){"Vul geldige voedingswaarden in."};return value}
    private fun loadDay(){call(JSONObject().put("action","day").put("date",date.toString())){day=it}}
    private fun findBarcode(barcode:String){if(!barcode.matches(Regex("[0-9]{6,32}"))){message="Deze barcode wordt niet herkend.";render();return};call(JSONObject().put("action","find_barcode").put("barcode",barcode)){val p=it.optJSONObject("product");if(p==null){createProduct=true;selected=null;message="Barcode gescand, maar het product staat nog niet in de productendatabase. Controleer de barcode en vul de waarden van het etiket in."}else{selected=p;createProduct=false;message="Product en voedingswaarden gevonden."}}}
    private fun searchProducts(query:String){if(query.length<2){message="Gebruik minimaal twee zoektekens.";render();return};call(JSONObject().put("action","search").put("query",query)){products=it.optJSONArray("products")?:JSONArray();message=if(products.length()==0)"Geen product gevonden." else "${products.length()} product(en) gevonden."}}
    private fun call(payload:JSONObject,onSuccess:(JSONObject)->Unit){if(busy)return;val connection=store.connection()?:return;busy=true;render();lifecycleScope.launch{try{onSuccess(ServerApi.post(connection.server,"nutrition",payload,connection.token))}catch(e:Exception){message=if(e is ApiException)e.message else e.message?:"Niet gelukt. Controleer de website-update en verbinding."}finally{busy=false;render()}}}
    private fun number(value:JSONObject,key:String,decimals:Int):String{if(!value.has(key)||value.isNull(key))return "—";return String.format(Locale("nl","NL"),"%.${decimals}f",value.optDouble(key))}
    private fun listName(product:JSONObject)=listOf(product.optString("name"),product.optString("brand")).filter{it.isNotBlank()}.joinToString(" · ")
}
