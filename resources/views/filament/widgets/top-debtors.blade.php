<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Maiores devedores</x-slot>
        <x-slot name="description">{{ $count }} atleta(s) com cobranças em aberto</x-slot>

        <div style="overflow-x:auto;">
            <table style="width:100%;font-size:.85rem;border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left;color:#71717a;">
                        <th style="padding:.4rem 0;">Atleta</th>
                        <th style="padding:.4rem .5rem;text-align:center;">Em aberto</th>
                        <th style="padding:.4rem .5rem;text-align:center;">Atraso</th>
                        <th style="padding:.4rem 0;text-align:right;">Devido</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr style="border-top:1px solid rgba(120,120,120,.15);">
                            <td style="padding:.45rem 0;font-weight:500;">{{ $row['player_name'] }}</td>
                            <td style="padding:.45rem .5rem;text-align:center;">{{ $row['open_charges'] }}</td>
                            <td style="padding:.45rem .5rem;text-align:center;">
                                @if ($row['months_late'] > 0)
                                    <span style="display:inline-block;border-radius:9999px;background:#fef2f2;color:#b91c1c;padding:1px 8px;font-size:.72rem;font-weight:600;">{{ $row['months_late'] }}m</span>
                                @else
                                    <span style="color:#a1a1aa;">—</span>
                                @endif
                            </td>
                            <td style="padding:.45rem 0;text-align:right;font-weight:600;color:#dc2626;">{{ $money($row['total_owed_cents']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="padding:1rem 0;text-align:center;color:#71717a;">Ninguém devendo. 🎉</td></tr>
                    @endforelse
                </tbody>
                @if ($count > 0)
                    <tfoot>
                        <tr style="border-top:2px solid rgba(120,120,120,.3);font-weight:700;">
                            <td style="padding:.5rem 0;" colspan="3">Total geral</td>
                            <td style="padding:.5rem 0;text-align:right;color:#dc2626;">{{ $money($total) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
