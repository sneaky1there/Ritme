package nl.ritme.health
import org.junit.Test
import org.junit.Assert.*
import java.time.LocalDate

class ProtocolTest {
    @Test fun acceptsHttpsWithSubdirectory() {assertEquals("https://example.nl/fitness",Protocol.server("https://example.nl/fitness/"))}
    @Test(expected=IllegalArgumentException::class) fun refusesCleartext() {Protocol.server("http://example.nl")}
    @Test(expected=IllegalArgumentException::class) fun refusesEmbeddedPassword() {Protocol.server("https://user:secret@example.nl")}
    @Test(expected=IllegalArgumentException::class) fun refusesQuery() {Protocol.server("https://example.nl?token=x")}
    @Test fun pairingDecodesServer() {val p=Protocol.pairing("ritme://pair?server=https%3A%2F%2Fexample.nl%2Ffitness&code="+"a".repeat(64));assertEquals("https://example.nl/fitness",p.first);assertEquals(64,p.second.length)}
    @Test(expected=IllegalArgumentException::class) fun refusesWrongQr() {Protocol.pairing("https://example.nl")}
    @Test(expected=IllegalArgumentException::class) fun refusesBadSecret() {Protocol.pairing("ritme://pair?server=https%3A%2F%2Fexample.nl&code=abc")}
    @Test fun windowIncludesTodayAnd28Dates() {val dates=Protocol.dates(LocalDate.parse("2026-01-01"));assertEquals(28,dates.size);assertEquals("2025-12-05",dates.first().toString());assertEquals("2026-01-01",dates.last().toString());assertEquals(28,dates.distinct().size)}
    @Test fun daylightSavingUsesCalendarDays() {val z=Protocol.zone("Europe/Amsterdam");val d=LocalDate.parse("2026-03-29");assertEquals(23L,java.time.Duration.between(d.atStartOfDay(z),d.plusDays(1).atStartOfDay(z)).toHours())}
}
