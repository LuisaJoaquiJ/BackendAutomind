# 📱 Guía de Configuración para Android Studio - BackendAutomind

## 🔗 URLs Base de la API

```
Base URL: http://10.0.2.2:8000/api
(Usar 10.0.2.2 en emulador, 127.0.0.1 en dispositivo físico con adb reverse)
```

## 🔐 Autenticación

### 1. Login - Obtener Token
```
POST /api/login
Content-Type: application/json

{
  "email": "usuario@example.com",
  "password": "password123"
}

✅ Response (200):
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "rol": "estudiante"
  }
}
```

### 2. Logout
```
POST /api/logout
Authorization: Bearer {token}

✅ Response (200):
{
  "success": true,
  "message": "Logged out successfully"
}
```

### 3. Get User Info
```
GET /api/user
Authorization: Bearer {token}

✅ Response (200):
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "documento": "1234567890",
    "programa": "Ingeniería de Sistemas",
    "semestre": 4,
    "rol": "estudiante"
  }
}
```

---

## 📚 Materias (Estudiante)

### 1. Listar Materias
```
GET /api/materias
Authorization: Bearer {token}

✅ Response (200):
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Cálculo Diferencial",
      "codigo": "MAT101",
      "creditos": 4,
      "docente": "Dr. Carlos López",
      "horario": "Lunes 8:00-10:00",
      "sala": "Aula 101",
      "created_at": "2026-05-22T10:00:00Z",
      "updated_at": "2026-05-22T10:00:00Z"
    }
  ],
  "total_creditos": 16
}
```

### 2. Crear Materia (Admin/Docente)
```
POST /api/materias
Authorization: Bearer {token}
Content-Type: application/json

{
  "nombre": "Programación Avanzada",
  "codigo": "PRG201",
  "creditos": 3,
  "docente": "Dra. María González",
  "horario": "Martes 10:00-12:00",
  "sala": "Lab 205"
}

✅ Response (201):
{
  "success": true,
  "data": { ... }
}
```

---

## 🎓 Contenido Dinámico (IA)

### 1. Pantalla Dinámica por Nivel
```
GET /api/materias/{id}/ia/pantalla-dinamica?nivel=basico
Authorization: Bearer {token}

Niveles disponibles: basico, intermedio, avanzado

✅ Response (200):
{
  "success": true,
  "data": {
    "materia": { ... },
    "nivel_seleccionado": "basico",
    "niveles_disponibles": ["basico", "intermedio", "avanzado"],
    "introduccion": "Bienvenido a Cálculo Diferencial...",
    "contenidos": [
      {
        "titulo": "Conceptos Básicos",
        "descripcion": "Definición de límites y continuidad..."
      }
    ],
    "ejercicios_interactivos": [
      {
        "titulo": "Quiz: Límites",
        "descripcion": "Responde 5 preguntas...",
        "tipo": "quiz"
      }
    ],
    "actividades": [...],
    "retos": [...],
    "preguntas_frecuentes": [...],
    "aprendizaje_progresivo": {
      "nivel_actual": "basico",
      "siguiente_nivel": "intermedio",
      "progreso_sugerido": "Sigue practicando..."
    }
  }
}
```

### 2. Chat con IA
```
POST /api/materias/{id}/ia/chat
Authorization: Bearer {token}
Content-Type: application/json

{
  "mensaje": "¿Cómo calculo el límite de una función?",
  "nivel": "basico",
  "historial": [
    {
      "role": "user",
      "content": "¿Qué es un límite?"
    },
    {
      "role": "assistant",
      "content": "Un límite es..."
    }
  ]
}

✅ Response (200):
{
  "success": true,
  "data": {
    "respuesta": "El límite se calcula evaluando...",
    "sugerencias": [
      "¿Cuál es la diferencia entre límite por la izquierda y por la derecha?",
      "¿Cómo se resuelven indeterminaciones?",
      "¿Qué es la continuidad?"
    ],
    "pregunta_siguiente": "¿Necesitas más ejemplos?",
    "nivel": "basico"
  }
}
```

