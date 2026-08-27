#!/bin/bash

ROOT="$(cd "$(dirname "$0")" && pwd)"
BACKEND="$ROOT/backend"
FRONTEND="$ROOT/frontend"

# Colors
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
NC='\033[0m'

cleanup() {
  echo -e "\n${YELLOW}Shutting down...${NC}"
  kill 0
  exit 0
}
trap cleanup SIGINT SIGTERM

echo -e "${CYAN}Starting Laravel API${NC} → http://localhost:8000"
(cd "$BACKEND" && php artisan serve --port=8000 2>&1 | sed 's/^/[api] /') &

echo -e "${CYAN}Starting Reverb WebSocket${NC} → ws://localhost:8080"
(cd "$BACKEND" && php artisan reverb:start 2>&1 | sed 's/^/[ws]  /') &

echo -e "${CYAN}Starting Queue Worker${NC}"
(cd "$BACKEND" && php artisan queue:work --tries=3 2>&1 | sed 's/^/[queue] /') &

echo -e "${CYAN}Starting Nuxt Frontend${NC} → http://localhost:3000"
(cd "$FRONTEND" && npm run dev 2>&1 | sed 's/^/[web] /') &

echo -e "${GREEN}All services started. Press Ctrl+C to stop.${NC}\n"

wait
