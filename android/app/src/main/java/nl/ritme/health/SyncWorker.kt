package nl.ritme.health

import android.content.Context
import androidx.health.connect.client.HealthConnectClient
import androidx.work.*
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import org.json.JSONObject
import java.time.ZonedDateTime
import java.time.format.DateTimeFormatter
import java.util.concurrent.TimeUnit

object SyncEngine {
    val mutex = Mutex()
    suspend fun sync(context: Context, background: Boolean, progress: (String) -> Unit = {}): String = mutex.withLock {
        val store = SecureStore(context)
        val connection = store.connection() ?: error("Koppel eerst je website.")
        check(HealthConnectClient.getSdkStatus(context) == HealthConnectClient.SDK_AVAILABLE) { "Installeer of werk Health Connect bij." }
        val health = HealthReader(context)
        if(background) check(store.background && health.backgroundAvailable() && HealthReader.background in health.client.permissionController.getGrantedPermissions()) { "Achtergrondtoegang staat uit. Open de app om te synchroniseren." }
        val payload = health.snapshot(connection.timezone, progress)
        progress("Veilig versturen naar je website…")
        ServerApi.post(connection.server,"sync",payload,connection.token)
        val timestamp = ZonedDateTime.now(Protocol.zone(connection.timezone)).format(DateTimeFormatter.ofPattern("dd-MM HH:mm"))
        val message = "Bijgewerkt op $timestamp · 28 dagen gecontroleerd."
        store.status = message
        message
    }
    fun schedule(context: Context, enabled: Boolean) {
        val manager = WorkManager.getInstance(context)
        if(!enabled) {manager.cancelUniqueWork("ritme-health-sync");return}
        val work = PeriodicWorkRequestBuilder<SyncWorker>(6, TimeUnit.HOURS)
            .setConstraints(Constraints.Builder().setRequiredNetworkType(NetworkType.CONNECTED).build())
            .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 5, TimeUnit.MINUTES).build()
        manager.enqueueUniquePeriodicWork("ritme-health-sync", ExistingPeriodicWorkPolicy.KEEP, work)
    }
}
class SyncWorker(context: Context, params: WorkerParameters): CoroutineWorker(context,params) {
    override suspend fun doWork(): Result {
        val store = SecureStore(applicationContext)
        if(!store.background || store.connection()==null) return Result.success()
        return try {SyncEngine.sync(applicationContext,true);Result.success()}
        catch(e: CancellationException) {throw e}
        catch(e: Exception) {
            store.status = "Automatisch bijwerken niet gelukt. Open de app en probeer opnieuw."
            if(e is java.io.IOException && (e !is ApiException || e.statusCode>=500 || e.statusCode==429) && runAttemptCount<3) Result.retry() else Result.failure()
        }
    }
}
