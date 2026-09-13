plugins { id("com.android.application"); id("org.jetbrains.kotlin.android") }
android {
    namespace = "nl.ritme.health"
    compileSdk = 35
    defaultConfig { applicationId = "nl.ritme.health"; minSdk = 28; targetSdk = 35; versionCode = 4; versionName = "1.1.2" }
    signingConfigs {
        create("release") {
            storeFile = file(System.getenv("RITME_KEYSTORE") ?: "../signing/ritme-release.jks")
            storePassword = System.getenv("RITME_STORE_PASSWORD")
            keyAlias = "ritme"
            keyPassword = System.getenv("RITME_STORE_PASSWORD")
        }
    }
    buildTypes { getByName("release") { isMinifyEnabled = false; signingConfig = signingConfigs.getByName("release") } }
    compileOptions { sourceCompatibility = JavaVersion.VERSION_17; targetCompatibility = JavaVersion.VERSION_17 }
    kotlinOptions { jvmTarget = "17" }
}
dependencies {
    implementation("androidx.activity:activity-ktx:1.10.1")
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.9.0")
    implementation("androidx.health.connect:connect-client:1.1.0-alpha11")
    implementation("androidx.work:work-runtime-ktx:2.10.1")
    implementation("com.journeyapps:zxing-android-embedded:4.3.0")
    testImplementation("junit:junit:4.13.2")
}
