/**
 * Ejemplos de cómo usar el Agente IA desde el Frontend
 * Framework: Vue.js / Nuxt / React (ejemplos adaptables)
 */

// ============================================================================
// 1. OBTENER CONTENIDO DINÁMICO GENERADO POR IA
// ============================================================================

export async function obtenerPantallaDinamica(materiaId, nivel = 'basico', token) {
  try {
    const response = await fetch(
      `/api/materias/${materiaId}/ia/pantalla-dinamica?nivel=${nivel}`,
      {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      }
    );

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();
    
    if (data.success) {
      return data.data;
    } else {
      throw new Error(data.message || 'Error al obtener contenido');
    }
  } catch (error) {
    console.error('Error en pantallaDinamica:', error);
    throw error;
  }
}

// Uso:
/*
const contenido = await obtenerPantallaDinamica(5, 'intermedio', token);
console.log(contenido.introduccion);
console.log(contenido.contenidos);
console.log(contenido.ejercicios_interactivos);
*/

// ============================================================================
// 2. CHAT SIMPLE CON EL AGENTE IA
// ============================================================================

export async function enviarMensajeChat(materiaId, mensaje, nivel = 'basico', token) {
  try {
    const response = await fetch(
      `/api/materias/${materiaId}/ia/chat`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          mensaje: mensaje,
          nivel: nivel,
          historial: [],
        }),
      }
    );

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();
    
    if (data.success) {
      return data.data;
    } else {
      throw new Error(data.message || 'Error en el chat');
    }
  } catch (error) {
    console.error('Error en chat:', error);
    throw error;
  }
}

// Uso:
/*
const respuesta = await enviarMensajeChat(5, '¿Cuál es el tema principal?', 'basico', token);
console.log(respuesta.respuesta);
console.log(respuesta.sugerencias);
*/

// ============================================================================
// 3. CHAT CON HISTORIAL (CONVERSACIÓN)
// ============================================================================

export async function chatConHistorial(materiaId, mensaje, historial, nivel = 'basico', token) {
  try {
    const response = await fetch(
      `/api/materias/${materiaId}/ia/chat`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          mensaje: mensaje,
          nivel: nivel,
          historial: historial,
        }),
      }
    );

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();
    
    if (data.success) {
      return data.data;
    } else {
      throw new Error(data.message || 'Error en el chat');
    }
  } catch (error) {
    console.error('Error en chat:', error);
    throw error;
  }
}

// Uso:
/*
const historial = [
  {
    role: 'user',
    content: '¿Qué es una variable?',
  },
  {
    role: 'assistant',
    content: 'Una variable es un contenedor que almacena un valor...',
  },
];

const respuesta = await chatConHistorial(
  5,
  '¿Puedo usar múltiples variables en una ecuación?',
  historial,
  'intermedio',
  token
);
console.log(respuesta.respuesta);
*/

// ============================================================================
// 4. COMPONENTE VUE DE EJEMPLO
// ============================================================================

