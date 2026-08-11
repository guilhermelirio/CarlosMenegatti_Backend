# Backend multi-tenant para grupos de futebol

API e painel administrativo para múltiplas organizações, construídos com Laravel 13, Filament 4, PostgreSQL e Redis. O nome comercial do produto ainda não foi definido; `Organization` é apenas o nome técnico do tenant.

## Arquitetura

- Cada tabela de domínio possui `organization_id` e um escopo global obrigatório.
- Usuários pertencem a organizações por `organization_user`: atletas (`member`) entram somente no aplicativo;
  administrador, tesoureiro e consulta entram somente no painel administrativo.
- A API usa Sanctum e resolve o tenant por `X-Organization-Id`. O cabeçalho é opcional quando o usuário possui uma única organização.
- O Filament usa tenancy nativa em `/admin/{organization-slug}`.
- Administradores podem editar o perfil da organização e gerenciar usuários e papéis pelo próprio painel;
  a criação de novas organizações não fica disponível no painel.
- Cada organização mantém pelo menos um administrador; remover um vínculo nunca apaga a conta global do usuário.
- O caixa permite filtrar por período, tipo, categoria e atleta; lançamentos podem ser editados, estornados ou excluídos de forma recuperável e auditada.
- Cobranças podem ser filtradas por período, tipo, vínculo, situação e atleta; os relatórios e o PDF respeitam o período selecionado.
- Mensalidades aceitam descontos fixos e percentuais, por jogador ou por cobrança, além de isenção mensal e gratuidade permanente.
- Convidados ficam registrados sem login no aplicativo e geram receita por partida na categoria própria.
- Multa e juros mensais são configuráveis por organização e começam em 0%.
- O Pix manual usa `piggly/php-pix` para validar a chave e gerar localmente o copia-e-cola e o QR Code;
  a confirmação do recebimento permanece sob responsabilidade do tesoureiro.
- O fechamento mensal fotografa caixa e inadimplência; meses fechados ficam bloqueados e somente administradores podem reabri-los.
- Edições, estornos, exclusões recuperáveis e restaurações do caixa geram histórico de auditoria com responsável e valores anteriores/novos.
- Comprovantes são opcionais e podem ser anexados aos pagamentos pelo aplicativo ou aos lançamentos pelo painel.
- O aplicativo consome uma lista única de cobranças, reunindo mensalidades e diárias, com detalhe e pagamento via Pix.
- Cada jogo possui limite de jogadores configurado pelo administrador; a API informa ocupação e vagas e impede confirmações acima do limite.
- O painel possui perfis de administrador, tesoureiro e somente consulta; relatórios podem ser exportados em PDF e CSV compatível com Excel.
- Os relatórios podem ser filtrados em conjunto por período, atleta, vínculo, categoria e tipo de lançamento;
  a seleção também é aplicada aos indicadores, gráficos, PDF e CSV.
- Todas as páginas operacionais do painel usam a largura total disponível. No aplicativo e no painel, o termo
  apresentado ao usuário é “Jogo”; “sessão” permanece apenas como detalhe técnico interno legado do banco.
- Horizon processa filas Redis; o scheduler roda separadamente; Pulse fica disponível para administradores.
- Reverb não faz parte da stack enquanto não existir um caso real de atualização em tempo real.

## Desenvolvimento com Sail

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

Para executar servidor, Horizon, scheduler, logs e Vite juntos:

```bash
./vendor/bin/sail composer dev
```

## Produção

A stack de referência usa Nginx + PHP-FPM 8.5 + OPcache, com processos separados para Horizon e scheduler.

```bash
cp .env.production.example .env.production
# preencha APP_KEY, senhas, domínio e e-mail
docker compose --env-file .env.production -f compose.prod.yaml build
docker compose --env-file .env.production -f compose.prod.yaml run --rm app php artisan migrate --force
docker compose --env-file .env.production -f compose.prod.yaml up -d
```

O deploy deve executar `php artisan optimize` após as variáveis definitivas estarem disponíveis. Como `opcache.validate_timestamps=0` em produção, publique uma nova imagem ou reinicie os processos a cada release.

## Qualidade

```bash
composer test
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/pint --test
composer audit
```

Documentação da API: `/docs/api`. Horizon: `/horizon`. Pulse: `/pulse`.
