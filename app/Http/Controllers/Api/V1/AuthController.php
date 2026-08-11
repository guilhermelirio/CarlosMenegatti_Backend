<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\V1\Auth\AuthTokenData;
use App\Data\V1\Auth\LoginData;
use App\Data\V1\Organization\OrganizationData;
use App\Data\V1\Player\PlayerData;
use App\Models\Organization;
use App\Models\User;
use App\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends ApiController
{
    /**
     * Authenticate an athlete and issue a Sanctum token.
     *
     * @unauthenticated
     */
    public function login(LoginData $data, Request $request, CurrentOrganization $currentOrganization): JsonResponse
    {
        $user = User::query()->where('email', $data->email)->first();

        if ($user === null || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $organizations = $user->organizations()->get();
        $requestedId = $request->header('X-Organization-Id');

        /** @var Organization|null $activeOrganization */
        $activeOrganization = $requestedId !== null
            ? $organizations->firstWhere('id', $requestedId)
            : ($organizations->count() === 1 ? $organizations->first() : null);

        if ($requestedId !== null && $activeOrganization === null) {
            throw ValidationException::withMessages([
                'organization' => ['Organização inválida para este usuário.'],
            ]);
        }

        if ($activeOrganization !== null) {
            $currentOrganization->set($activeOrganization);
        }

        $player = $activeOrganization === null ? null : $user->players()->first();

        if ($organizations->isEmpty() || ($activeOrganization !== null && $player === null)) {
            $currentOrganization->clear();

            throw ValidationException::withMessages([
                'email' => ['Este login não está vinculado a um atleta.'],
            ]);
        }

        $token = $user->createToken($data->device_name)->plainTextToken;

        $payload = new AuthTokenData(
            token: $token,
            token_type: 'Bearer',
            active_organization_id: $activeOrganization?->id,
            organizations: $organizations->map(
                fn (Organization $organization) => OrganizationData::fromModel($organization),
            )->all(),
            player: $player === null ? null : PlayerData::fromModel($player),
        );

        $currentOrganization->clear();

        // Login is not a resource creation -> 200 (Data responses default to 201).
        return response()->json($payload);
    }

    /** Revoke the current access token. */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Sessão encerrada.']);
    }

    /** Profile of the authenticated athlete. */
    public function me(Request $request): PlayerData
    {
        return PlayerData::fromModel($this->currentPlayer($request));
    }
}