---

## 📝 Notas

### 1. Listar Notas
```
GET /api/notas
Authorization: Bearer {token}

✅ Response (200):
{
  "success": true,
  "data": [
    {
      "id": 1,
      "estudiante_id": 5,
      "estudiante": "Juan Pérez",
      "curso_id": 1,
      "nota": 4.2,
      "corte1": 4.0,
      "corte2": 4.5,
      "corte3": 4.0,
      "fecha": "2026-05-22T10:00:00Z"
    }
  ]
}
```

### 2. Crear Nota (Docente)
```
POST /api/notas
Authorization: Bearer {token}
Content-Type: application/json

{
  "estudiante_id": 5,
  "materia_id": 1,
  "corte1": 3.5,
  "corte2": 4.0,
  "corte3": 3.8
}

✅ Response (201):
{
  "success": true,
  "data": { ... }
}
```

---

## ⏰ Horarios

### 1. Listar Horarios
```
GET /api/horarios
Authorization: Bearer {token}

✅ Response (200):
{
  "success": true,
  "data": [
    {
      "id": 1,
      "dia": "Lunes",
      "hora_inicio": "08:00:00",
      "hora_fin": "10:00:00",
      "sala": "Aula 101",
      "curso": "Cálculo Diferencial",
      "codigo": "MAT101",
      "docente": "Dr. Carlos López"
    }
  ]
}
```

---

## 📢 Avisos

### 1. Listar Avisos
```
GET /api/avisos
Authorization: Bearer {token}

✅ Response (200):
{
  "success": true,
  "data": [
    {
      "id": 1,
      "titulo": "Cambio de Horario",
      "contenido": "La clase de mañana se traslada a...",
      "prioridad": "alta",
      "materia_id": 1,
      "fecha_publicacion": "2026-05-22T10:00:00Z"
    }
  ]
}
```

### 2. Crear Aviso (Docente)
```
POST /api/docente/avisos
Authorization: Bearer {token}
Content-Type: application/json

{
  "titulo": "Evaluación Parcial",
  "contenido": "La evaluación será el próximo viernes...",
  "materia_id": 1,
  "prioridad": "alta"
}

✅ Response (201):
{
  "success": true,
  "data": { ... }
}
```

---

## 👨‍🏫 Endpoints Docente

### 1. Dashboard Docente
```
GET /api/docente/dashboard
Authorization: Bearer {token}

✅ Response (200):
{
  "success": true,
  "data": {
    "estadisticas": {
      "total_cursos": 3,
      "total_estudiantes": 45,
      "total_avisos": 8,
      "promedio_general": 3.8,
      "total_contenidos": 12
    }
  }
}
```

### 2. Materias del Docente
```
GET /api/docente/materias
Authorization: Bearer {token}

✅ Response (200):
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Cálculo Diferencial",
      "codigo": "MAT101",
      "creditos": 4,
      "total_estudiantes": 30,
      "horarios": [...]
    }
  ]
}
```

### 3. Estudiantes por Materia
```
GET /api/docente/materias/{materiaId}/estudiantes
Authorization: Bearer {token}

✅ Response (200):
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Juan Pérez",
      "email": "juan@example.com",
      "documento": "1234567890",
      "programa": "Ingeniería",
      "semestre": 4,
      "nota_definitiva": 4.2,
      "notas_parciales": [
        { "tipo": "corte1", "valor": 4.0, "porcentaje": 30 },
        { "tipo": "corte2", "valor": 4.5, "porcentaje": 30 },
        { "tipo": "corte3", "valor": 4.0, "porcentaje": 40 }
      ]
    }
  ]
}
```

