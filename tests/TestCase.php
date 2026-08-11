<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Organization;
use App\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected Organization $organization;

    protected function setCurrentOrganization(?Organization $organization = null): Organization
    {
        $this->organization = $organization ?? Organization::factory()->create();
        app(CurrentOrganization::class)->set($this->organization);

        return $this->organization;
    }

    /**
     * Trava de segurança: nunca deixe a suíte (RefreshDatabase) rodar contra um
     * banco que não seja o de testes. Sem isto, rodar os testes com a config
     * cacheada (que ignora o SQLite em memória do phpunit.xml) faria o
     * `migrate:fresh` apagar o banco de desenvolvimento.
     *
     * Roda em refreshApplication() (antes dos traits/RefreshDatabase), então
     * aborta ANTES de qualquer alteração no banco.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $isIsolatedDatabase = ($connection === 'sqlite' && $database === ':memory:')
            || str_contains($database, 'testing');

        if (! app()->environment('testing') || ! $isIsolatedDatabase) {
            throw new RuntimeException(
                "Testes abortados: conexão [{$connection}] com banco [{$database}] não é isolada. ".
                'Rode `php artisan config:clear` antes de `php artisan test` '.
                '(a config cacheada sobrepõe o DB de testes do phpunit.xml).'
            );
        }
    }
}
