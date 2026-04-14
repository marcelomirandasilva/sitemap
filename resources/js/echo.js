import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const chaveAplicacao = import.meta.env.VITE_REVERB_APP_KEY;
const hostConfigurado = import.meta.env.VITE_REVERB_HOST;
const portaConfigurada = import.meta.env.VITE_REVERB_PORT;
const esquemaConfigurado = import.meta.env.VITE_REVERB_SCHEME;
const hostnameAtual = window.location.hostname;
const paginaUsaHttps = window.location.protocol === 'https:';
const hostConfiguradoEhLocal = ['localhost', '127.0.0.1', '::1'].includes(hostConfigurado);
const hostnameAtualEhLocal = ['localhost', '127.0.0.1', '::1'].includes(hostnameAtual);

// Evita que o frontend tente abrir WebSocket em localhost quando a aplicação
// está sendo acessada por um domínio como sitemap.test ou genmap.app.
const hostWebSocket = hostConfiguradoEhLocal && !hostnameAtualEhLocal
    ? hostnameAtual
    : (hostConfigurado || hostnameAtual);

const esquemaWebSocket = esquemaConfigurado || (paginaUsaHttps ? 'https' : 'http');
const usaTls = esquemaWebSocket === 'https';
const portaWebSocket = Number(portaConfigurada || (usaTls ? 443 : 8080));

if (chaveAplicacao) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: chaveAplicacao,
        wsHost: hostWebSocket,
        wsPort: portaWebSocket,
        wssPort: portaWebSocket,
        forceTLS: usaTls,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        withCredentials: true,
    });
}
