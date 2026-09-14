package nl.ritme.health

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.io.IOException
import java.net.URL
import javax.net.ssl.HttpsURLConnection

class ApiException(val statusCode: Int, message: String) : IOException(message)
object ServerApi {
    suspend fun post(server: String, endpoint: String, payload: JSONObject, token: String? = null): JSONObject = withContext(Dispatchers.IO) {
        val connection = URL(Protocol.server(server) + "/api/mobile/" + endpoint + ".php").openConnection() as HttpsURLConnection
        try {
            connection.requestMethod = "POST"
            connection.instanceFollowRedirects = false // Do not forward credentials to another host.
            connection.connectTimeout = 15000; connection.readTimeout = 30000
            connection.doOutput = true
            connection.setRequestProperty("Content-Type", "application/json; charset=utf-8")
            connection.setRequestProperty("Accept", "application/json")
            token?.let { connection.setRequestProperty("Authorization", "Bearer $it") }
            connection.outputStream.use { it.write(payload.toString().toByteArray(Charsets.UTF_8)) }
            val code = connection.responseCode
            if (code !in 200..299) {
                val apiMessage=try{connection.errorStream?.use{JSONObject(String(it.readBytesBounded(),Charsets.UTF_8)).optString("message").takeIf(String::isNotBlank)}}catch(_:Exception){null}
                val message = apiMessage ?: when(code) {
                    401,403 -> "Koppeling ongeldig of ingetrokken. Maak een nieuwe koppellink op de website."
                    409 -> "Koppellink gebruikt, verlopen of tijdzone gewijzigd. Koppel opnieuw."
                    413,422 -> "Gegevens geweigerd. Controleer of app en website dezelfde versie gebruiken."
                    429 -> "Te veel verzoeken. Probeer over een paar minuten opnieuw."
                    in 300..399 -> "De website verwijst door. Gebruik het definitieve HTTPS-adres in de webconfiguratie."
                    else -> "De website is niet beschikbaar (HTTP $code). Controleer de installatie."
                }
                throw ApiException(code, message)
            }
            val bytes = connection.inputStream.use { it.readBytesBounded() }
            JSONObject(String(bytes, Charsets.UTF_8))
        } finally { connection.disconnect() }
    }
    private fun java.io.InputStream.readBytesBounded(): ByteArray {
        val out = java.io.ByteArrayOutputStream()
        val buffer = ByteArray(4096)
        while (true) { val n = read(buffer); if (n < 0) break; out.write(buffer,0,n); if(out.size()>65536) throw IOException("Ongeldig serverantwoord.") }
        return out.toByteArray()
    }
}