### 4. Registrar Nota
```
POST /api/docente/materias/{materiaId}/notas
Authorization: Bearer {token}
Content-Type: application/json

{
  "estudiante_id": 5,
  "corte1": 3.5,
  "corte2": 4.0,
  "corte3": 3.8
}

✅ Response (201):
{
  "success": true,
  "data": { ... }
}
```

### 5. Actualizar Nota
```
PUT /api/docente/notas/{notaId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "corte1": 4.0,
  "corte2": 4.2,
  "corte3": 3.9
}

✅ Response (200):
{
  "success": true,
  "data": { ... }
}
```

---

## 🔧 Configuración para Android

### 1. build.gradle (Project)
```gradle
// Top-level build file
plugins {
    id 'com.android.application' version '8.2.0'
    id 'org.jetbrains.kotlin.android' version '1.9.0'
}

allprojects {
    repositories {
        google()
        mavenCentral()
    }
}
```

### 2. build.gradle (Module: app)
```gradle
dependencies {
    // Retrofit
    implementation 'com.squareup.retrofit2:retrofit:2.9.0'
    implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
    implementation 'com.squareup.okhttp3:okhttp:4.11.0'
    implementation 'com.squareup.okhttp3:logging-interceptor:4.11.0'
    
    // Coroutines
    implementation 'org.jetbrains.kotlinx:kotlinx-coroutines-core:1.7.0'
    implementation 'org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.0'
    
    // ViewModel
    implementation 'androidx.lifecycle:lifecycle-viewmodel-ktx:2.6.1'
    implementation 'androidx.lifecycle:lifecycle-runtime-ktx:2.6.1'
    
    // Navigation
    implementation 'androidx.navigation:navigation-fragment-ktx:2.7.0'
    implementation 'androidx.navigation:navigation-ui-ktx:2.7.0'
    
    // Material
    implementation 'com.google.android.material:material:1.10.0'
}
```

