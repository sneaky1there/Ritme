package nl.ritme.health

import java.net.URI
import java.net.URLDecoder
import java.time.LocalDate
import java.time.ZoneId

object Protocol {
    fun server(value: String): String {
        val uri = URI(value.trim())
        require(uri.scheme == "https" && !uri.host.isNullOrBlank()) { "Gebruik een geldig HTTPS-adres." }
        require(uri.rawUserInfo == null && uri.rawQuery == null && uri.rawFragment == null) { "Het serveradres mag geen inloggegevens of query bevatten." }
        require(uri.port == -1 || uri.port in 1..65535)
        require(!uri.path.orEmpty().split('/').any { it == ".." || it == "." })
        return uri.toASCIIString().trimEnd('/')
    }
    fun pairing(value: String): Pair<String, String> {
        val uri = URI(value.trim())
        require(uri.scheme == "ritme" && uri.host == "pair") { "Scan of plak de koppellink uit Ritme → Telefoon." }
        val pairs = uri.rawQuery.orEmpty().split('&').associate {
            val parts = it.split('=', limit = 2)
            URLDecoder.decode(parts[0], "UTF-8") to URLDecoder.decode(parts.getOrElse(1) { "" }, "UTF-8")
        }
        val code = pairs["code"].orEmpty()
        require(code.matches(Regex("[a-f0-9]{64}"))) { "Deze koppellink is ongeldig." }
        return server(pairs["server"].orEmpty()) to code
    }
    fun dates(today: LocalDate): List<LocalDate> = (27L downTo 0L).map { today.minusDays(it) }
    fun zone(value: String): ZoneId = ZoneId.of(value)
}
