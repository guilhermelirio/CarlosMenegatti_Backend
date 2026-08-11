<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Trava de segurança: nunca deixe a suíte (RefreshDatabase) rodar contra um
     * banco que não seja o de testes. Sem isto, rodar os testes com a config
     * cacheada (que ignora o DB_DATABASE=testing do phpunit.xml) faria o
     * `migrate:fresh` apagar o banco de desenvolvimento.
     *
     * Roda em refreshApplication() (antes dos traits/RefreshDatabase), então
     * aborta ANTES de qualquer alteração no banco.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $database = (string) config('database.connections.'.config('database.default').'.database');

        if (! str_contains($database, 'testing')) {
            throw new RuntimeException(
                "Testes abortados: conectados ao banco [{$database}], que não é de testes. ".
                'Rode `php artisan config:clear` antes de `php artisan test` '.
                '(a config cacheada sobrepõe o DB de testes do phpunit.xml).'
            );
        }
    }
}
