package nl.ritme.health

import android.content.Context
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import org.json.JSONObject
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec

data class Connection(val server: String, val token: String, val timezone: String)

class SecureStore(context: Context) {
    private val prefs = context.getSharedPreferences("ritme", Context.MODE_PRIVATE)
    private fun key(): SecretKey {
        val store = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }
        (store.getKey("ritme-device", null) as? SecretKey)?.let { return it }
        return KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore").apply {
            init(KeyGenParameterSpec.Builder("ritme-device", KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT)
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM).setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE).build())
        }.generateKey()
    }
    fun save(value: Connection) {
        val plain = JSONObject().put("server", value.server).put("token", value.token).put("timezone", value.timezone).toString()
        val cipher = Cipher.getInstance("AES/GCM/NoPadding").apply { init(Cipher.ENCRYPT_MODE, key()) }
        val ciphertext = cipher.doFinal(plain.toByteArray(Charsets.UTF_8))
        check(prefs.edit().putString("connection", Base64.encodeToString(cipher.iv + ciphertext, Base64.NO_WRAP)).commit())
    }
    fun connection(): Connection? {
        val encoded = prefs.getString("connection", null) ?: return null
        return try {
            val bytes = Base64.decode(encoded, Base64.NO_WRAP)
            val cipher = Cipher.getInstance("AES/GCM/NoPadding").apply { init(Cipher.DECRYPT_MODE, key(), GCMParameterSpec(128, bytes.copyOfRange(0, 12))) }
            val value = JSONObject(String(cipher.doFinal(bytes.copyOfRange(12, bytes.size)), Charsets.UTF_8))
            Connection(Protocol.server(value.getString("server")), value.getString("token"), value.getString("timezone"))
        } catch (_: Exception) { null }
    }
    fun clear() { check(prefs.edit().clear().commit()) }
    var background: Boolean
        get() = prefs.getBoolean("background", false)
        set(value) { prefs.edit().putBoolean("background", value).apply() }
    var status: String
        get() = prefs.getString("status", "Nog niet gesynchroniseerd.")!!
        set(value) { prefs.edit().putString("status", value).apply() }
}
