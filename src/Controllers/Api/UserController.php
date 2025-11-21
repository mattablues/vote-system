<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Status;
use App\Models\User;
use Radix\Controller\ApiController;
use Radix\Http\JsonResponse;
use Throwable;

class UserController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->validateRequest(); // Kontrollera API-token för GET-anrop

        // Hämta användardata
        $pageRaw   = $this->request->get['page']    ?? 1;
        $perPageRaw = $this->request->get['perPage'] ?? 10;

        // Parsning + hårda gränser (robust mot mutationer)
        $currentPage = is_numeric($pageRaw) ? (int) $pageRaw : 1;
        $perPage     = is_numeric($perPageRaw) ? (int) $perPageRaw : 10;

        // Hårda valideringar för att neutralisera mutationer
        if ($currentPage < 1) {
            $this->respondWithErrors(['page' => 'current_page must be >= 1'], 422);
        }

        if ($perPage < 1) {
            $this->respondWithErrors(['perPage' => 'per_page must be between 1 and 100'], 422);
        }
        // Dela upp övre gränsen för att stå emot >= / > mutationer
        if ($perPage > 100) {
            $this->respondWithErrors(['perPage' => 'per_page must be between 1 and 100'], 422);
        }

        /** @var array{
         *     data: list<\App\Models\User>,
         *     pagination: array<string,mixed>
         * } $results
         */
        $results = User::with('status')
            ->paginate($perPage, $currentPage);

        return $this->json([
            'success' => true,
            'data' => $results['data'],
            'meta' => $results['pagination'],
        ]);
    }

    public function store(): JsonResponse
    {
        // Validera inkommande förfrågan
        $this->validateRequest([
            'first_name' => 'required|string|min:2|max:15',
            'last_name' => 'required|string|min:2|max:15',
            'email' => 'required|email|unique:App\Models\User,email',
            'password' => 'required|string|min:8|max:15',
            'password_confirmation' => 'required|confirmed:password',
        ]);

        $data = $this->request->post;

        $firstName = is_string($data['first_name'] ?? null) ? $data['first_name'] : '';
        $lastName  = is_string($data['last_name'] ?? null) ? $data['last_name'] : '';
        $email     = is_string($data['email'] ?? null) ? $data['email'] : '';
        /** @var non-empty-string $password */
        $password  = is_string($data['password'] ?? null) ? $data['password'] : '';

        // Skapa användare
        $user = new User();
        $user->fill([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
        ]);
        $user->password = $password; // triggar setPasswordAttribute
        $user->save();

        $status = new Status();
        $status->fill([
            'user_id' => $user->id,
            'status' => 'activate',
        ]);
        $status->save();

        // Returnera resultat
        return $this->json(['success' => true, 'data' => $user->toArray()], 201);
    }

    public function update(string $id): JsonResponse
    {
        $this->validateRequest([
            'first_name' => 'nullable|string|min:2|max:15',
            'last_name' => 'nullable|string|min:2|max:15',
            'email' => 'nullable|email', // Endast validera om e-post skickas
            'password' => 'nullable|string|min:6',
        ]);

        $user = User::find($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'errors' => ['user' => 'Användaren kunde inte hittas.'],
            ], 404);
        }

        $data = $this->request->filterFields($this->request->post, []);

        // Hantera lösenord
        if (array_key_exists('password', $this->request->post)
            && is_string($this->request->post['password'])
            && $this->request->post['password'] !== ''
        ) {
            /** @var non-empty-string $password */
            $password = $this->request->post['password'];
            $user->password = $password;
        }

        $user->fill($data);
        $user->save();

        return $this->json([
            'success' => true,
            'data' => $user->toArray(),
        ]);
    }

    public function partialUpdate(string $id): JsonResponse
    {
        $rules = [
            'first_name' => 'sometimes|string|min:2|max:15',
            'last_name' => 'sometimes|string|min:2|max:15',
        ];

        // Lägg endast till email-regeln om fältet existerar i indata
        if (isset($this->request->post['email'])) {
            $rules['email'] = "email|unique:App\Models\User,email,$id,id";
        }

        $this->validateRequest($rules);

        $user = User::find($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'errors' => ['user' => 'Användaren kunde inte hittas.'],
            ], 404);
        }

        // Filtrera bort onödiga fält
        $data = $this->request->filterFields($this->request->post);

        // Uppdatera användaren
        $user->fill($data);
        $user->save();

        return $this->json([
            'success' => true,
            'data' => $user->toArray(),
        ]);
    }

    public function delete(string $id): JsonResponse
    {
        // Steg 1: Validera API-token eller session
        $this->validateRequest();

        // Steg 2: Hämta användaren (inklusive trashed)
        $user = User::find($id, true);

        // Kontrollera att användaren existerar
        if (!$user) {
            return $this->json([
                'success' => false,
                'errors' => ['user' => 'Användaren kunde inte hittas.'],
            ], 404);
        }

        // Steg 3: Säkerställ att `deleted_at` finns, annars hämta det typ-säkert
        if (!array_key_exists('deleted_at', $user->getAttributes())) {
            $rawDeletedAt = $user->fetchGuardedAttribute('deleted_at');

            if (!is_string($rawDeletedAt)) {
                $rawDeletedAt = null;
            }

            /** @var string|null $rawDeletedAt */
            $user->deleted_at = $rawDeletedAt;
        }

        // Steg 4: Kontrollera om användaren redan är soft deleted
        if (!empty($user->deleted_at)) {
            return $this->json([
                'success' => false,
                'errors' => ['user' => 'Användaren är redan soft deleted.'],
            ], 400);
        }

        // Steg 5: Utför soft delete
        try {
            $user->delete();
        } catch (Throwable $e) {
            return $this->json([
                'success' => false,
                'errors' => ['server' => "Fel: {$e->getMessage()}"],
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Användaren har raderats (soft delete).',
        ]);
    }
}
