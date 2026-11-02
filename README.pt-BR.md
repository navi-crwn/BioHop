<p align="center">🌐 <a href="README.md">English</a> · <a href="README.zh-CN.md">简体中文</a> · <a href="README.de.md">Deutsch</a> · <a href="README.id.md">Bahasa Indonesia</a> · <a href="README.ja.md">日本語</a> · <b>Português (Brasil)</b> · <a href="README.es.md">Español</a> · <a href="README.fr.md">Français</a> · <a href="README.ru.md">Русский</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Licença MIT"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="workflow tests"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/hero-en-dark.webp"><img src="docs/readme/hero-en-light.webp" alt="BioHop: one page with your links, embeds, socials and a QR code" width="100%"></picture></p>

<p align="center"><b>Uma página com os seus links, embeds, redes sociais e um código QR — publicada em <code>hel.ink/b/{slug}</code>.</b></p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#execute-você-mesmo">Execute você mesmo</a> ·
  <a href="#funcionalidades">Funcionalidades</a> ·
  <a href="#perguntas-frequentes">Perguntas frequentes</a> ·
  <a href="#segurança">Segurança</a> ·
  <a href="#licença">Licença</a>
</p>

---

Instale com um comando (ele verifica seu computador primeiro, faz poucas perguntas e pode instalar o hub HOP ID para você):

```bash
curl -fsSL https://raw.githubusercontent.com/navi-crwn/BioHop/main/install.sh | bash
```

A partir de um clone: `./install.sh` · atualizar: `./update.sh` · remover: `./uninstall.sh`. Use `--yes` para aceitar todos os padrões; `--help` lista as opções.

## O que é o BioHop?

O BioHop é uma aplicação link-in-bio: você monta uma única página com os seus links, imagens, embeds e redes sociais, escolhe um tema e publica em `hel.ink/b/{slug}` — com um código QR pronto para compartilhar.

