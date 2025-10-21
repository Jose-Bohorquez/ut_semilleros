<template>
  <div class="dashboard">
    <h2>Bienvenido, {{ user?.name || '(sin nombre)' }}</h2>
    <p>Email: {{ user?.email }}</p>

    <button @click="logout">Cerrar sesión</button>
  </div>
</template>

<script setup>
import axios from 'axios'
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const API = ''
const router = useRouter()
const user = ref(null)

const getUser = async () => {
  const token = localStorage.getItem('token')
  if (!token) return router.push({ name: 'Login' })

  try {
    const { data } = await axios.get(`${API}/api/me`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    user.value = data
  } catch (err) {
    router.push({ name: 'Login' })
  }
}

const logout = () => {
  localStorage.removeItem('token')
  router.push({ name: 'Login' })
}

onMounted(getUser)
</script>

<style>
.dashboard {
  max-width: 600px;
  margin: 50px auto;
  text-align: center;
}
button {
  margin-top: 15px;
  padding: 10px;
}
</style>
