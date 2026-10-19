# Site da Graça Church Palhoça

Site com programação, localização da igreja e dos grupos de convívio, app instalável (PWA) e avisos push automáticos. Inclui um painel para a equipe cadastrar tudo.

- **Backend:** PHP 8.3 puro (sem framework, sem Composer) + MySQL 8 (ou MariaDB 10.6+)
- **Front:** HTML gerado no servidor, CSS embutido e ~6 KB de JavaScript. Funciona sem JavaScript.
- **Primeira visita no celular:** ~14 KB de HTML comprimido + 8 KB de imagem + fonte. Pensado para 3G.

## Rodar localmente (Docker)

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php bin/vapid.php      # copie as 2 linhas para o .env
docker compose restart app cron
docker compose exec app php bin/migrate.php --demo   # opcional: grupos de exemplo
```

- Site: http://localhost:8080
- Painel: http://localhost:8080/admin (no primeiro acesso ele pede o `SETUP_TOKEN` do `.env` para criar o administrador)
- O serviço `cron` roda as migrações e o agendador a cada minuto.

Sem Docker: `php -S localhost:8080 -t public public/router-dev.php` com um MySQL local e as variáveis do `.env`.

## Publicar em produção

Serve qualquer hospedagem com PHP 8.1+ (extensões `pdo_mysql`, `openssl`, `curl`, `mbstring`) e MySQL 8 / MariaDB 10.6+.

1. Envie os arquivos e aponte o domínio para a pasta **`public/`** (nunca para a raiz do projeto).
2. Crie o `.env` a partir do `.env.example`, com `APP_URL=https://gracachurchpalhoca.org` e `APP_ENV=production`.
3. Rode `php bin/migrate.php` e `php bin/vapid.php` (copie as chaves para o `.env`). **Gere as chaves uma vez só:** trocar as chaves invalida todas as inscrições.
4. Configure o agendador no crontab (ou no painel da hospedagem):
   ```
   * * * * * php /caminho/do/site/bin/cron.php >/dev/null 2>&1
   ```
5. **HTTPS é obrigatório** para o app instalável e os avisos.
6. No painel, em **Configurações**, marque o local exato da igreja no mapa (o site atual não informava as coordenadas).

O `.htaccess` em `public/` já cuida de reescrita de URL, HTTPS, compressão e cache no Apache. Para Nginx:

```nginx
root /caminho/do/site/public;
index index.php;
gzip on; gzip_types text/css application/javascript application/json application/manifest+json text/markdown image/svg+xml;
location / { try_files $uri /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; fastcgi_pass unix:/run/php/php8.3-fpm.sock; }
location ~* \.(woff2|webp|png|jpg|ico|js|css)$ { expires 1y; add_header Cache-Control "public, immutable"; }
location = /sw.js { add_header Cache-Control "no-cache"; }
```

## Avisos push

- Sem cadastro: a pessoa escolhe os tipos (cultos, eventos, ministérios, seu grupo) e permite as notificações.
- **Lembretes automáticos:** cada programação e cada grupo tem "enviar lembrete X antes". O agendador cria o aviso na hora certa, uma única vez por data.
- **Avisos manuais:** painel > Avisos, com prévia e contagem de quem vai receber.
- Funciona no Android, Windows, Linux, Mac (Chrome, Edge, Firefox, Safari) e no **iPhone/iPad com iOS 16.4+ depois de instalar o app na Tela de Início** (limitação da Apple). O site explica isso para quem está no iPhone.
- Aparelhos que cancelaram a inscrição são removidos sozinhos. Falhas temporárias são tentadas de novo.
- Implementação própria do padrão Web Push (VAPID + aes128gcm) em `src/WebPush.php`, testada em `tests/push_test.php`.

## SEO, AEO e GEO

- Cada encontro e cada grupo tem página própria, com título e descrição gerados a partir dos dados.
- Dados estruturados schema.org: `Church` (endereço, horários, geo), `Event` com recorrência para cada programação e grupo, `FAQPage`, `BreadcrumbList`, `WebSite`.
- Textos que respondem logo de cara ("Cultos: domingos às 19h30 e quartas às 20h, na BR 282, KM 18"), bons para respostas de assistentes e buscas por voz.
- `/sitemap.xml`, `/robots.txt` (libera buscadores de IA) e `/llms.txt` (resumo dos fatos em Markdown para ChatGPT, Claude, Perplexity e afins).
- Perguntas frequentes editáveis no painel.
- Arquivo de calendário (`/eventos/<nome>.ics`) com repetição, para "Pôr na agenda".

## Privacidade dos grupos

Grupos que se reúnem em casas mostram só o bairro e um ponto aproximado (~100 m) no mapa. O endereço completo fica no painel e só aparece se a equipe marcar "Mostrar endereço completo no site".

## Estrutura

```
public/           raiz do site (index.php, sw.js, manifesto, imagens, JS)
src/              código PHP (rotas em public/index.php, telas em src/Http)
views/            páginas (HTML + PHP) e o CSS embutido
database/         migrações e dados de exemplo
bin/              migrate, cron, vapid, create-admin
brand/            logo e fotos originais, tokens do design system
tests/            teste da criptografia dos avisos
```

## Dados de exemplo

`database/demo.sql` cria 3 grupos e 1 evento **de exemplo**, com coordenadas aproximadas. Não carregue em produção. A programação real (cultos e ministérios), endereço, WhatsApp e Instagram vêm do site atual e já entram com `bin/migrate.php`.
