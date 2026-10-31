#!/usr/bin/env bash
# Instalador do TdOkei: cria o .env com segredos aleatorios e sobe os containers.
set -euo pipefail
cd "$(dirname "$0")"

command -v docker >/dev/null || { echo "Docker nao encontrado. Instale o Docker primeiro."; exit 1; }
docker compose version >/dev/null 2>&1 || { echo "Docker Compose v2 nao encontrado."; exit 1; }
command -v openssl >/dev/null || { echo "openssl nao encontrado."; exit 1; }

if [ -f .env ]; then
  echo ".env ja existe: mantendo a configuracao atual."
else
  [ -f .env.example ] || { echo ".env.example nao encontrado."; exit 1; }

  # perguntar VAR "Texto" padrao regex "Dica"
  # Pergunta de novo ate a resposta ser valida (modo interativo). Em modo
  # automatico (valor vindo do ambiente ou sem terminal) aborta com mensagem.
  perguntar() {
    local valor="${!1:-}"
    if [ -z "$valor" ] && [ -t 0 ]; then
      while true; do
        read -r -p "$2 [$3]: " valor
        valor="${valor:-$3}"
        [[ "$valor" =~ $4 ]] && break
        echo "  Valor invalido. $5"
        valor=""
      done
    fi
    valor="${valor:-$3}"
    [[ "$valor" =~ $4 ]] || { echo "$2 invalido: $valor. $5"; exit 1; }
    printf -v "$1" '%s' "$valor"
  }

  PORTA="${WEB_PORT:-}"
  NOME="${DB_NAME:-}"
  perguntar PORTA "Porta web"      8081   '^[0-9]+$'          "Use apenas numeros (ex.: 8081)."
  perguntar NOME  "Nome do banco"  tdokei '^[A-Za-z0-9_]+$'   "Use letras, numeros e _ (sem hifen ou espaco)."

  cp .env.example .env
  sed -i "s/^WEB_PORT=.*/WEB_PORT=${PORTA}/" .env
  sed -i "s#^APP_URL=.*#APP_URL=http://localhost:${PORTA}#" .env
  sed -i "s/^DB_NAME=.*/DB_NAME=${NOME}/" .env
  sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$(openssl rand -hex 16)/" .env
  sed -i "s/^APP_SECRET=.*/APP_SECRET=$(openssl rand -hex 32)/" .env
  # 644: o .env e montado no container e lido pelo PHP-FPM (www-data), que nao e o dono.
  chmod 644 .env
  echo ".env criado com senhas aleatorias."
fi

mkdir -p uploads/servicos

docker compose up -d --build --wait

PORTA="$(grep '^WEB_PORT=' .env | cut -d= -f2)"
echo
echo "TdOkei no ar: http://localhost:${PORTA}"
echo "Login inicial: admin / Trocar@123  (troque a senha no primeiro acesso)"
