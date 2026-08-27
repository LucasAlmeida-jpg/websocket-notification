# Notification System

Mini rede social com notificações em tempo real, construída com **Laravel 11**, **Nuxt 3**, **Laravel Reverb** (WebSockets) e **Web Push** (VAPID).

## Stack

| Camada | Tecnologia |
|--------|-----------|
| API | Laravel 11 + Sanctum |
| Frontend | Nuxt 3 + Vue 3 + Tailwind CSS |
| Banco | SQLite (troque por MySQL/Postgres em produção) |
| WebSocket | Laravel Reverb |
| Web Push | minishlink/web-push (VAPID) |
| Queue | Laravel Queue (database driver) |

---

## Arquitetura de notificações

```
[Usuário A] → curtir / comentar / seguir / mencionar
     │
     ├─── 1. SocialNotification (via queue) ─── notifications table (DB)
     │
     └─── 2. NotificationCreated (broadcast) ──► Reverb WebSocket ──► [Usuário B frontend]
```

Quando o usuário B está conectado via WebSocket, recebe a notificação em tempo real.
Quando offline, encontra a notificação via `GET /api/notifications`.

---

## Instalação

### Backend

```bash
cd backend

# 1. Instalar dependências
composer install

# 2. Configurar .env
cp .env.example .env
php artisan key:generate

# 3. Rodar migrations
php artisan migrate

# 4. Gerar chaves VAPID para Web Push
php artisan vapid:generate
```

### Frontend

```bash
cd frontend

npm install
```

---

## Executar a aplicação

Na raiz do projeto, rode o script que sobe todos os serviços de uma vez:

```bash
./start.sh
```

Isso inicia em paralelo:

| Processo | Endereço |
|----------|----------|
| Laravel API | http://localhost:8000 |
| Reverb WebSocket | ws://localhost:8080 |
| Queue Worker | — |
| Nuxt Frontend | http://localhost:3000 |

Pressione `Ctrl+C` para derrubar tudo.

---

## Endpoints da API

### Autenticação

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/register` | Criar conta |
| POST | `/api/login` | Login → retorna token |
| POST | `/api/logout` | Logout (token Bearer) |
| GET | `/api/me` | Dados do usuário logado |
| PATCH | `/api/me` | Atualizar perfil (nome, bio, avatar) |

### Posts

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/feed` | Feed principal (paginado) |
| POST | `/api/posts` | Criar post ou reply |
| GET | `/api/posts/{id}` | Ver post + replies |
| DELETE | `/api/posts/{id}` | Deletar post (apenas dono) |
| POST | `/api/posts/{id}/like` | Curtir / descurtir |
| POST | `/api/posts/{id}/repost` | Repostar / desfazer |
| POST | `/api/posts/{id}/send` | Enviar post para um seguidor |

### Usuários

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/users?q=` | Buscar usuários |
| GET | `/api/users/{id}` | Ver perfil + posts |
| POST | `/api/users/{id}/follow` | Seguir / deixar de seguir |
| GET | `/api/following` | Lista de quem você segue |

### Notificações

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/notifications` | Listar notificações (paginado) |
| GET | `/api/notifications/unread-count` | Quantidade não lida |
| PATCH | `/api/notifications/{id}/read` | Marcar uma como lida |
| PATCH | `/api/notifications/read-all` | Marcar todas como lidas |
| DELETE | `/api/notifications/{id}` | Deletar notificação |

### Web Push

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/push/vapid-key` | Buscar chave VAPID pública |
| POST | `/api/push` | Registrar subscription push |
| DELETE | `/api/push` | Remover subscription |

---

## Exemplos de uso (curl)

### Registrar e fazer login
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Alice","email":"alice@ex.com","password":"password","password_confirmation":"password"}'

curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@ex.com","password":"password"}'
# → {"user":{...},"token":"1|abc..."}
```

### Criar um post
```bash
curl -X POST http://localhost:8000/api/posts \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body":"Olá mundo!"}'
```

### Curtir um post
```bash
curl -X POST http://localhost:8000/api/posts/1/like \
  -H "Authorization: Bearer SEU_TOKEN"
```

### Ver notificações
```bash
curl http://localhost:8000/api/notifications \
  -H "Authorization: Bearer SEU_TOKEN"
```

---

## Conectar ao WebSocket (frontend)

```javascript
import Pusher from 'pusher-js';

const pusher = new Pusher('local-key', {
    wsHost: '127.0.0.1',
    wsPort: 8080,
    forceTLS: false,
    cluster: 'mt1',
    authEndpoint: 'http://localhost:8000/broadcasting/auth',
    auth: {
        headers: {
            Authorization: 'Bearer ' + SEU_TOKEN,
        },
    },
});

const channel = pusher.subscribe('private-users.1');

channel.bind('notification.created', (data) => {
    console.log('Nova notificação:', data.notification);
});
```

---

## Usuários de teste (seeds)

| Nome | Email | Senha |
|------|-------|-------|
| Alice | alice@example.com | password |
| Bob | bob@example.com | password |
| Carol | carol@example.com | password |
