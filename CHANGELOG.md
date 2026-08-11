# Changelog

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/).

## [Unreleased]

### Added
- **Multi-tenancy por organização**: vínculo de usuários com papéis, escopo global em todo o domínio,
  tenant nativo no Filament e seleção segura via `X-Organization-Id` na API.
- **Administração de organizações**: onboarding com configurações e categorias iniciais, edição do perfil,
  criação/vínculo de usuários e proteção contra remoção ou rebaixamento do último administrador.
- **Operação assíncrona e observabilidade**: Laravel Horizon e Pulse, com acesso restrito a administradores,
  além de snapshot do Horizon no scheduler.
- **Stack de produção**: Nginx, PHP-FPM 8.5, OPcache, PostgreSQL e Redis em processos separados para
  aplicação, Horizon e scheduler (`compose.prod.yaml`).
- **Domínio financeiro da organização**: models `Player`, `GameSession`, `MonthlyFee`, `DailyFee`,
  `Attendance`, `Payment`, `Transaction`, `Category`, `Setting`, `WebhookEvent` (PK ULID, dinheiro em centavos).
- **Enums** com rótulos pt-BR: `PlayerStatus`, `MembershipType`, `PlayerPosition`, `FeeStatus`,
  `PaymentMethod`, `PaymentStatus`, `TransactionType`.
- **Configuração de valores** (mensalidade/diária padrão + dia de vencimento) com override individual por atleta.
- **Geração recorrente de mensalidades** (`fees:generate-monthly`) e marcação de vencidas (`fees:mark-overdue`),
  agendadas em `routes/console.php`.
- **Presenças**: ao marcar presença de diarista, gera automaticamente a diária a cobrar (via `AttendanceObserver`).
- **Pagamentos**: baixa manual (dinheiro/pix/transferência) e Pix automático; ao confirmar, quita a
  mensalidade/diária e lança a receita no caixa (transação).
- **Abstração de gateway Pix** (`PixGatewayContract`) + driver `FakePixGateway` funcional (QR/copia-e-cola),
  `PixManager` e `PixServiceProvider`. Webhook de confirmação preparado em `POST /api/webhooks/pix/{provider}/{secret}`
  (verificação de assinatura, dedupe por evento, processamento assíncrono via `ProcessPixWebhookJob`).
- **API V1 (Sanctum + Spatie Data + Scramble)**: login/logout/perfil, mensalidades/diárias/pagamentos do atleta,
  próximas sessões, confirmação de presença e início/consulta de Pix. Rate limiting em `login`; formato de erro padronizado.
- **Painel Filament (`/admin/{organization-slug}`)**: resources de Atletas, Sessões (com presenças), Mensalidades, Diárias, Pagamentos,
  Caixa e Categorias; ações "Gerar mensalidades do mês", "Receber pagamento" e "Confirmar pagamento";
  página "Configuração de valores"; página "Relatórios"; dashboard com widgets (saldo, receita x despesa,
  inadimplência, atletas ativos, próximas sessões).
- **Relatórios** (`ReportService`): inadimplência, caixa por período, receita por origem, presença por atleta.
- **Relatórios isolados por tenant**: exportação PDF exige administrador da organização e usa sua identidade no documento.
- **Filtros financeiros**: caixa por período, tipo, categoria e atleta; cobranças por período, tipo,
  vínculo, status e atleta; mensalidades por competência, status e atleta.
- **Relatórios por período**: indicadores, gráficos e PDF usam o mesmo intervalo selecionado, inclusive
  nos meses inicial e final.
- **Preservação do caixa**: exclusões de lançamentos são recuperáveis, e remoções definitivas permanecem bloqueadas.
- **Regras de cobrança**: descontos fixos e percentuais permanentes ou por mensalidade, isenção mensal,
  gratuidade permanente e multa/juros configuráveis por organização com padrão de 0%.
- **Convidados**: cadastro sem login, histórico de participação e receita individual por partida na categoria `Convidado`.
- **Fechamento mensal**: fotografia de caixa e inadimplência, bloqueio de alterações no período fechado e
  reabertura exclusiva por administrador com motivo obrigatório.
- **Auditoria financeira**: edições, estornos, exclusões recuperáveis e restaurações de lançamentos guardam
  responsável, data, estado anterior e estado posterior. Pagamentos confirmados não são apagáveis pelo painel.
- **Comprovantes opcionais**: upload pelo aplicativo para pagamentos e pelo painel para receitas/despesas,
  com armazenamento privado e download autorizado.
- **Perfis administrativos**: administrador, tesoureiro e somente consulta, preservando `member` para acesso do atleta.
- **Exportações**: relatórios em PDF e CSV UTF-8 compatível com Excel.
- **Backup diário**: banco PostgreSQL e comprovantes privados, com arquivo verificável e criptografável,
  retenção automática, monitoramento de integridade e volumes persistentes separados em produção.
- **Testes** (PHPUnit): fluxo de domínio, API, isolamento multi-tenant, regras e controles financeiros e smoke dos painéis Filament (42 testes).

### Notes
- Provider Pix real **ainda não integrado** — `FakePixGateway` ativo até aprovação (ver tabela comparativa no resumo do PR).

CONTRACT_UPDATE: api-v1-inicial (12 endpoints; contrato em `/docs/api`, export em `storage/api.json`)

CONTRACT_UPDATE: api-v1-multi-tenant (`organizations`, `active_organization_id` e cabeçalho `X-Organization-Id`)

CONTRACT_UPDATE: regras-financeiras-e-comprovantes (`gross_amount_cents`, descontos, encargos,
`has_receipt` e upload/download em `/api/v1/payments/{payment}/receipt`)
