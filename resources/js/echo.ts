import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

declare global {
  interface Window {
    Echo: Echo<any>
    Pusher: typeof Pusher
  }
}

window.Pusher = Pusher

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
  enabledTransports: ['ws', 'wss'],
  authEndpoint: '/broadcasting/auth',
  auth: {
    headers: {
      ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      'X-Requested-With': 'XMLHttpRequest',
    },
  },
})