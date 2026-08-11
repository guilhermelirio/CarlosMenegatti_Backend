<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Integrations\Pix\Support\PigglyPixCode;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use Throwable;
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

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $tenant = Filament::getTenant();

        return $user instanceof User
            && $tenant instanceof Organization
            && ($user->roleForOrganization($tenant)?->canManageFinance() ?? false);
    }

    public function mount(): void
    {
        $this->form->fill([
            'default_monthly_fee' => number_format(Setting::getInt(Setting::DEFAULT_MONTHLY_FEE_CENTS) / 100, 2, '.', ''),
            'default_daily_fee' => number_format(Setting::getInt(Setting::DEFAULT_DAILY_FEE_CENTS) / 100, 2, '.', ''),
            'monthly_fee_due_day' => Setting::getInt(Setting::MONTHLY_FEE_DUE_DAY, 10),
            'late_fee_percent' => Setting::getInt(Setting::LATE_FEE_PERCENT),
            'monthly_interest_percent' => Setting::getInt(Setting::MONTHLY_INTEREST_PERCENT),
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

                Section::make('Atrasos')
                    ->description('Os valores começam em 0%. Se permanecerem zerados, não haverá multa nem juros.')
                    ->schema([
                        TextInput::make('late_fee_percent')
                            ->label('Multa única')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),
                        TextInput::make('monthly_interest_percent')
                            ->label('Juros ao mês')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            PigglyPixCode::validateKey((string) $data['pix_key_type'], (string) $data['pix_key']);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'data.pix_key' => ['A chave Pix não corresponde ao tipo selecionado.'],
            ]);
        }

        Setting::set(Setting::DEFAULT_MONTHLY_FEE_CENTS, (string) (int) round(((float) $data['default_monthly_fee']) * 100));
        Setting::set(Setting::DEFAULT_DAILY_FEE_CENTS, (string) (int) round(((float) $data['default_daily_fee']) * 100));
        Setting::set(Setting::MONTHLY_FEE_DUE_DAY, (string) (int) $data['monthly_fee_due_day']);
        Setting::set(Setting::LATE_FEE_PERCENT, (string) (int) $data['late_fee_percent']);
        Setting::set(Setting::MONTHLY_INTEREST_PERCENT, (string) (int) $data['monthly_interest_percent']);

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
