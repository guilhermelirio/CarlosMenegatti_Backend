<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    /**
     * Em ambiente local, pré-preenche as credenciais do admin para agilizar o
     * desenvolvimento. Em produção o formulário abre vazio, como esperado.
     */
    public function mount(): void
    {
        parent::mount();

        if (app()->environment('local')) {
            $this->form->fill([
                'email' => 'admin@pelada.test',
                'password' => 'password',
                'remember' => true,
            ]);
        }
    }
}
