package nl.ritme.health

import android.annotation.SuppressLint
import android.graphics.Color
import android.net.Uri
import android.os.Bundle
import android.view.Gravity
import android.webkit.CookieManager
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Button
import android.widget.LinearLayout
import android.widget.TextView
import androidx.activity.ComponentActivity
import java.net.URLEncoder

class OverviewActivity : ComponentActivity() {
    private lateinit var webView: WebView
    private fun dp(value: Int) = (value * resources.displayMetrics.density).toInt()

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val connection = SecureStore(this).connection() ?: run { finish(); return }
        val server = Protocol.server(connection.server)
        val base = Uri.parse(server)
        val root = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setBackgroundColor(Color.rgb(245,246,240)) }
        val toolbar = LinearLayout(this).apply {
            gravity = Gravity.CENTER_VERTICAL; setPadding(dp(12),dp(8),dp(16),dp(8)); setBackgroundColor(Color.WHITE)
        }
        toolbar.addView(Button(this).apply { text="‹ Terug"; isAllCaps=false; setOnClickListener { finish() } })
        toolbar.addView(TextView(this).apply { text="Mijn overzicht"; textSize=20f; setTextColor(Color.rgb(35,60,50)); setPadding(dp(14),0,0,0) })
        root.addView(toolbar, LinearLayout.LayoutParams(-1,-2))
        webView = WebView(this).apply {
            setBackgroundColor(Color.rgb(245,246,240))
            settings.javaScriptEnabled = true
            settings.domStorageEnabled = false
            settings.allowFileAccess = false
            settings.allowContentAccess = false
            settings.mixedContentMode = android.webkit.WebSettings.MIXED_CONTENT_NEVER_ALLOW
            webViewClient = object : WebViewClient() {
                override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                    val uri=request.url
                    val sameOrigin=uri.scheme==base.scheme && uri.host==base.host && uri.port==base.port
                    val basePath=base.path.orEmpty().trimEnd('/')+"/"
                    return !(sameOrigin && uri.path.orEmpty().startsWith(basePath))
                }
            }
        }
        CookieManager.getInstance().apply { setAcceptCookie(true); setAcceptThirdPartyCookies(webView,false) }
        root.addView(webView, LinearLayout.LayoutParams(-1,0,1f))
        root.setOnApplyWindowInsetsListener { view,insets -> view.setPadding(insets.systemWindowInsetLeft,insets.systemWindowInsetTop,insets.systemWindowInsetRight,insets.systemWindowInsetBottom); insets }
        setContentView(root)
        val endpoint="$server/api/mobile/web-session.php"
        val body="token="+URLEncoder.encode(connection.token,Charsets.UTF_8.name())
        webView.postUrl(endpoint,body.toByteArray(Charsets.UTF_8))
    }

    override fun onDestroy() {
        if(::webView.isInitialized) { webView.stopLoading(); webView.removeAllViews(); webView.destroy() }
        super.onDestroy()
    }
}
