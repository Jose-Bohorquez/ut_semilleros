<template>
  <div class="login">
    <h2>Iniciar Sesión</h2>
    <form @submit.prevent="login">
      <input v-model="email" type="email" placeholder="Correo" required />
      <input v-model="password" type="password" placeholder="Contraseña" required />
      <button type="submit" :disabled="loading">
        {{ loading ? 'Entrando...' : 'Entrar' }}
      </button>
      <p v-if="error" class="error">{{ error }}</p>
    </form>
  </div>
</template>

<script setup>
import axios from 'axios'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

// Ruta base de tu API Laravel
const API = '' // vacío = mismo dominio en producción

const router = useRouter()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

const login = async () => {
  error.value = ''
  loading.value = true
  try {
    const { data } = await axios.post(`${API}/api/login`, {
      email: email.value,
      password: password.value
    })
    localStorage.setItem('token', data.token)
    router.push({ name: 'Dashboard' })
  } catch (e) {
    error.value = 'Credenciales incorrectas o error de servidor'
  } finally {
    loading.value = false
  }
}
</script>

<style>
.login {
  max-width: 320px;
  margin: 100px auto;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
input, button {
  padding: 10px;
}
.error {
  color: red;
}
</style>
