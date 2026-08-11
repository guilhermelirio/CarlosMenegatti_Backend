<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageValues extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Configuração de valores';

    protected static ?string $title = 'Configuração de valores';

    protected static ?int $navigationSort = 21;

    protected string $view = 'filament.pages.manage-values';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'default_monthly_fee' => number_format(Setting::getInt(Setting::DEFAULT_MONTHLY_FEE_CENTS) / 100, 2, '.', ''),
            'default_daily_fee' => number_format(Setting::getInt(Setting::DEFAULT_DAILY_FEE_CENTS) / 100, 2, '.', ''),
            'monthly_fee_due_day' => Setting::getInt(Setting::MONTHLY_FEE_DUE_DAY, 10),
            'pix_key_type' => Setting::get(Setting::PIX_KEY_TYPE, 'email'),
            'pix_key' => Setting::get(Setting::PIX_KEY),
            'pix_receiver_name' => Setting::get(Setting::PIX_RECEIVER_NAME),
            'pix_city' => Setting::get(Setting::PIX_CITY),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Valores padrão')
                    ->description('Usados quando o atleta não tem valor individual definido.')
                    ->schema([
                        TextInput::make('default_monthly_fee')
                            ->label('Mensalidade padrão')
                            ->numeric()
                            ->prefix('R$')
                            ->step('0.01')
                            ->required(),
                        TextInput::make('default_daily_fee')
                            ->label('Diária padrão')
                            ->numeric()
                            ->prefix('R$')
                            ->step('0.01')
                            ->required(),
                        Select::make('monthly_fee_due_day')
                            ->label('Dia de vencimento da mensalidade')
                            ->options(array_combine(range(1, 28), array_map('strval', range(1, 28))))
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Recebimento via Pix (manual)')
                    ->description('Chave Pix da organização usada para gerar o copia-e-cola/QR. O pagamento é confirmado manualmente pelo tesoureiro ao dar baixa.')
                    ->schema([
                        Select::make('pix_key_type')
                            ->label('Tipo da chave')
                            ->options([
                                'cpf' => 'CPF',
                                'cnpj' => 'CNPJ',
                                'email' => 'E-mail',
                                'telefone' => 'Telefone',
                                'aleatoria' => 'Aleatória',
                            ])
                            ->required(),
                        TextInput::make('pix_key')
                            ->label('Chave Pix')
                            ->maxLength(77)
                            ->required(),
                        TextInput::make('pix_receiver_name')
                            ->label('Nome do recebedor')
                            ->helperText('Máx. 25 caracteres (padrão do BR Code).')
                            ->maxLength(25)
                            ->required(),
                        TextInput::make('pix_city')
                            ->label('Cidade')
                            ->helperText('Máx. 15 caracteres.')
                            ->maxLength(15)
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set(Setting::DEFAULT_MONTHLY_FEE_CENTS, (string) (int) round(((float) $data['default_monthly_fee']) * 100));
        Setting::set(Setting::DEFAULT_DAILY_FEE_CENTS, (string) (int) round(((float) $data['default_daily_fee']) * 100));
        Setting::set(Setting::MONTHLY_FEE_DUE_DAY, (string) (int) $data['monthly_fee_due_day']);

        Setting::set(Setting::PIX_KEY_TYPE, (string) $data['pix_key_type']);
        Setting::set(Setting::PIX_KEY, (string) $data['pix_key']);
        Setting::set(Setting::PIX_RECEIVER_NAME, (string) $data['pix_receiver_name']);
        Setting::set(Setting::PIX_CITY, (string) $data['pix_city']);

        Notification::make()->title('Configurações atualizadas.')->success()->send();
    }

    /** @return array<int, Action> */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Salvar')->submit('save'),
        ];
    }
}
