# 🚀 GUÍA RÁPIDA - Agente IA con Ollama

## 🎯 En 5 minutos estarás listo

### Paso 1: Instala Ollama (si no lo tienes)

**Windows/Mac/Linux:**
Descarga desde → https://ollama.ai

### Paso 2: Descarga un Modelo

```bash
ollama pull llama3.1
```

O un modelo más rápido:
```bash
ollama pull mistral
```

### Paso 3: Verifica que Ollama esté corriendo

Abre otra terminal y ejecuta:
```bash
ollama serve
```

Deberías ver: `Listening on ...`

### Paso 4: Configura las Variables de Entorno

Tu `.env` ya tiene esto configurado:
```env
AI_PROVIDER=ollama
OLLAMA_HOST=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.1
OLLAMA_TIMEOUT=90
```

**¿Usas otro modelo?** Cambia `OLLAMA_MODEL`:
```env
OLLAMA_MODEL=mistral
```

### Paso 5: ¡Listo! Usa la API

#### Prueba en tu terminal:

```bash
# Obtener contenido dinámico
curl -X GET "http://127.0.0.1:8000/api/materias/1/ia/pantalla-dinamica?nivel=basico" \
  -H "Authorization: Bearer TU_TOKEN"

# Chat con IA
curl -X POST "http://127.0.0.1:8000/api/materias/1/ia/chat" \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "mensaje": "¿Cuál es el tema de esta materia?",
    "nivel": "basico"
  }'
```

## 📱 Desde tu App (Vue/React)

```javascript
// Obtener contenido
const response = await fetch('/api/materias/1/ia/pantalla-dinamica?nivel=basico', {
  headers: { 'Authorization': `Bearer ${token}` }
});
const data = await response.json();
console.log(data.data.introduccion);

// Chat
const chatResponse = await fetch('/api/materias/1/ia/chat', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    mensaje: '¿Puedes explicarme este concepto?',
    nivel: 'basico',
    historial: []
  })
});
const chatData = await chatResponse.json();
console.log(chatData.data.respuesta);
```

## ✅ Verifica que Todo Funciona

```bash
# Ver modelos disponibles en Ollama
curl http://127.0.0.1:11434/api/tags

# Ver logs del servidor Laravel
tail -f storage/logs/laravel.log
```

## 🐛 Algo No Funciona?

| Problema | Solución |
|----------|----------|
| "Connection refused" | Asegúrate que `ollama serve` esté ejecutándose |
| "Model not found" | Corre `ollama pull llama3.1` |
| Respuestas lentas | Cambia a `mistral` o aumenta `OLLAMA_TIMEOUT` |
| JSON inválido | El servicio tiene fallback automático |

## 📚 Próximos Pasos

1. Lee [AI_AGENT_README.md](./AI_AGENT_README.md) para documentación completa
2. Revisa [FRONTEND_EXAMPLES.js](./FRONTEND_EXAMPLES.js) para ejemplos de código
3. Integra los endpoints en tu app
4. ¡Disfruta del tutor de IA!

## 🎓 Características que Tienes Ahora

✅ Generación automática de contenido educativo  
✅ Chat interactivo con IA  
✅ Múltiples niveles de dificultad  
✅ Soporte para PDFs  
✅ Historial de conversación  
✅ Sugerencias automáticas  
✅ Totalmente local (sin internet)  

---

**¿Necesitas ayuda?** Revisa los archivos de documentación en el proyecto.

**Última actualización:** Mayo 2026