### 3. ApiService.kt
```kotlin
import retrofit2.http.*

interface ApiService {
    
    companion object {
        const val BASE_URL = "http://10.0.2.2:8000/api/"
    }
    
    // Auth
    @POST("login")
    suspend fun login(
        @Body loginRequest: LoginRequest
    ): AuthResponse
    
    @POST("logout")
    suspend fun logout(): ApiResponse<String>
    
    @GET("user")
    suspend fun getUser(): ApiResponse<UserData>
    
    // Materias
    @GET("materias")
    suspend fun getMaterias(): ApiResponse<List<Materia>>
    
    @POST("materias")
    suspend fun createMateria(@Body materia: Materia): ApiResponse<Materia>
    
    // IA
    @GET("materias/{id}/ia/pantalla-dinamica")
    suspend fun getPantallaDinamica(
        @Path("id") materiaId: Int,
        @Query("nivel") nivel: String = "basico"
    ): ApiResponse<PantallaDinamicaData>
    
    @POST("materias/{id}/ia/chat")
    suspend fun chat(
        @Path("id") materiaId: Int,
        @Body chatRequest: ChatRequest
    ): ApiResponse<ChatResponse>
    
    // Notas
    @GET("notas")
    suspend fun getNotas(): ApiResponse<List<Nota>>
    
    // Horarios
    @GET("horarios")
    suspend fun getHorarios(): ApiResponse<List<Horario>>
    
    // Avisos
    @GET("avisos")
    suspend fun getAvisos(): ApiResponse<List<Aviso>>
    
    // Docente
    @GET("docente/dashboard")
    suspend fun getDocenteDashboard(): ApiResponse<DashboardData>
    
    @GET("docente/materias")
    suspend fun getDocenteMaterias(): ApiResponse<List<Materia>>
}

// Data Classes
data class LoginRequest(
    val email: String,
    val password: String
)

data class AuthResponse(
    val success: Boolean,
    val token: String,
    val user: UserData
)

data class ApiResponse<T>(
    val success: Boolean,
    val data: T,
    val message: String? = null
)

data class UserData(
    val id: Int,
    val name: String,
    val email: String,
    val documento: String?,
    val programa: String?,
    val semestre: Int?,
    val rol: String
)

data class Materia(
    val id: Int,
    val nombre: String,
    val codigo: String,
    val creditos: Int,
    val docente: String,
    val horario: String,
    val sala: String
)

data class Nota(
    val id: Int,
    val estudiante_id: Int,
    val estudiante: String,
    val curso_id: Int,
    val nota: Double,
    val corte1: Double?,
    val corte2: Double?,
    val corte3: Double?
)

data class Horario(
    val id: Int,
    val dia: String,
    val hora_inicio: String,
    val hora_fin: String,
    val sala: String,
    val curso: String,
    val codigo: String
)

data class Aviso(
    val id: Int,
    val titulo: String,
    val contenido: String,
    val prioridad: String,
    val materia_id: Int?,
    val fecha_publicacion: String
)

data class PantallaDinamicaData(
    val materia: Materia,
    val nivel_seleccionado: String,
    val niveles_disponibles: List<String>,
    val introduccion: String,
    val contenidos: List<Contenido>,
    val ejercicios_interactivos: List<Ejercicio>,
    val actividades: List<Actividad>,
    val retos: List<Reto>
)

data class Contenido(
    val titulo: String,
    val descripcion: String
)

data class Ejercicio(
    val titulo: String,
    val descripcion: String,
    val tipo: String
)

data class Actividad(
    val titulo: String,
    val descripcion: String,
    val tipo: String
)

data class Reto(
    val titulo: String,
    val descripcion: String
)

data class ChatRequest(
    val mensaje: String,
    val nivel: String = "basico",
    val historial: List<HistorialItem> = emptyList()
)

data class HistorialItem(
    val role: String,
    val content: String
)

data class ChatResponse(
    val respuesta: String,
    val sugerencias: List<String>,
    val pregunta_siguiente: String?,
    val nivel: String
)

data class DashboardData(
    val estadisticas: Estadisticas
)

data class Estadisticas(
    val total_cursos: Int,
    val total_estudiantes: Int,
    val total_avisos: Int,
    val promedio_general: Double,
    val total_contenidos: Int
)
```

### 4. RetrofitClient.kt
```kotlin
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import java.util.concurrent.TimeUnit

object RetrofitClient {
    
    private var token: String = ""
    
    fun setToken(authToken: String) {
        token = authToken
    }
    
    private val loggingInterceptor = HttpLoggingInterceptor().apply {
        level = HttpLoggingInterceptor.Level.BODY
    }
    
    private val authInterceptor = okhttp3.Interceptor { chain ->
        val original = chain.request()
        val requestBuilder = original.newBuilder()
        
        if (token.isNotEmpty()) {
            requestBuilder.header("Authorization", "Bearer $token")
        }
        
        requestBuilder.header("Accept", "application/json")
        requestBuilder.header("Content-Type", "application/json")
        
        chain.proceed(requestBuilder.build())
    }
    
    private val okHttpClient = OkHttpClient.Builder()
        .addInterceptor(loggingInterceptor)
        .addInterceptor(authInterceptor)
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(30, TimeUnit.SECONDS)
        .build()
    
    private val retrofit = Retrofit.Builder()
        .baseUrl(ApiService.BASE_URL)
        .client(okHttpClient)
        .addConverterFactory(GsonConverterFactory.create())
        .build()
    
    val apiService: ApiService = retrofit.create(ApiService::class.java)
}
```

### 5. AndroidManifest.xml
```xml
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    package="com.example.automind">

    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />

    <application
        ...>
        
        <!-- Network Security Config -->
        <domain-config cleartextTrafficPermitted="true">
            <domain includeSubdomains="true">10.0.2.2</domain>
        </domain-config>
        
    </application>

</manifest>
```

