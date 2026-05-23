# 🤖 Agente de IA Ollama - BackendAutomind

Este documento explica cómo configurar y usar el agente de IA local con Ollama integrado en BackendAutomind.

## ✨ Características

- **Generación de contenido educativo**: Crea automáticamente materiales de aprendizaje para diferentes niveles
- **Chat interactivo**: Responde preguntas de estudiantes sobre el contenido de las materias
- **Procesamiento de PDFs**: Extrae texto de documentos para usarlos como contexto
- **Múltiples niveles**: Básico, Intermedio, Avanzado
- **Totalmente local**: Funciona con Ollama sin conexión a internet

## 🚀 Requisitos

### 1. Ollama Instalado

Descarga Ollama desde: https://ollama.ai

Después de instalar, asegúrate de que el servicio esté corriendo:
```bash
# Linux/Mac
ollama serve

# Windows (Ollama se ejecuta como servicio automáticamente)
```

### 2. Modelo Descargado

Descarga un modelo de lenguaje local:
```bash
ollama pull llama3.1
# O cualquier otro modelo disponible: mistral, neural-chat, etc.
```

Verifica los modelos disponibles:
```bash
ollama list
```

## ⚙️ Configuración

### Variables de Entorno (.env)

```env
# AI Configuration
AI_PROVIDER=ollama
OLLAMA_HOST=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.1
OLLAMA_TIMEOUT=90
```

**Explicación:**
- `AI_PROVIDER`: Proveedor de IA (actualmente solo 'ollama')
- `OLLAMA_HOST`: URL del servidor Ollama
- `OLLAMA_MODEL`: Modelo a usar (ej: llama3.1, mistral, neural-chat)
- `OLLAMA_TIMEOUT`: Timeout en segundos para solicitudes

## 🔌 Endpoints de API

### 1. Pantalla Dinámica - Contenido Generado por IA

Obtiene contenido educativo generado para una materia en un nivel específico.

```bash
GET /api/materias/{id}/pantalla-dinamica?nivel=basico
```

**Parámetros de Query:**
- `nivel`: `basico` | `intermedio` | `avanzado` (default: basico)

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "materia": { ... },
    "nivel_seleccionado": "basico",
    "niveles_disponibles": ["basico", "intermedio", "avanzado"],
    "introduccion": "...",
    "contenidos": [
      {
        "titulo": "...",
        "descripcion": "..."
      }
    ],
    "ejercicios_interactivos": [
      {
        "titulo": "...",
        "descripcion": "...",
        "tipo": "quiz"
      }
    ],
    "actividades": [...],
    "retos": [...],
    "preguntas_frecuentes": [...],
    "aprendizaje_progresivo": {
      "nivel_actual": "basico",
      "siguiente_nivel": "intermedio",
      "progreso_sugerido": "..."
    }
  }
}
```

### 2. Chat Interactivo - Tutor IA

Inicia un diálogo con el agente IA sobre los contenidos de una materia.

```bash
POST /api/materias/{id}/chat
```

**Body (JSON):**
```json
{
  "mensaje": "¿Puedes explicarme este concepto?",
  "nivel": "basico",
  "historial": [
    {
      "role": "user",
      "content": "¿Qué es una variable?"
    },
    {
      "role": "assistant",
      "content": "Una variable es..."
    }
  ]
}
```

**Parámetros:**
- `mensaje`: (requerido) La pregunta o solicitud del estudiante
- `nivel`: `basico` | `intermedio` | `avanzado` (default: basico)
- `historial`: Array del historial de conversación (opcional)

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "respuesta": "Aquí está la respuesta...",
    "sugerencias": [
      "Siguiente pregunta 1",
      "Siguiente pregunta 2"
    ],
    "pregunta_siguiente": "¿Tienes más preguntas?",
    "nivel": "basico"
  }
}
```

## 📁 Estructura de Archivos

```
app/
├── Services/
│   └── MateriaIAService.php          # Servicio principal de IA
├── Jobs/
│   └── ProcesarPdfConIA.php          # Job para procesar PDFs en background
└── Http/Controllers/
    └── MateriaController.php         # Controlador con nuevos métodos

config/
└── ai.php                            # Configuración de IA

.env                                  # Variables de entorno (incluye OLLAMA_*)
```

## 🛠️ Clases Principales

### MateriaIAService

**Métodos públicos:**

```php
// Extrae texto de un PDF
public function extractPdfText(UploadedFile $file): string

// Construye un pack de aprendizaje completo
public function buildLearningPack(Materia $materia, ?string $pdfText, string $nivel): array

// Construye un resumen de PDF
public function buildPdfSummary(Materia $materia, ?string $pdfText, string $nivel): array

// Genera respuestas de chat con historial
public function buildChatResponse(Materia $materia, ?string $pdfText, string $message, string $nivel = 'basico', array $history = []): array
```

