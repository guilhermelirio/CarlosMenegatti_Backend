<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * View "charges": junta mensalidades e diárias numa única lista de cobranças,
 * para a tela unificada de Cobranças. Read-only (só leitura).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement(<<<'SQL'
                CREATE VIEW charges AS
                    SELECT
                        mf.id,
                        mf.organization_id,
                        'monthly' AS charge_type,
                        mf.player_id,
                        mf.amount_cents,
                        mf.status,
                        printf('%02d/%04d', mf.reference_month, mf.reference_year) AS reference_label,
                        mf.due_date AS reference_date,
                        mf.paid_at,
                        mf.created_at
                    FROM monthly_fees mf
                    UNION ALL
                    SELECT
                        df.id,
                        df.organization_id,
                        'daily' AS charge_type,
                        df.player_id,
                        df.amount_cents,
                        df.status,
                        strftime('%d/%m/%Y', gs.scheduled_date) AS reference_label,
                        gs.scheduled_date AS reference_date,
                        df.paid_at,
                        df.created_at
                    FROM daily_fees df
                    LEFT JOIN game_sessions gs ON gs.id = df.game_session_id
            SQL);

            return;
        }

        DB::statement(<<<'SQL'
            CREATE VIEW charges AS
                SELECT
                    mf.id,
                    mf.organization_id,
                    'monthly'::text AS charge_type,
                    mf.player_id,
                    mf.amount_cents,
                    mf.status,
                    to_char(make_date(mf.reference_year::int, mf.reference_month::int, 1), 'MM/YYYY') AS reference_label,
                    mf.due_date AS reference_date,
                    mf.paid_at,
                    mf.created_at
                FROM monthly_fees mf
                UNION ALL
                SELECT
                    df.id,
                    df.organization_id,
                    'daily'::text AS charge_type,
                    df.player_id,
                    df.amount_cents,
                    df.status,
                    to_char(gs.scheduled_date, 'DD/MM/YYYY') AS reference_label,
                    gs.scheduled_date AS reference_date,
                    df.paid_at,
                    df.created_at
                FROM daily_fees df
                LEFT JOIN game_sessions gs ON gs.id = df.game_session_id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS charges');
    }
};