/*
<template>
  <div class="ia-tutore">
    <!-- Selector de Nivel -->
    <div class="nivel-selector">
      <button 
        v-for="n in ['basico', 'intermedio', 'avanzado']"
        :key="n"
        @click="cambiarNivel(n)"
        :class="{ active: nivelActual === n }"
      >
        {{ n.charAt(0).toUpperCase() + n.slice(1) }}
      </button>
    </div>

    <!-- Contenido Generado -->
    <div class="contenido-generado" v-if="contenido">
      <h2>{{ contenido.titulo }}</h2>
      <p>{{ contenido.introduccion }}</p>
      
      <div class="contenidos">
        <h3>Contenidos</h3>
        <div v-for="(c, i) in contenido.contenidos" :key="i" class="contenido-item">
          <h4>{{ c.titulo }}</h4>
          <p>{{ c.descripcion }}</p>
        </div>
      </div>

      <div class="ejercicios">
        <h3>Ejercicios Interactivos</h3>
        <div v-for="(e, i) in contenido.ejercicios_interactivos" :key="i" class="ejercicio-item">
          <h4>{{ e.titulo }}</h4>
          <p>{{ e.descripcion }}</p>
          <span class="tipo">{{ e.tipo }}</span>
        </div>
      </div>
    </div>

    <!-- Chat -->
    <div class="chat-container">
      <div class="historial">
        <div v-for="(msg, i) in historialChat" :key="i" :class="msg.role">
          <p>{{ msg.content }}</p>
        </div>
      </div>

      <div class="input-area">
        <input 
          v-model="mensajeNuevo"
          @keyup.enter="enviarMensaje"
          placeholder="Pregunta al tutor de IA..."
          class="input"
        />
        <button @click="enviarMensaje" class="btn-enviar">
          Enviar
        </button>
      </div>

      <div v-if="sugerencias.length" class="sugerencias">
        <p>Sugerencias:</p>
        <button 
          v-for="(s, i) in sugerencias" 
          :key="i"
          @click="mensajeNuevo = s; enviarMensaje()"
          class="sugerencia-btn"
        >
          {{ s }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  materiaId: Number,
  token: String,
});

const nivelActual = ref('basico');
const contenido = ref(null);
const historialChat = ref([]);
const mensajeNuevo = ref('');
const sugerencias = ref([]);
const cargando = ref(false);

onMounted(async () => {
  await cargarContenido();
});

async function cargarContenido() {
  try {
    cargando.value = true;
    const respuesta = await fetch(
      `/api/materias/${props.materiaId}/ia/pantalla-dinamica?nivel=${nivelActual.value}`,
      {
        headers: {
          'Authorization': `Bearer ${props.token}`,
        },
      }
    );
    
    const data = await respuesta.json();
    if (data.success) {
      contenido.value = data.data;
    }
  } catch (error) {
    console.error('Error:', error);
  } finally {
    cargando.value = false;
  }
}

function cambiarNivel(nivel) {
  nivelActual.value = nivel;
  cargarContenido();
}

async function enviarMensaje() {
  if (!mensajeNuevo.value.trim()) return;

  // Agregar mensaje del usuario
  historialChat.value.push({
    role: 'user',
    content: mensajeNuevo.value,
  });

  const mensaje = mensajeNuevo.value;
  mensajeNuevo.value = '';

  try {
    cargando.value = true;
    const respuesta = await fetch(
      `/api/materias/${props.materiaId}/ia/chat`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${props.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          mensaje: mensaje,
          nivel: nivelActual.value,
          historial: historialChat.value.slice(0, -1), // Excluir el último (que acabamos de agregar)
        }),
      }
    );

    const data = await respuesta.json();
    if (data.success) {
      // Agregar respuesta del IA
      historialChat.value.push({
        role: 'assistant',
        content: data.data.respuesta,
      });

      sugerencias.value = data.data.sugerencias || [];
    }
  } catch (error) {
    console.error('Error:', error);
    historialChat.value.pop(); // Remover el mensaje si hubo error
  } finally {
    cargando.value = false;
  }
}
</script>

<style scoped>
.ia-tutor {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
}

.nivel-selector {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.nivel-selector button {
  padding: 10px 15px;
  border: 2px solid #ddd;
  border-radius: 5px;
  cursor: pointer;
  transition: all 0.3s;
}

.nivel-selector button.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.contenido-generado {
  background: #f5f5f5;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 30px;
}

.chat-container {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 20px;
  background: white;
}

.historial {
  height: 300px;
  overflow-y: auto;
  margin-bottom: 20px;
  padding: 10px;
  border: 1px solid #eee;
  border-radius: 5px;
}

.historial > div {
  margin-bottom: 10px;
  padding: 10px;
  border-radius: 5px;
}

.historial > div.user {
  background: #e3f2fd;
  text-align: right;
}

.historial > div.assistant {
  background: #f3e5f5;
}

.input-area {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
}

.input {
  flex: 1;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 14px;
}

.btn-enviar {
  padding: 10px 20px;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.btn-enviar:hover {
  background: #0056b3;
}

.sugerencias {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.sugerencia-btn {
  padding: 8px 12px;
  background: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 20px;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s;
}

.sugerencia-btn:hover {
  background: #e0e0e0;
}
</style>
*/

// ============================================================================
// 5. COMPOSABLE PARA VUE 3
// ============================================================================

export function useIAAgente(materiaId, token) {
  const contenido = ref(null);
  const historialChat = ref([]);
  const cargando = ref(false);
  const error = ref(null);
  const nivelActual = ref('basico');

  async function cargarPantallaDinamica(nivel = 'basico') {
    try {
      cargando.value = true;
      error.value = null;
      nivelActual.value = nivel;

      const respuesta = await fetch(
        `/api/materias/${materiaId}/ia/pantalla-dinamica?nivel=${nivel}`,
        {
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        }
      );

      const data = await respuesta.json();
      if (data.success) {
        contenido.value = data.data;
      } else {
        throw new Error(data.message);
      }
    } catch (err) {
      error.value = err.message;
      console.error('Error:', err);
    } finally {
      cargando.value = false;
    }
  }

  async function enviarPregunta(mensaje) {
    try {
      cargando.value = true;
      error.value = null;

      const respuesta = await fetch(
        `/api/materias/${materiaId}/ia/chat`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            mensaje,
            nivel: nivelActual.value,
            historial: historialChat.value,
          }),
        }
      );

      const data = await respuesta.json();
      if (data.success) {
        historialChat.value.push(
          { role: 'user', content: mensaje },
          { role: 'assistant', content: data.data.respuesta }
        );
        return data.data;
      } else {
        throw new Error(data.message);
      }
    } catch (err) {
      error.value = err.message;
      console.error('Error:', err);
    } finally {
      cargando.value = false;
    }
  }

  function resetearChat() {
    historialChat.value = [];
  }

  return {
    contenido,
    historialChat,
    cargando,
    error,
    nivelActual,
    cargarPantallaDinamica,
    enviarPregunta,
    resetearChat,
  };
}

// Uso del composable:
/*
const { 
  contenido, 
  historialChat, 
  cargarPantallaDinamica, 
  enviarPregunta 
} = useIAAgente(materiaId, token);

onMounted(() => cargarPantallaDinamica('basico'));
*/