**Uso en el Controlador:**

```php
$iaService = app(MateriaIAService::class);

// Generar contenido
$pack = $iaService->buildLearningPack($materia, $textoDelPDF, 'basico');

// Procesar chat
$respuesta = $iaService->buildChatResponse($materia, $textoDelPDF, "¿Qué es X?", 'basico', $historial);
```

### ProcesarPdfConIA (Job)

Procesa PDFs en segundo plano y genera contenido para los tres niveles.

```php
use App\Jobs\ProcesarPdfConIA;

// Despachar el job
ProcesarPdfConIA::dispatch($materiaId, $contenidoId);
```

## 🔍 Flujo de Datos

1. **Solicitud del Cliente** → Endpoint `/api/materias/{id}/chat` o `/api/materias/{id}/pantalla-dinamica`

2. **Validación** → El controlador valida permisos y parámetros

3. **Extracción de Contexto** → Se obtiene el texto del PDF (si existe)

4. **Construcción del Prompt** → El servicio arma un prompt optimizado para IA

5. **Solicitud a Ollama** → Se envía el prompt al servidor local de Ollama

6. **Procesamiento de Respuesta** → Se valida y normaliza el JSON de respuesta

7. **Fallback** → Si Ollama no responde, se retorna contenido por defecto

8. **Respuesta al Cliente** → Se retorna el JSON procesado

## 🧠 Ejemplos de Uso

### Ejemplo 1: Obtener Contenido para Estudiante

```bash
curl -X GET \
  'http://127.0.0.1:8000/api/materias/1/pantalla-dinamica?nivel=intermedio' \
  -H 'Authorization: Bearer TU_TOKEN'
```

### Ejemplo 2: Chat con el Agente

```bash
curl -X POST \
  'http://127.0.0.1:8000/api/materias/1/chat' \
  -H 'Authorization: Bearer TU_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "mensaje": "¿Cuáles son los pasos para resolver una ecuación cuadrática?",
    "nivel": "intermedio",
    "historial": []
  }'
```

### Ejemplo 3: Chat con Historial

```bash
curl -X POST \
  'http://127.0.0.1:8000/api/materias/1/chat' \
  -H 'Authorization: Bearer TU_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "mensaje": "¿Y cómo sé si una ecuación es cuadrática?",
    "nivel": "basico",
    "historial": [
      {
        "role": "user",
        "content": "¿Cuáles son los pasos para resolver una ecuación?"
      },
      {
        "role": "assistant",
        "content": "Los pasos son: 1) Igualar a cero, 2) Usar la fórmula general..."
      }
    ]
  }'
```

## ⚠️ Troubleshooting

### Problema: "Connection refused" a Ollama

**Solución:**
```bash
# Verifica que Ollama esté corriendo
ollama serve

# En otra terminal, verifica la conexión
curl http://127.0.0.1:11434/api/tags
```

### Problema: Modelo no encontrado

**Solución:**
```bash
# Descarga un modelo
ollama pull llama3.1

# Verifica que esté disponible
ollama list
```

### Problema: Respuestas lentas

**Solución:**
- Aumenta `OLLAMA_TIMEOUT` en .env
- Usa un modelo más pequeño (ej: mistral en lugar de llama3.1)
- Mejora el hardware de la máquina

### Problema: JSON inválido en respuestas

**Solución:**
- El servicio tiene fallback automático
- Si persiste, revisa los logs: `php artisan logs` o `storage/logs/`

## 📚 Modelos Recomendados

| Modelo | Tamaño | Velocidad | Calidad | Recomendación |
|--------|--------|-----------|---------|---------------|
| mistral | 4.1GB | ⚡⚡⚡ | ⭐⭐⭐ | Mejor relación velocidad/calidad |
| llama3.1 | 4.7GB | ⚡⚡ | ⭐⭐⭐⭐ | Muy buena calidad |
| neural-chat | 3.3GB | ⚡⚡⚡ | ⭐⭐⭐ | Optimizado para chat |
| openchat | 3.5GB | ⚡⚡⚡ | ⭐⭐⭐ | Rápido y competente |

```bash
# Cambiar modelo en .env
OLLAMA_MODEL=mistral
```

## 🔒 Seguridad

- ✅ Valida entrada del usuario
- ✅ Respeta permisos por rol
- ✅ Timeout de solicitudes configurables
- ✅ Logs de errores
- ✅ Manejo de excepciones

## 🚀 Próximas Mejoras

- [ ] Cacheo de respuestas frecuentes
- [ ] Métricas de uso del agente
- [ ] Fine-tuning de prompts por materia
- [ ] Evaluación de respuestas por estudiantes
- [ ] Dashboard de análisis de IA

---

**Versión:** 1.0  
**Última actualización:** Mayo 2026  
**Compatibilidad:** Laravel 12+
