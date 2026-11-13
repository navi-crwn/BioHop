<p align="center">🌐 <a href="README.md">English</a> · <a href="README.zh-CN.md">简体中文</a> · <a href="README.de.md">Deutsch</a> · <a href="README.id.md">Bahasa Indonesia</a> · <a href="README.ja.md">日本語</a> · <b>Português (Brasil)</b> · <a href="README.es.md">Español</a> · <a href="README.fr.md">Français</a> · <a href="README.ru.md">Русский</a></p>

# BioHop

<p align="center">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="Licença MIT"></a>
  <a href="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml"><img src="https://github.com/navi-crwn/BioHop/actions/workflows/tests.yml/badge.svg?branch=main" alt="workflow de testes"></a>
</p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/dashboard-dark.webp"><img src="docs/readme/dashboard-light.webp" alt="O painel do BioHop: cliques desta semana em comparação com a anterior, uma lista de configuração e um cartão para cada página bio" width="100%"></picture></p>

<p align="center">Uma página para seus links, incorporações, redes sociais e um código QR. No serviço hospedado, ela é publicada em <code>hel.ink/b/{slug}</code>.</p>

<p align="center">
  <a href="https://bio.hel.ink">bio.hel.ink</a> ·
  <a href="#install">Instalação</a> ·
  <a href="#features">Recursos</a> ·
  <a href="#run-your-own">Rode o seu</a> ·
  <a href="#privacy">Privacidade</a> ·
  <a href="#faq">Perguntas frequentes</a> ·
  <a href="#security">Segurança</a> ·
  <a href="#license">Licença</a>
</p>

---

<a id="install"></a>

## Instalação

Um comando. Ele verifica seu computador primeiro, faz algumas perguntas e pode configurar o hub HOP ID para você:

```bash
curl -fsSL https://raw.githubusercontent.com/navi-crwn/BioHop/main/install.sh | bash
```

A partir de um clone: `./install.sh`. Atualize com `./update.sh` e remova com `./uninstall.sh`. Adicione `--yes` para aceitar todos os padrões; `--help` lista as opções. O instalador precisa de PHP 8.3 ou mais recente, Composer, Node 20 ou mais recente e cerca de 1 GB de espaço livre em disco. `--dry-run` mostra os passos sem executá-los.

