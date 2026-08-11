# Changelog

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/).

## [Unreleased]

### Added
- **Domínio financeiro da pelada**: models `Player`, `GameSession`, `MonthlyFee`, `DailyFee`,
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
- **Painel Filament (`/admin`)**: resources de Atletas, Sessões (com presenças), Mensalidades, Diárias, Pagamentos,
  Caixa e Categorias; ações "Gerar mensalidades do mês", "Receber pagamento" e "Confirmar pagamento";
  página "Configuração de valores"; página "Relatórios"; dashboard com widgets (saldo, receita x despesa,
  inadimplência, atletas ativos, próximas sessões).
- **Relatórios** (`ReportService`): inadimplência, caixa por período, receita por origem, presença por atleta.
- **Testes** (PHPUnit): fluxo de domínio, API e smoke dos painéis Filament (19 testes).

### Notes
- Provider Pix real **ainda não integrado** — `FakePixGateway` ativo até aprovação (ver tabela comparativa no resumo do PR).

CONTRACT_UPDATE: api-v1-inicial (12 endpoints; contrato em `/docs/api`, export em `storage/api.json`)
