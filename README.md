# TdOkei — Gestão de Solicitações Escolares

Sistema web de fila de pedidos entre escolas municipais e a Secretaria de Ensino. Diretores e equipes das escolas solicitam **produtos** (papelaria, higiene, limpeza) e **serviços** (manutenção, reparos), e a Secretaria acompanha, atende e confirma a entrega com QR Code.

---

## Funcionalidades

**Escolas (diretor, escriturário, estagiário)**
- Solicitações de produto (com itens e quantidades) e de serviço (com foto do problema)
- Acompanhamento do status com linha do tempo e comentários na própria solicitação
- Confirmação de entrega por **QR Code**, com registro de itens em falta ou destino incorreto
- Sugestão de novos produtos para o catálogo
- **Oportunidades** enviadas pela Secretaria (aceitar ou rejeitar, com quantidade quando pedida) — somente diretor e escriturário

**Secretaria (administradores)**
- Fila por categoria (produtos ou serviços), com mudança de status, rejeição com motivo e geração do QR Code de entrega
- Envio de oportunidades para escolas escolhidas

**Superadmin**
- Cadastro de escolas, usuários, produtos e unidades de medida
- Auditoria completa (ações e logins), gestão de imagens (filtro por idade, download e exclusão em massa) e sugestões de produtos

**Secretário**
- Somente histórico e relatórios

---

## Perfis de acesso

| Perfil | O que faz |
|---|---|
| `superadmin` | Acesso total e cadastros |
| `administrador` | Gerencia a fila de **uma** categoria (produto ou serviço) |
| `diretor` | Solicita, comenta, confirma entregas e responde oportunidades |
| `escriturario` | Mesmas permissões do diretor, vários por escola |
| `estagiario` | Como o escriturário, **sem acesso a Oportunidades**; o nome dele aparece nos relatórios |
| `secretario` | Histórico e relatórios (somente leitura) |

---

## Início rápido

Requisitos: **Docker** com Compose v2, `bash` e `openssl`.

```bash
git clone https://github.com/TdOkei/chamados-core-php.git
cd chamados-core-php
./install.sh
```

O instalador pergunta a porta e o nome do banco, gera senhas aleatórias no `.env`, cria o banco com dados de exemplo e sobe os containers. Ao final ele mostra o endereço de acesso.

Para rodar sem perguntas: `WEB_PORT=8081 DB_NAME=tdokei ./install.sh`

### Instalação manual

```bash
cp .env.example .env
# edite o .env: troque DB_PASSWORD e APP_SECRET (ex.: openssl rand -hex 32)
docker compose up -d --build --wait
```

### Usuários de exemplo

Todos usam a senha provisória **`Trocar@123`**:

| Usuário | Perfil |
|---|---|
| `admin` | superadmin |
| `adm.produtos` | administrador (produtos) |
| `adm.servicos` | administrador (serviços) |
| `secretario` | secretário |
| `diretor` | diretor da "Escola Exemplo" |
| `escriturario` | escriturário da "Escola Exemplo" |
| `estagiario` | estagiário da "Escola Exemplo" |

> **Troque as senhas no primeiro acesso e apague os usuários de exemplo antes de usar em produção.**

Os dados de exemplo (`docker/db/seed.sql`) só são carregados quando o banco é criado do zero. Para recomeçar: `docker compose down -v` apaga o banco e o `./install.sh` (ou `docker compose up -d`) o recria.

---

## Configuração (`.env`)

| Variável | Descrição |
|---|---|
| `WEB_PORT` | Porta publicada no servidor (padrão 8081) |
| `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASSWORD` | Conexão com o MariaDB (obrigatórias, sem valor padrão no código) |
| `APP_ENV` | `local` ou `production` (em `production` o cookie de sessão exige HTTPS) |
| `APP_DEBUG` | `true` mostra detalhes de erro; mantenha `false` fora do desenvolvimento |
| `APP_URL` | URL pública, usada nos links do QR Code |
| `APP_SECRET` | Segredo da aplicação (gerado pelo instalador) |
| `SESSION_NAME` | Nome do cookie de sessão |
| `UPLOADS_PATH` | Pasta das fotos dentro do container (padrão `/var/www/uploads`) |

O `.env` nunca vai para o Git. Fotos de serviço: JPG ou PNG, até **5 MB**.

---

## Regras de negócio importantes

- **Escola inativa fica congelada.** Usuários dela não conseguem entrar (mensagem neutra orientando a procurar o suporte); as solicitações existentes não podem ser criadas, canceladas, avançadas, rejeitadas nem concluídas por QR Code, e ela não recebe nem aceita oportunidades. Ao reativar a escola, tudo volta ao normal.
- **Administrador** só enxerga e altera solicitações da própria categoria.
- **Oportunidades** só são vistas e respondidas por diretor e escriturário.

### Fluxo de status

```
Pendente → Em Análise → Em Processamento → Despachado → Concluído
                                                      ↘ Item em Falta (entrega parcial)
                                                      ↘ Retornado (destino incorreto)
         → Rejeitado
         → Cancelado (apenas enquanto Pendente, pela escola)
```

---

## Tecnologia

- **Backend:** PHP 8.3 (camadas Repository / Service, PDO com prepared statements)
- **Banco:** MariaDB 11.4
- **Frontend:** HTML, CSS e JavaScript puros, com design system próprio
- **Servidor:** nginx 1.27 + PHP-FPM, tudo em Docker Compose
- **Segurança:** token CSRF, escape de saída contra XSS, bloqueio por tentativas de login, sessões de 4 h, auditoria

```
.
├── docker/                # Dockerfile do PHP, nginx.conf, php.ini, schema.sql e seed.sql
├── docker-compose.yml
├── install.sh             # instalador
├── public_html/
│   ├── public/            # raiz web (páginas, api, assets)
│   ├── src/               # Config, Helpers, Middleware, Repositories, Services
│   └── templates/         # layout-header e layout-footer
└── uploads/               # fotos de serviço (criada na instalação, fora do Git)
```

---

## Operação

**Backup**
```bash
docker compose exec -T db sh -c 'mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' > backup.sql
tar czf uploads-backup.tar.gz uploads/
```

**Atualizar:** `git pull && docker compose up -d --build --wait`

**Produção:** coloque o sistema atrás de HTTPS (proxy reverso), use `APP_ENV=production`, `APP_DEBUG=false` e senhas fortes, e restrinja o acesso ao `.env`.

---

## Contribuindo

Os commits seguem o padrão [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `refactor:`, `docs:`). Sugestões e correções são bem-vindas por issue ou pull request.

---

## Licença

Distribuído sob a licença **GNU AGPL-3.0**. Veja o arquivo [LICENSE](LICENSE).