### 6. network_security_config.xml
```xml
<?xml version="1.0" encoding="utf-8"?>
<network-security-config>
    <domain-config cleartextTrafficPermitted="true">
        <domain includeSubdomains="true">10.0.2.2</domain>
        <domain includeSubdomains="true">localhost</domain>
        <domain includeSubdomains="true">127.0.0.1</domain>
    </domain-config>
</network-security-config>
```

---

## 🧪 Ejemplos de Uso en Fragments/Activities

### Login Fragment
```kotlin
import kotlinx.coroutines.*

class LoginFragment : Fragment() {
    
    private val apiService = RetrofitClient.apiService
    
    private fun login(email: String, password: String) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = apiService.login(LoginRequest(email, password))
                
                if (response.success) {
                    RetrofitClient.setToken(response.token)
                    // Guardar token en SharedPreferences
                    saveToken(response.token)
                    
                    // Navegar a pantalla principal
                    findNavController().navigate(R.id.action_login_to_home)
                } else {
                    showError("Login fallido")
                }
            } catch (e: Exception) {
                showError(e.message ?: "Error de conexión")
            }
        }
    }
    
    private fun saveToken(token: String) {
        requireActivity().getSharedPreferences("auth", Context.MODE_PRIVATE)
            .edit()
            .putString("token", token)
            .apply()
    }
}
```

### Materias Fragment
```kotlin
class MateriasFragment : Fragment() {
    
    private val apiService = RetrofitClient.apiService
    
    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        loadMaterias()
    }
    
    private fun loadMaterias() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = apiService.getMaterias()
                
                if (response.success) {
                    updateUI(response.data)
                }
            } catch (e: Exception) {
                showError(e.message ?: "Error")
            }
        }
    }
}
```

### Chat Fragment
```kotlin
class ChatFragment : Fragment() {
    
    private val apiService = RetrofitClient.apiService
    private val chatHistory = mutableListOf<HistorialItem>()
    
    private fun enviarMensaje(materiaId: Int, mensaje: String) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val request = ChatRequest(
                    mensaje = mensaje,
                    nivel = "basico",
                    historial = chatHistory
                )
                
                val response = apiService.chat(materiaId, request)
                
                if (response.success) {
                    // Agregar a historial
                    chatHistory.add(HistorialItem("user", mensaje))
                    chatHistory.add(HistorialItem("assistant", response.data.respuesta))
                    
                    // Actualizar UI
                    mostrarRespuesta(response.data)
                }
            } catch (e: Exception) {
                showError(e.message ?: "Error")
            }
        }
    }
}
```

---

## 📋 Checklist de Configuración

- [ ] Base URL configurada correctamente en `ApiService`
- [ ] Dependencias Retrofit agregadas en `build.gradle`
- [ ] `AndroidManifest.xml` incluye permisos de INTERNET
- [ ] `network_security_config.xml` configurado
- [ ] `RetrofitClient` con interceptores de autenticación
- [ ] Token guardado en SharedPreferences después de login
- [ ] Token incluido en headers de todas las requests
- [ ] Manejo de excepciones implementado
- [ ] Coroutines usadas para llamadas asincrónicas

---

## 🔗 URLs de Ejemplo

| Acción | URL |
|--------|-----|
| Login | `POST http://10.0.2.2:8000/api/login` |
| Materias | `GET http://10.0.2.2:8000/api/materias` |
| Chat IA | `POST http://10.0.2.2:8000/api/materias/1/ia/chat` |
| Panel Dinámico | `GET http://10.0.2.2:8000/api/materias/1/ia/pantalla-dinamica?nivel=basico` |
| Notas | `GET http://10.0.2.2:8000/api/notas` |
| Horarios | `GET http://10.0.2.2:8000/api/horarios` |

---

**Versión:** 1.0  
**Última actualización:** Mayo 2026  
**Compatible con:** Android 8.0+, Kotlin 1.9+
