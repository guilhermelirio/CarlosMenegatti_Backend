<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #18181b; font-size: 12px; }
        .header { background: #0a0a0a; color: #fff; padding: 18px 24px; }
        .header .club { font-size: 20px; font-weight: bold; color: #fb923c; }
        .header .sub { font-size: 11px; color: #d4d4d8; margin-top: 2px; }
        .wrap { padding: 18px 24px; }
        h2 { font-size: 14px; border-bottom: 2px solid #f97316; padding-bottom: 4px; margin: 22px 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        .kpis td { width: 25%; padding: 10px; border: 1px solid #e4e4e7; text-align: center; }
        .kpis .label { color: #71717a; font-size: 10px; text-transform: uppercase; }
        .kpis .value { font-size: 16px; font-weight: bold; margin-top: 4px; }
        .green { color: #16a34a; } .red { color: #dc2626; } .orange { color: #ea580c; }
        .data th { background: #f4f4f5; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #52525b; border-bottom: 1px solid #e4e4e7; }
        .data td { padding: 6px 8px; border-bottom: 1px solid #f0f0f0; }
        .right { text-align: right; } .center { text-align: center; }
        .total-row td { font-weight: bold; border-top: 2px solid #d4d4d8; }
        .foot { margin-top: 24px; color: #a1a1aa; font-size: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="club">Carlos Menegatti FC</div>
        <div class="sub">Relatório financeiro &middot; Período: {{ $periodLabel }} &middot; Gerado em {{ $generatedAt }}</div>
    </div>

    <div class="wrap">
        <h2>Resumo do período</h2>
        <table class="kpis">
            <tr>
                <td><div class="label">Receitas</div><div class="value green">{{ $money($cash['income_cents']) }}</div></td>
                <td><div class="label">Despesas</div><div class="value red">{{ $money($cash['expense_cents']) }}</div></td>
                <td><div class="label">Saldo</div><div class="value {{ $cash['balance_cents'] >= 0 ? 'green' : 'red' }}">{{ $money($cash['balance_cents']) }}</div></td>
                <td><div class="label">Inadimplência</div><div class="value orange">{{ $money($totalOwed) }}</div></td>
            </tr>
        </table>

        <h2>Fluxo de caixa — últimos 6 meses</h2>
        <table class="data">
            <thead>
                <tr><th>Mês</th><th class="right">Receitas</th><th class="right">Despesas</th><th class="right">Saldo</th></tr>
            </thead>
            <tbody>
                @foreach ($series as $m)
                    <tr>
                        <td>{{ $m['label'] }}</td>
                        <td class="right green">{{ $money($m['income_cents']) }}</td>
                        <td class="right red">{{ $money($m['expense_cents']) }}</td>
                        <td class="right">{{ $money($m['balance_cents']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>Inadimplência detalhada</h2>
        <table class="data">
            <thead>
                <tr><th>Atleta</th><th class="center">Cobranças em aberto</th><th class="center">Meses em atraso</th><th class="right">Total devido</th></tr>
            </thead>
            <tbody>
                @forelse ($delinquency as $row)
                    <tr>
                        <td>{{ $row['player_name'] }}</td>
                        <td class="center">{{ $row['open_charges'] }}</td>
                        <td class="center">{{ $row['months_late'] > 0 ? $row['months_late'] : '—' }}</td>
                        <td class="right red">{{ $money($row['total_owed_cents']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="center">Ninguém devendo.</td></tr>
                @endforelse
            </tbody>
            @if (count($delinquency) > 0)
                <tfoot>
                    <tr class="total-row"><td colspan="3">Total</td><td class="right red">{{ $money($totalOwed) }}</td></tr>
                </tfoot>
            @endif
        </table>

        <div class="foot">Carlos Menegatti FC — documento gerado automaticamente pelo sistema de controle de mensalidades.</div>
    </div>
</body>
</html>
