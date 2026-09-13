package nl.ritme.health

import android.content.Context
import androidx.health.connect.client.HealthConnectClient
import androidx.health.connect.client.HealthConnectFeatures
import androidx.health.connect.client.feature.ExperimentalFeatureAvailabilityApi
import androidx.health.connect.client.permission.HealthPermission
import androidx.health.connect.client.records.StepsRecord
import androidx.health.connect.client.records.WeightRecord
import androidx.health.connect.client.records.SleepSessionRecord
import androidx.health.connect.client.request.AggregateRequest
import androidx.health.connect.client.request.ReadRecordsRequest
import androidx.health.connect.client.time.TimeRangeFilter
import kotlinx.coroutines.delay
import org.json.JSONArray
import org.json.JSONObject
import java.time.Instant
import java.time.LocalDate
import java.time.ZoneId

class HealthReader(context: Context) {
    val client = HealthConnectClient.getOrCreate(context)
    companion object {
        val steps = HealthPermission.getReadPermission(StepsRecord::class)
        val weight = HealthPermission.getReadPermission(WeightRecord::class)
        val sleep = HealthPermission.getReadPermission(SleepSessionRecord::class)
        val permissions = setOf(steps, weight, sleep)
        val background = HealthPermission.PERMISSION_READ_HEALTH_DATA_IN_BACKGROUND
    }
    @OptIn(ExperimentalFeatureAvailabilityApi::class)
    fun backgroundAvailable() = client.features.getFeatureStatus(HealthConnectFeatures.FEATURE_READ_HEALTH_DATA_IN_BACKGROUND) == HealthConnectFeatures.FEATURE_STATUS_AVAILABLE
    suspend fun snapshot(timezone: String, onProgress: (String) -> Unit): JSONObject {
        val granted = client.permissionController.getGrantedPermissions()
        val metrics = linkedMapOf(steps to "steps", weight to "weight", sleep to "sleep_minutes").filterKeys { it in granted }.values.toList()
        check(metrics.isNotEmpty()) { "Geef eerst toestemming voor minstens één gegevenstype." }
        val zone = ZoneId.of(timezone)
        val now = Instant.now()
        val dates = Protocol.dates(now.atZone(zone).toLocalDate())
        val rows = JSONArray()
        // Read weight once, paginate and choose the latest measurement per server-local calendar day.
        val weights = mutableMapOf<LocalDate, WeightRecord>()
        if (weight in granted) {
            var page: String? = null
            do {
                val response = client.readRecords(ReadRecordsRequest(WeightRecord::class,
                    timeRangeFilter = TimeRangeFilter.between(dates.first().atStartOfDay(zone).toInstant(), now), pageSize = 1000, pageToken = page))
                response.records.forEach { record ->
                    val date = record.time.atZone(zone).toLocalDate()
                    if(weights[date]?.time?.isAfter(record.time) != true) weights[date] = record
                }
                page = response.pageToken
            } while(page != null)
        }
        dates.forEachIndexed { index, date ->
            onProgress("Health Connect lezen · ${index + 1} / ${dates.size} dagen")
            val start = date.atStartOfDay(zone).toInstant()
            val end = minOf(date.plusDays(1).atStartOfDay(zone).toInstant(), now)
            val row = JSONObject().put("date", date.toString())
            val origins = JSONObject()
            val aggregateMetrics = buildSet {
                if(steps in granted) add(StepsRecord.COUNT_TOTAL)
                if(sleep in granted) add(SleepSessionRecord.SLEEP_DURATION_TOTAL)
            }
            if(aggregateMetrics.isNotEmpty()) {
                // Health Connect aggregation deduplicates prioritized activity/sleep sources.
                val result = client.aggregate(AggregateRequest(metrics = aggregateMetrics, timeRangeFilter = TimeRangeFilter.between(start,end)))
                val packages = JSONArray(result.dataOrigins.map { it.packageName }.sorted())
                if(steps in granted) { row.put("steps", result[StepsRecord.COUNT_TOTAL] ?: JSONObject.NULL); origins.put("steps", packages) }
                if(sleep in granted) { row.put("sleep_minutes", result[SleepSessionRecord.SLEEP_DURATION_TOTAL]?.toMinutes() ?: JSONObject.NULL); origins.put("sleep_minutes", packages) }
            }
            if(weight in granted) {
                row.put("weight", weights[date]?.weight?.inKilograms ?: JSONObject.NULL)
                origins.put("weight", JSONArray(weights[date]?.let { listOf(it.metadata.dataOrigin.packageName) } ?: emptyList<String>()))
            }
            row.put("origins", origins)
            rows.put(row)
            delay(110) // Avoid bursting IPC requests on older Health Connect providers.
        }
        // Revocation during reads must not result in a partial snapshot being uploaded.
        check(client.permissionController.getGrantedPermissions().containsAll(granted.intersect(permissions))) { "Toestemming gewijzigd. Probeer opnieuw." }
        return JSONObject().put("protocol",1).put("timezone",timezone).put("from",dates.first().toString())
            .put("to",dates.last().toString()).put("metrics",JSONArray(metrics)).put("days",rows)
    }
}
