# Notification System API

Mini sistema de notificações estilo rede social, com **Laravel 11**, **WebSockets** (Soketi) e **Web Push** (VAPID).

## Stack

| Camada | Tecnologia |
|--------|-----------|
| API | Laravel 11 + Sanctum |
| Banco | SQLite (troque por MySQL/Postgres em produção) |
| WebSocket | [Soketi](https://soketi.app/) — servidor Pusher-compatível |
| Web Push | minishlink/web-push (VAPID) |
| Queue | Laravel Queue (database driver) |

---

## Arquitetura de notificações

```
[Usuário A] → POST /api/notifications/send
     │
     ├─── 1. SocialNotification (via queue) ─── notifications table (DB)
     │
     └─── 2. NotificationCreated (broadcast) ──► Soketi WebSocket ──► [Usuário B frontend]
```

Quando o usuário B está conectado via WebSocket, recebe a notificação em tempo real.
Quando offline, encontra a notificação via `GET /api/notifications`.

---

## Instalação

```bash
# 1. Instalar dependências
composer install

# 2. Configurar .env (SQLite já está configurado por padrão)
cp .env.example .env
php artisan key:generate

# 3. Rodar migrations e seeds
php artisan migrate --seed

# 4. Gerar chaves VAPID para Web Push
php artisan vapid:generate
```

---

## Executar a aplicação

Você precisa de **3 processos** rodando simultaneamente:

### Terminal 1 — Laravel API
```bash
php artisan serve --port=8080
```

### Terminal 2 — WebSocket Server (Soketi)
```bash
# Instalar Soketi globalmente
npm install -g @soketi/soketi

# Iniciar com as configurações do projeto
soketi start --config=soketi.json
```

### Terminal 3 — Queue Worker (processa notificações assíncronas)
```bash
php artisan queue:work --queue=default
```

---

## Endpoints da API

### Autenticação

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/register` | Criar conta |
| POST | `/api/login` | Login → retorna token |
| POST | `/api/logout` | Logout (token Bearer) |
| GET | `/api/me` | Dados do usuário logado |

### Notificações (requer autenticação)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/notifications` | Listar notificações (paginado) |
| GET | `/api/notifications/unread-count` | Quantidade não lida |
| POST | `/api/notifications/send` | Enviar notificação para outro usuário |
| PATCH | `/api/notifications/{id}/read` | Marcar uma como lida |
| PATCH | `/api/notifications/read-all` | Marcar todas como lidas |
| DELETE | `/api/notifications/{id}` | Deletar notificação |

### Web Push (requer autenticação)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/push/vapid-key` | Buscar chave VAPID pública |
| POST | `/api/push` | Registrar subscription push |
| DELETE | `/api/push` | Remover subscription |
| POST | `/api/push/test` | Enviar push de teste |

---

## Exemplos de uso (curl)

### 1. Registrar e fazer login
```bash
curl -X POST http://localhost:8080/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Alice","email":"alice@ex.com","password":"password","password_confirmation":"password"}'

curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@ex.com","password":"password"}'
# → {"user":{...},"token":"1|abc..."}
```

### 2. Enviar notificação (Alice curte a foto do Bob)
```bash
curl -X POST http://localhost:8080/api/notifications/send \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "recipient_id": 2,
    "type": "like",
    "message": "Alice curtiu sua foto.",
    "resource_type": "post",
    "resource_id": 42
  }'
```

Tipos suportados: `like`, `comment`, `follow`, `mention`

### 3. Bob verifica notificações
```bash
curl http://localhost:8080/api/notifications \
  -H "Authorization: Bearer TOKEN_DO_BOB"
```

### 4. Marcar como lida
```bash
curl -X PATCH "http://localhost:8080/api/notifications/UUID-DA-NOTIFICACAO/read" \
  -H "Authorization: Bearer TOKEN_DO_BOB"
```

---

## Conectar ao WebSocket (frontend)

```javascript
import Pusher from 'pusher-js';

const pusher = new Pusher('local-key', {
    wsHost: '127.0.0.1',
    wsPort: 6001,
    forceTLS: false,
    cluster: 'mt1',
    authEndpoint: 'http://localhost:8080/broadcasting/auth',
    auth: {
        headers: {
            Authorization: 'Bearer ' + SEU_TOKEN,
        },
    },
});

// Escutar canal privado do usuário (ID do usuário logado)
const channel = pusher.subscribe('private-users.2');

channel.bind('notification.created', (data) => {
    console.log('Nova notificação em tempo real:', data.notification);
    // { type: "like", message: "Alice curtiu sua foto.", actor_name: "Alice", ... }
});
```

---

## Web Push (PWA / Service Worker)

```javascript
// 1. Buscar chave VAPID pública
const res = await fetch('/api/push/vapid-key', {
    headers: { Authorization: 'Bearer ' + token }
});
const { public_key } = await res.json();

// 2. Subscrever o Service Worker
const registration = await navigator.serviceWorker.ready;
const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: public_key,
});

// 3. Registrar no servidor
await fetch('/api/push', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + token },
    body: JSON.stringify({
        endpoint: subscription.endpoint,
        public_key: subscription.toJSON().keys.p256dh,
        auth_token: subscription.toJSON().keys.auth,
    }),
});
```

---

## Usuários de teste (seeds)

| Nome | Email | Senha |
|------|-------|-------|
| Alice | alice@example.com | password |
| Bob | bob@example.com | password |
| Carol | carol@example.com | password |
