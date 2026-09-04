import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
  interface Window {
    Echo?: Echo<any>
    Pusher: typeof Pusher
  }
}

const key = import.meta.env.VITE_REVERB_APP_KEY

// Reverb sozlanmagan bo'lsa Echo'ni umuman yaratmaymiz: kalitsiz `new Echo()`
// pusher-js ichida xato tashlab, butun ilovani oq ekranga olib boradi.
// Echo'dan foydalanadigan sahifalar `window.Echo` mavjudligini tekshiradi.
if (key) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

  window.Pusher = Pusher

  window.Echo = new Echo({
    broadcaster: 'reverb',
    key,
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
} else if (import.meta.env.DEV) {
  console.warn('[echo] VITE_REVERB_APP_KEY sozlanmagan — realtime o\'chirilgan.')
}
