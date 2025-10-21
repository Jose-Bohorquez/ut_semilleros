// Importamos las herramientas de Vue Router
import { createRouter, createWebHistory } from 'vue-router'

// Importamos las vistas (páginas)
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'

// Middleware básico: protege la ruta del dashboard si no hay token
const requireAuth = (to, from, next) => {
  const token = localStorage.getItem('token')
  if (!token) return next({ name: 'Login' }) // si no hay token, redirige al login
  next()
}

// Definimos las rutas del sitio
const routes = [
  { path: '/', name: 'Login', component: Login },
  { path: '/dashboard', name: 'Dashboard', component: Dashboard, beforeEnter: requireAuth },
]

// Creamos y exportamos el enrutador
export default createRouter({
  history: createWebHistory(),
  routes,
})
