<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class ApiController extends Controller
{
    /** Resolve the Player linked to the authenticated user, or 403 if none. */
    protected function currentPlayer(Request $request): Player
    {
        $user = $request->user();
        $player = $user?->players()->first();

        if ($player === null) {
            throw new HttpException(403, 'Usuário autenticado não está vinculado a um atleta.');
        }

        return $player;
    }
}