> [!NOTE]
> O BioHop guarda suas contas em um hub HOP ID ([HEL.ink](https://github.com/navi-crwn/HEL.ink)). Não existe modo sem hub. O instalador configura um hub ao lado do BioHop (o padrão, cerca de um minuto) ou se conecta a um que você já usa. Para instalar o hub e vários apps de uma só vez, use o `install-family.sh` do repositório HEL.ink.

## O que é o BioHop

O BioHop é um app de link na bio. Você monta uma página com links, imagens, incorporações e ícones de redes sociais, escolhe um tema e a publica. O serviço hospedado em [bio.hel.ink](https://bio.hel.ink) é o painel, e as páginas públicas ficam em `hel.ink/b/{slug}`. No seu próprio servidor, uma página é servida em `{PUBLIC_URL}/b/{slug}`.

No BioHop não há cadastro nem senha. Você entra uma vez com seu HOP ID no hub, e o app passa a conhecer você. O BioHop começou como a metade "bio" de um app cuja outra metade hoje é o [LinkHop](https://github.com/navi-crwn/LinkHop), o encurtador de URLs. O plano dessa separação está em [`docs/planning/PLAN-pemisahan-aplikasi.md`](docs/planning/PLAN-pemisahan-aplikasi.md) (a Fase D é este repositório).

As capturas de tela abaixo vêm de uma instalação local com dados de exemplo inventados (a página de um estúdio de cerâmica). Elas não mostram pessoas nem contas reais.

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/editor-dark.webp"><img src="docs/readme/editor-light.webp" alt="O editor de páginas: a lista de blocos com um painel Quick Add à esquerda e, à direita, uma pré-visualização ao vivo da página em um celular" width="100%"></picture></p>

<p align="center"><picture><source media="(prefers-color-scheme: dark)" srcset="docs/readme/analytics-dark.webp"><img src="docs/readme/analytics-light.webp" alt="Análises da página em 30 dias: visualizações, cliques em links, taxa de cliques, cliques ao longo do tempo, dispositivos, links mais clicados, países e como os visitantes chegaram" width="100%"></picture></p>

<p align="center">
  <img src="docs/readme/public-page.webp" alt="Uma página bio pública em um celular: perfil, um chip de status, uma barra de abas Links e Shop, três botões e um FAQ" width="32%">
  &nbsp;
  <img src="docs/readme/public-shop.webp" alt="A aba Shop da mesma página: dois produtos em uma grade de duas colunas, um deles marcado como Sold out" width="32%">
</p>

<a id="features"></a>

## Recursos

### Páginas e editor

- Uma página pública em `/b/{slug}` com código QR (`/b/{slug}/qr` baixa um PNG) e um manifesto de web app instalável.
- Um assistente de seis passos para uma página nova: endereço, objetivo, plataformas, primeiros links, perfil e cores, com um celular ao vivo. O formulário simples continua disponível em `?classic=1`.
- Importação de uma página pública do Linktree como rascunho.
- O editor em `/bio` é uma página que rola normalmente, com uma pré-visualização ao vivo ao lado. Ele salva sozinho cerca de um segundo depois que você para de digitar e envia só o que mudou. `Ctrl/Cmd+S` salva na hora e `Ctrl/Cmd+K` abre uma paleta de comandos.
- Arraste para reordenar. Um modo de seleção mostra, oculta, arquiva ou exclui vários blocos de uma vez. Um bloco arquivado sai da página, mas mantém seus cliques.
- Versões: o editor salva uma versão antes de cada sessão de edição. Você também pode dar nome a uma versão e restaurar qualquer uma.
- As páginas podem ser duplicadas. Por padrão, uma conta tem 3 páginas e uma página tem 100 blocos (veja as configurações abaixo).
- Design: quatro layouts de cabeçalho (clássico, hero, banner com imagem de capa, cutout), 17 temas no seletor, plano de fundo em cor, gradiente ou imagem, estilos de botão e de bloco, fontes servidas pelo próprio app, CSS próprio por página e um botão para a linha "Powered by".
- A interface está disponível em English, 简体中文, Deutsch, Bahasa Indonesia, 日本語, Português (Brasil), Español, Français e Русский.

### Blocos

O BioHop tem 29 tipos de bloco: link, texto, título, divisor, espaçador, imagem, carrossel, PDF, áudio, vídeo, YouTube, Spotify, SoundCloud, música, mapa, contagem regressiva, FAQ, HTML, código, cartão de contato, e-mail, WhatsApp, link de loja de apps, barra de meta, produto, coleção, formulário de inscrição, enquete e feed RSS ou Atom.

- Um bloco de WhatsApp alterna entre até 10 números, em sequência ou por peso.
- Um bloco de app leva o visitante à loja que corresponde ao sistema dele.
- Qualquer bloco pode ter horário de publicação e de expiração.
- Um link pode enviar os visitantes para outro lugar conforme país, dispositivo, sistema ou hora do dia, e pode ter um PIN próprio.
- Uma coleção agrupa os blocos que estão abaixo dela, empilhados ou em uma grade de duas colunas.
- Players e mapas do YouTube, Spotify e dos outros serviços só carregam quando o visitante toca neles.

### Loja

Um bloco de produto tem imagem, nome, preço com moeda, preço antigo, variações com preço e estoque próprios, uma quantidade em estoque e um botão de esgotado. Ele pode preencher seus detalhes a partir do endereço da loja. Quando uma página tem um produto, a página pública mostra uma barra de abas Links e Shop, e a loja tem um endereço próprio em `/b/shop/{slug}`. O BioHop não processa pagamentos: um produto abre o próprio endereço em uma nova aba e conta como um clique.

### Configurações da página

Senha, aviso de conteúdo (nenhum, sensível ou adulto 18+), não listada, modo de privacidade (sem nenhuma contagem), um endereço para onde enviar os visitantes enquanto a página não estiver publicada, um chip de status, um botão flutuante, uma linha de rodapé, uma imagem para compartilhar, um favicon, título e descrição de SEO, parâmetros UTM opcionais nos links de saída e o pixel do Google Analytics, do Tag Manager, do Facebook ou do TikTok do próprio dono da página (veja [Privacidade](#privacy)).

### Formulários e webhooks

Um bloco de formulário de inscrição coleta nome, endereço de e-mail e número de telefone, e o visitante precisa marcar uma caixa de consentimento que cita a página. O dono baixa as entradas em CSV, apaga-as e define por quantos dias elas são guardadas (de 1 a 365, 90 por padrão). Uma página também pode enviar cada nova entrada, como JSON assinado, para um endereço que o dono escolhe. O agendador repete as entregas que falharam, e o editor mostra um registro de entregas com um botão de teste e um de reenvio.

### Análises

Por página: visualizações, cliques em links, taxa de cliques, cliques ao longo do tempo, dispositivos, países, links mais clicados, sites de origem, cliques recentes e como os visitantes chegaram (pelo próprio link, pelo código QR, por uma etiqueta NFC com `?src=nfc` ou pelo web app instalado). Cliques de bots não contam, um novo clique do mesmo visitante em até 10 minutos não conta, e um endereço conta no máximo 20 cliques por bloco em 10 minutos. O que o registro de cliques guarda está em [Privacidade](#privacy).

### Links curtos

"Shorten this URL" em um bloco chama a API do LinkHop com o token de serviço do próprio membro (escopo `link:create`, criado no primeiro uso e guardado em `bio_linkhop_tokens`). Se o LinkHop não puder ser alcançado, o bloco mantém seu endereço original e o editor avisa. Um link para hel.ink ou para um app HOP é mantido como foi digitado.

### Segurança e operação

- `abuse:scan` verifica páginas bio e blocos com as regras e a `domain_blacklist` compartilhada do `hop/safety`. Ele roda de hora em hora sobre o que mudou e uma vez por dia sobre tudo. As consultas ao Google Safe Browsing, ao abuse.ch URLhaus e ao Spamhaus DBL são opcionais.
- Os visitantes podem denunciar uma página em `/report-abuse` (o Cloudflare Turnstile protege o formulário quando suas chaves estão definidas).
- O trabalho de administração acontece no Imperium, o painel de controle do HOP. O BioHop responde às requisições assinadas `/api/hop/*` dele, e `/admin` envia os administradores para lá.
- Os uploads são recusados com um 507 quando o disco está quase cheio (veja [`docs/UPLOAD-STORAGE.md`](docs/UPLOAD-STORAGE.md)). As imagens enviadas são reduzidas para WebP. SVG só é aceito nos ícones próprios dos blocos e no logo do QR, e é reconstruído a partir de uma lista de permissões antes de ser guardado; a foto de perfil, as imagens, a capa, o fundo, a imagem de compartilhamento e o favicon aceitam PNG, JPG, GIF, WebP ou BMP.
- O leitor de feeds e o remetente de webhooks se conectam aos endereços que verificaram, de modo que um nome não possa resolver para outro endereço entre a verificação e a requisição.
- A política de segurança de conteúdo não cita nenhum host de CDN, de fontes ou de ícones. As bibliotecas vão junto com o app.
- A página inicial em bio.hel.ink é um tour interativo com a página oficial do HEL.ink dentro de um celular. Com "reduzir movimento" ativado, cada cena mostra seu estado final.

<a id="run-your-own"></a>

## Rode o seu

Você precisa de PHP 8.3 ou mais recente, Composer e Node 20 ou mais recente. Os pacotes compartilhados `hop/auth`, `hop/ui` e `hop/safety` vêm de seus repositórios no GitHub (`navi-crwn/hop-auth`, `hop-ui` e `hop-safety`, listados como repositórios `vcs` no `composer.json`). O instalador acima faz tudo isso por você. Para fazer à mão:

```bash
git clone https://github.com/navi-crwn/BioHop.git
cd BioHop
composer install
cp .env.example .env && php artisan key:generate
```

Abra o `.env` antes de migrar. O `.env.example` documenta cada chave. Defina pelo menos estas:

| Chave | O que é |
|---|---|
| `APP_URL`, `PUBLIC_URL` | O endereço do painel e o endereço em que as páginas públicas são servidas |
| `ASSET_URL` | Onde uploads e arquivos compilados são servidos; vazio significa `APP_URL` |
| `HOP_HUB_URL` | O endereço do seu hub (o login com HOP ID acontece lá) |
| `HOP_APP` | O slug deste app no hub: `biohop` |
| `HOP_SSO_SECRET` ou `HOP_SSO_SECRET_BIOHOP` | O segredo de SSO compartilhado com o hub, com 32 caracteres ou mais |
| `LINKHOP_URL` | Seu LinkHop, para "shorten this URL" (`LINKHOP_ENABLED=false` desliga) |
| `DB_CONNECTION`, `DB_*`, `DB_MIGRATIONS_TABLE` | Configurações do banco de dados. `DB_MIGRATIONS_TABLE=migrations_bio` mantém o registro de migrações deste app separado do registro do hub |
| `TRUSTED_PROXIES` | `cloudflare`, `*` ou uma lista de endereços de proxy, quando você roda atrás de um proxy |
| `BIO_MAX_PAGES`, `BIO_MAX_BLOCKS`, `BIO_SLUG_MIN_LENGTH` | Páginas por conta (3), blocos por página (100) e o menor endereço que um membro pode reivindicar (5) |
| `BIO_ALLOW_CUSTOM_CSS` | Defina como `false` para recusar CSS próprio e parar de enviar aos visitantes o CSS que já existe |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile para o formulário de denúncia. Em produção, a falta do secret faz o formulário recusar envios |

As chaves opcionais cobrem GeoIP (`GEOLITE_CITY_DB`, `GEOLITE_ASN_DB`, `IPINFO_API_KEY`, `ABSTRACTAPI_KEY`), consultas de reputação de links (`SAFE_BROWSING_KEY`, `URLHAUS_AUTH_KEY`, `SPAMHAUS_DBL`), a folga de espaço para uploads (`BIO_UPLOAD_MIN_FREE_MB`, `BIO_UPLOAD_MIN_FREE_PERCENT`) e a página oficial que a página inicial mostra (`HOP_OFFICIAL_BIO`, `HOP_OFFICIAL_VIDEO`, `HOP_OFFICIAL_MUSIC`, `HOP_OFFICIAL_CONTACT`). Um operador também pode alterar, em tempo de execução, o menor endereço, as páginas por conta, os blocos por página e o botão de CSS próprio, pelo Imperium.

```bash
php artisan migrate
php artisan storage:link
npm install && npm run build
php artisan serve        # http://localhost:8000
```

### Mantenha rodando

O BioHop precisa de um worker de fila e do agendador. Em produção, rode `php artisan queue:work --tries=3` e `php artisan schedule:work` sob systemd ou supervisor, ou use uma única linha de cron: `* * * * * cd /path/to/BioHop && php artisan schedule:run`. O `start.sh` do instalador inicia o servidor web, o worker e o agendador juntos, para um teste em uma única máquina.

O agendador executa estes comandos:

| Comando | Quando | O que faz |
|---|---|---|
| `abuse:scan --since=2h` | de hora em hora | Verifica páginas e blocos que mudaram |
| `abuse:scan --full --purge` | diariamente, 04:10 | Verifica tudo e apaga itens desativados por abuso há mais tempo que o prazo de limpeza |
| `abuse:feeds` | diariamente, 02:15 | Atualiza os feeds de ameaças do `hop/safety` |
| `privacy:prune` | diariamente, 03:30 | Apaga registros de cliques com mais de 30 dias, entradas de formulário que passaram do prazo de retenção e entregas de webhook com mais de 7 dias |
| `bio:webhooks:retry` | a cada minuto | Envia os webhooks cuja próxima tentativa chegou |

Outros comandos, executados à mão: `bio:official-page` preenche a página oficial do HEL.ink a partir do manifesto compartilhado, `bio:optimize-images` converte uploads antigos para WebP e `bio:unwrap-family-links` restaura links para sites HOP que tinham sido embrulhados em links curtos. Os três só relatam, a menos que você passe `--apply`.

### Atrás do Cloudflare: páginas públicas a partir da borda

Uma página bio pública é montada a cada requisição. Servida de um único lugar, isso é rápido perto do servidor e lento longe dele: medida a partir da origem, a página responde em 36 a 175 ms, enquanto um visitante na Ásia, cuja requisição passa por uma borda do Cloudflare até a Alemanha, espera de 0,3 a 3,2 s. Por isso o BioHop deixa uma borda guardar uma página simples por um minuto. Uma página publicada sem senha, aviso de conteúdo, formulário ou enquete, pedida por um visitante sem sessão, sem login e sem idioma escolhido, é enviada com `Cache-Control: public, s-maxage=60, max-age=0, stale-while-revalidate=300` e `Vary: Cookie`, e sem cookie. Toda outra resposta continua `no-cache, private`. A visita é contada por uma pequena requisição feita pela página (`POST /b/{slug}/view`, sem framework JavaScript) com as mesmas regras de uma vez por visitante de um clique, então uma página vinda da borda ainda conta; uma página que a própria origem serve conta a visita como antes.

O Cloudflare só guarda HTML quando uma Cache Rule manda. Crie uma para a zona:

- **Se**: o hostname é `hel.ink` ou `bio.hel.ink` (seus `PUBLIC_URL` e `APP_URL`), o caminho do URI começa com `/b/`, e o cabeçalho Cookie não contém `biohop_session` (seu `SESSION_COOKIE`), `hop_lang` nem `remember_web`.
- **Então**: cache eligibility *Eligible for cache*; Edge TTL *Use cache-control header if present, bypass cache if not*; Browser TTL *Respect origin*.

Uma regra que ignora o cache sempre que houver qualquer cookie também está correta, só que então visitantes que clicaram em um bloco (`hel_vid`) ou escolheram um idioma também deixam de usar o cache. O Cloudflare não honra `Vary: Cookie`, então a condição de cookie na regra é o que mantém a página de alguém logado ou uma página traduzida separada da cópia compartilhada.

Para que uma mudança apareça na hora e não ao fim do minuto, defina `CLOUDFLARE_ZONE_ID` e `CLOUDFLARE_API_TOKEN` (um token de API com *Zone → Cache Purge* para essa zona). Salvar uma página, publicá-la ou despublicá-la, ou adicionar, alterar ou remover um bloco então enfileira um purge por URL da página, da sua loja e das suas entradas por QR, NFC e app instalado nos dois hosts (`PurgeEdgeCache`, um job por salvamento; precisa do worker da fila). Um purge que falha é registrado no log e nada mais; o salvamento nunca espera pelo Cloudflare. Com as duas chaves vazias só os cabeçalhos são enviados e uma mudança aparece quando o minuto termina.

### Banco de dados

O BioHop foi projetado para compartilhar um banco de dados com o hub e o LinkHop. Cada app mantém seu próprio registro de migrações, aqui `migrations_bio`. Rode primeiro as migrações do hub, para que existam as tabelas que pertencem aos outros apps (entre elas `users`, `roles`, `api_tokens`, `ip_bans`, `domain_blacklist` e `abuse_flags`). Quando você instala com `--hub new` em SQLite, o BioHop usa o arquivo de banco de dados do hub. Esse desenho está documentado, mas a compatibilidade entre as versões dos apps não é verificada aqui; por isso, teste antes de apontar o BioHop para um banco de dados que importa para você.

### Atualizar e remover

O `./update.sh` primeiro copia o banco de dados para `backups/` (SQLite com `sqlite3 .backup`, MySQL com `mysqldump`; a cópia só pode ser lida por você). Depois coloca o site em modo de manutenção, baixa o código novo, instala os pacotes, compila o front end, migra e avisa os workers de fila para reiniciarem. Se um passo falhar antes da migração, ele restaura o código anterior. Se falhar durante ou depois da migração, deixa o site em modo de manutenção e mostra os passos manuais. `--no-backup` pula a cópia e `--force` atualiza mesmo que você tenha alterado arquivos rastreados. O `./uninstall.sh` mantém seus dados; `--purge` apaga também `.env`, `storage/` e `backups/`, depois que você digita o nome do site.

### Testes

Os testes rodam em SQLite na memória. As tabelas que pertencem a outros apps são criadas por `database/migrations/testing/`, e só quando a suíte de testes roda. A CI executa `composer audit`, o Pint e os testes em PHP 8.3.

```bash
php artisan test
vendor/bin/pint --test
```

## Login

Entre com seu HOP ID no hub. Abrir `/bio` leva você ao hub e de volta, o que exige que `HOP_HUB_URL`, `HOP_APP=biohop` e o segredo de SSO já estejam definidos. Sair encerra sua sessão aqui e depois no hub. A verificação de e-mail e as configurações da conta ficam no hub. As páginas bio públicas em `/b/{slug}` não exigem login, mas o BioHop foi feito para rodar junto com o hub e não é um app independente.

<a id="privacy"></a>

## Privacidade

Isto é o que o código faz hoje.

- O registro de cliques guarda a hora, o bloco, o país, o tipo de dispositivo, o navegador, o site de origem e como o visitante chegou. Ele guarda um hash com chave do endereço IP do visitante (HMAC-SHA256 com `APP_KEY`, reduzido a 40 caracteres), nunca o endereço em si. O `privacy:prune` apaga os registros de cliques depois de 30 dias. Votos de enquete e entradas de formulário também guardam apenas um hash com chave do endereço.
- Para descobrir o país, o endereço do visitante é consultado em um banco de dados GeoIP local, se você instalou um, e do contrário é enviado ao primeiro serviço web de geolocalização que responder (veja [`CREDITS.md`](CREDITS.md)). Essa consulta usa o endereço em texto puro, antes de ele virar hash.
- Para contar uma visualização de página uma vez por dia e limitar os cliques por endereço, o app mantém o endereço em texto puro dentro de chaves de cache por até 24 horas (visualizações) e 10 minutos (cliques). Com o cache em banco de dados padrão, essas chaves são linhas na tabela `cache`.
- Um cookie aleatório, `hel_vid`, dura um ano e é definido em um clique. Ele só ajuda a eliminar duplicatas na contagem de cliques. Quem lê pela primeira vez uma página pública simples não recebe sessão nem cookies. Membros logados têm linhas de sessão que incluem o endereço, como em qualquer sessão em banco de dados do Laravel.
- O modo de privacidade de uma página interrompe a contagem de visualizações e cliques dessa página.
- As denúncias de abuso guardam o endereço de e-mail de quem denuncia, o motivo e o endereço IP dessa pessoa na tabela compartilhada `abuse_reports`. Este repositório não limpa essa tabela.
- O BioHop não adiciona scripts próprios de publicidade ou de análise. Fontes, ícones e bandeiras são servidos pelo app, então o visitante não contata o Google nem uma CDN.
- O dono de uma página pode adicionar Google Analytics, Google Tag Manager, um pixel do Facebook ou um pixel do TikTok à sua página. O dono da página escolhe adicioná-los e é responsável por eles; o BioHop apenas fornece a plataforma. Eles só carregam depois que o visitante concorda, em um aviso na página, e a escolha do visitante fica guardada nesse navegador. Quando carregam, o navegador do visitante envia dados a esses serviços, sob as políticas de privacidade deles. O BioHop não recebe esses dados.
- O visitante precisa marcar uma caixa de consentimento para enviar um formulário de inscrição. As entradas ficam guardadas para o dono da página, que define o prazo de retenção, pode exportá-las em CSV e pode apagá-las. Elas são apagadas junto com a página ou a conta.

<a id="faq"></a>

## Perguntas frequentes

**Posso usar o BioHop sem o hub?** Não. Para editar é preciso um HOP ID no hub, e não há cadastro nem senha locais. As páginas públicas não exigem login, mas o app foi feito para rodar junto com o hub.

**O que acontece com meus dados de cliques?** Eles são guardados por 30 dias e depois apagados. O registro de cliques guarda um hash com chave do endereço, não o endereço. Veja [Privacidade](#privacy) para os detalhes.

**A verificação de segurança roda sozinha?** Sim, desde que o agendador esteja rodando. Ela usa regras compartilhadas e uma lista de bloqueio compartilhada. Nenhuma verificação automática pega tudo.

**Posso exportar minhas páginas?** Sim. `/settings/export` devolve suas páginas e blocos em JSON. Registros de cliques e entradas de formulário não estão incluídos; as entradas de formulário têm seu próprio download em CSV.

**Posso hospedar por conta própria?** Sim, sob a licença MIT. Você traz seu próprio hub, um LinkHop se quiser links curtos e um banco de dados. Veja [Rode o seu](#run-your-own).

<a id="family"></a>

## A família HOP

O BioHop faz parte da família **HOP** de apps de código aberto que você mesmo pode hospedar, por trás do [hel.ink](https://hel.ink).

<!-- hop-family:start -->
<!-- Generated from HEL.ink resources/hop-assets/apps.source.json; edit there, then run scripts/hop_assets.py build. -->
| App | Address | Repository | What it does |
| --- | --- | --- | --- |
| **HOP ID** | [hel.ink](https://hel.ink) · accounts at `hub.hel.ink` | [navi-crwn/HEL.ink](https://github.com/navi-crwn/HEL.ink) | One account for every HOP app. |
| **LinkHop** | [link.hel.ink](https://link.hel.ink) · `hel.ink/l/…` | [navi-crwn/LinkHop](https://github.com/navi-crwn/LinkHop) | Short links, QR codes and click analytics. |
| **BioHop** | [bio.hel.ink](https://bio.hel.ink) · `hel.ink/b/…` | [navi-crwn/BioHop](https://github.com/navi-crwn/BioHop) | One link-in-bio page for everything you do. |
| **PixelHop** | [pixel.hel.ink](https://pixel.hel.ink) · `p.hel.ink/…` | [navi-crwn/PixelHop](https://github.com/navi-crwn/PixelHop) | Image hosting with albums and direct links. |
| **NoteHop** | [note.hel.ink](https://note.hel.ink) · `n.hel.ink/…` | [navi-crwn/NoteHop](https://github.com/navi-crwn/NoteHop) | Encrypted notes you share with a link. |
| **FileHop** | [file.hel.ink](https://file.hel.ink) | [navi-crwn/FileHop](https://github.com/navi-crwn/FileHop) | Convert files in your browser; nothing is uploaded. |
| **PayHop** | [pay.hel.ink](https://pay.hel.ink) | [navi-crwn/PayHop](https://github.com/navi-crwn/PayHop) | Make a payment QR and split a bill; nothing leaves your browser. |
| **Imperium** | not public-facing: optional, for admins | [navi-crwn/Imperium](https://github.com/navi-crwn/Imperium) | The HOP control panel. |
<!-- hop-family:end -->

<a id="security"></a>

## Segurança

Encontrou uma vulnerabilidade? Relate em particular pelos security advisories do GitHub deste repositório: [github.com/navi-crwn/BioHop/security/advisories](https://github.com/navi-crwn/BioHop/security/advisories). Não abra issues públicas para relatos de segurança e não publique segredos, tokens ou detalhes de exploits em lugares públicos. O [SECURITY.md](SECURITY.md) tem os detalhes.

## Changelog e créditos

As mudanças estão listadas em [CHANGELOG.md](CHANGELOG.md). Os softwares de terceiros, fontes, ícones e serviços que o BioHop usa ou distribui, com suas licenças, estão listados em [CREDITS.md](CREDITS.md).

<a id="license"></a>

## Hospedagem própria

<!-- self-hosting:start -->
Tudo o que o BioHop precisa está em repositórios públicos: nenhum repositório privado, nenhum token e nenhuma conta no hel.ink. Os detalhes estão no guia da família: [docs/self-hosting.md](https://github.com/navi-crwn/HEL.ink/blob/main/docs/self-hosting.md) (em inglês).

| Peça | Para o BioHop | O que faz |
| --- | --- | --- |
| [HEL.ink](https://github.com/navi-crwn/HEL.ink) (hub, HOP ID) | **Obrigatório**: as contas ficam lá, no mesmo banco de dados | Contas e login para todos os apps |
| [hop/auth](https://github.com/navi-crwn/hop-auth), [hop/ui](https://github.com/navi-crwn/hop-ui), [hop/safety](https://github.com/navi-crwn/hop-safety) | Instalados pelo `composer install` a partir do GitHub público | Código PHP compartilhado: login, interface, segurança de links |
| [Imperium](https://github.com/navi-crwn/Imperium) | Opcional, mas as páginas de administração do BioHop ficam lá | Painel de administração de toda a família; precisa do hub |
| Fontes e blocos da família | Nada a instalar: carregados de `hel.ink/hop-assets` | Sem hel.ink: fontes do sistema, sem blocos flutuantes |

- **Ordem de instalação**: primeiro o [hub](https://github.com/navi-crwn/HEL.ink), depois o BioHop. `./install.sh --hub new` instala os dois.
- **Configuração mínima**: `APP_URL`, `PUBLIC_URL`, `HOP_APP=biohop`, `HOP_HUB_URL`, `HOP_SSO_SECRET` (o mesmo do hub), `DB_*` (o banco de dados do hub), `HOP_TRUSTED_HOSTS` (seus nomes de host); com o Imperium: `HOP_CONTROL_URL`.
- **Sozinho ou na família**: o BioHop não funciona sem um hub. Na família, ele compartilha o banco de dados e o `HOP_SSO_SECRET` do hub.
- **Sem o Imperium**: `/admin` mostra um aviso. Gerencie as contas nas páginas de administração do próprio hub e use a API assinada `/api/hop/*` e os comandos artisan.
<!-- self-hosting:end -->

## Licença

[MIT](LICENSE), como o resto da família HOP. As licenças de terceiros estão listadas em [CREDITS.md](CREDITS.md).

---

<p align="center"><sub>BioHop · parte da família HOP · <a href="https://hel.ink">hel.ink</a></sub></p>