É a metade de bio do que antes era um único app (hoje [LinkHop](https://github.com/navi-crwn/LinkHop), o encurtador de URL). As contas ficam no hub ([HEL.ink](https://github.com/navi-crwn/HEL.ink)): aqui não há registro nem senha — você entra uma vez com o seu **HOP ID** e o app já o reconhece. O plano por trás da divisão está documentado em [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md) (a fase D é este repositório).

<a id="funcionalidades"></a>

## Funcionalidades

- **Páginas de bio públicas em `hel.ink/b/{slug}`, cada uma com código QR e contagem de cliques.**
- **Um editor em `/bio` para páginas, links, upload de imagens/avatar/ícones/logo do QR, pré-visualização ao vivo, duplicação e análises por página.**
- **Temas predefinidos (light, dark, minimal, neon e muitos outros) compartilhados pelo editor, pela pré-visualização e pela página pública.**
- **Sem cadastro local nem senhas: você entra uma vez com o seu HOP ID no hub.**
- **Análises de cliques com país (GeoIP), além de detecção de dispositivo, navegador e bots.**
- **"Shorten this URL" nos blocos via API do LinkHop com o token de serviço do próprio membro; se o LinkHop estiver fora do ar, o bloco mantém a URL original.**
- **Segurança de URL/conteúdo: `abuse:scan` com a `domain_blacklist` compartilhada (hop/safety), agendado a cada hora.**
- **Limpeza diária dos dados de cliques (`privacy:prune`).**

<a id="execute-você-mesmo"></a>

## Execute você mesmo

Você precisa de PHP 8.3, Composer e Node 20+. Os pacotes compartilhados `hop/auth`, `hop/ui` e `hop/safety` vêm dos repositórios GitHub correspondentes (`navi-crwn/hop-auth`, `hop-ui`, `hop-safety`, branch `main`, listados como repositórios `vcs` no `composer.json`).

```bash
git clone git@github.com:navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

**Configure antes de migrar.** Abra o `.env` (o `.env.example` documenta todas as chaves; os comentários dele são a referência) e defina pelo menos:

| Chave | O que é |
|---|---|
| `APP_URL` / `PUBLIC_URL` | O endereço deste app e o endereço público onde as páginas são servidas |
| `HOP_HUB_URL` | O endereço do seu hub (o login com HOP ID vai para lá) |
| `HOP_APP` | O identificador deste app no hub: `biohop` |
| `HOP_SSO_SECRET` ou `HOP_SSO_SECRET_BIOHOP` | O segredo SSO compartilhado com o hub, 32 caracteres ou mais |
| `LINKHOP_URL` | O endereço do seu LinkHop (ativa o "shorten this URL" nos blocos) |
| `DB_CONNECTION`, `DB_*`, `DB_MIGRATIONS_TABLE` | Configurações do banco de dados; `DB_MIGRATIONS_TABLE=migrations_bio` mantém o registro de migrações deste app separado |

```bash
php artisan migrate      # registra apenas em migrations_bio
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

**Banco de dados.** O BioHop é projetado para compartilhar um único banco MySQL com o hub e o LinkHop (cada app mantém o próprio registro de migrações: `migrations_bio` aqui). Execute primeiro as migrações do hub, para que tabelas como `users`, `roles`, `api_tokens` e `domain_blacklist` existam. Este é o design documentado; a compatibilidade de migrações entre as versões dos apps não é verificada neste README, então confirme antes de apontar o BioHop para um banco de dados que importa.

**Os testes** rodam em sqlite em memória; as tabelas de outros apps são criadas por `database/migrations/testing/` (apenas para testes).

```bash
php artisan test
vendor/bin/pint --test
```

## Login (HOP ID)

Entre com o seu HOP ID no hub — sem cadastro nem senha aqui. O login e o editor usam SSO padrão, que exige que as configurações `HOP_HUB_URL`, `HOP_APP=biohop` e o segredo SSO acima estejam prontos antes de você entrar. Abrir `/bio` leva você ao hub e de volta. Sair encerra a sua sessão aqui e redireciona para o logout do HOP ID.

As páginas de bio públicas (`/b/{slug}`) não exigem login. O BioHop é projetado para funcionar junto com o hub — não é um app independente.

<a id="perguntas-frequentes"></a>

## Perguntas frequentes

**O que acontece com os meus dados de cliques?** A contagem de cliques existe para mostrar o desempenho da sua página, e o comando `privacy:prune` remove os dados de cliques diariamente. Nenhum arquivo de cliques de longo prazo é mantido de propósito.

**A verificação de segurança roda sozinha?** Sim. O `abuse:scan` é agendado a cada hora e verifica páginas e blocos de bio contra a `domain_blacklist` compartilhada do hop/safety. São mecanismos, não garantias: nenhuma verificação automatizada promete detectar tudo.

**Posso usar o BioHop sem o hub?** Não. Editar exige um HOP ID no hub, e não há cadastro local nem senha. As páginas de bio públicas não exigem login, mas o app é projetado para funcionar junto com o hub, e o BioHop não pode garantir que as rotas públicas nunca contatem o hub.

**Posso hospedar por minha conta?** Sim — licença MIT, e a seção acima é o guia de início rápido completo. Você traz o seu próprio hub, LinkHop e banco de dados.

<a id="família"></a>

## A família HOP

O BioHop faz parte da família **HOP** de apps de código aberto auto-hospedáveis por trás do [hel.ink](https://hel.ink).

| App | O que faz | Endereço | Código |
|---|---|---|---|
| **Hub** | Portal de contas, login com HOP ID | `hel.ink` · `hub.hel.ink` | [navi-crwn/HEL.ink](https://github.com/navi-crwn/HEL.ink) |
| **LinkHop** | Encurtador de URL | `link.hel.ink` · `hel.ink/l/{slug}` | [navi-crwn/LinkHop](https://github.com/navi-crwn/LinkHop) |
| **BioHop** | Páginas link-in-bio | `bio.hel.ink` · `hel.ink/b/{slug}` | este repositório |
| **PixelHop** | Hospedagem de imagens e mídia | `pixel.hel.ink` · `p.hel.ink` | [navi-crwn/PixelHop](https://github.com/navi-crwn/PixelHop) |
| **NoteHop** | Notas e documentos privados | `note.hel.ink` · `n.hel.ink` | [navi-crwn/NoteHop](https://github.com/navi-crwn/NoteHop) |

<a id="segurança"></a>

## Segurança

Encontrou uma vulnerabilidade? Reporte-a de forma privada pelos security advisories do GitHub deste repositório: [github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories). Por favor, não abra issues públicas para relatos de segurança e não publique segredos, tokens ou detalhes de exploração em locais públicos. Consulte o [SECURITY.md](SECURITY.md) para detalhes.

<a id="licença"></a>

## Licença

[MIT](LICENSE) — como o resto da família HOP. As licenças de terceiros estão listadas no arquivo [LICENSE](LICENSE).

---

<p align="center"><sub>BioHop · parte da família HOP · <a href="https://hel.ink">hel.ink</a></sub></p>
