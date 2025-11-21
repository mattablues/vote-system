<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\User;
use Radix\Controller\ApiController;
use Radix\Http\JsonResponse;

class SearchController extends ApiController
{
    public function users(): JsonResponse
    {
        // Validera förfrågan för att säkerställa att rätt data skickas med
        $this->validateRequest([
            'search.term' => 'required|string|min:1',
            'search.current_page' => 'nullable|integer|min:1',
        ]);

        $post = $this->request->post;

        $search = isset($post['search']) && is_array($post['search'])
            ? $post['search']
            : [];

        $termRaw        = $search['term']          ?? '';
        $currentPageRaw = $search['current_page']  ?? 1;
        $perPageRaw     = $search['per_page']      ?? 10;

        $term = is_string($termRaw) ? $termRaw : '';
        $currentPage = is_numeric($currentPageRaw) ? (int) $currentPageRaw : 1;
        $perPage     = is_numeric($perPageRaw) ? (int) $perPageRaw : 10;

        // Kontrollera om söktermen är tom, och returnera tomma resultat om så är fallet
        if ($term === '') {
            return $this->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'term' => $term,
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => 0,
                ],
            ]);
        }

        // Utför sökningen i User-modellen med hjälp av QueryBuilder
        /** @var array{
         *     data: list<\App\Models\User>,
         *     search: array<string,mixed>
         * } $results
         */
        $results = User::with('status')
            ->search($term, ['first_name', 'last_name', 'email'], $perPage, $currentPage);

        // Formatera resultaten som JSON
        $data = array_map(
            /**
             * @param \App\Models\User $user
             * @return array<string,mixed>
             */
            function (User $user): array {
                $arr = $user->toArray();
                $avatarRaw = $arr['avatar'] ?? null;
                $path = is_string($avatarRaw) && $avatarRaw !== ''
                    ? $avatarRaw
                    : '/images/graphics/avatar.png';

                $arr['avatar_url'] = versioned_file($path);
                return $arr;
            },
            $results['data']
        );

        return $this->json([
            'success' => true,
            'data' => $data,
            'meta' => $results['search'], // Metadata som t.ex. term, current_page, last_page
        ]);
    }

    public function deletedUsers(): JsonResponse
    {
        // Validera förfrågan för att säkerställa att rätt data skickas med
        $this->validateRequest([
            'search.term' => 'required|string|min:1',
            'search.current_page' => 'nullable|integer|min:1',
        ]);

        $post = $this->request->post;

        $search = isset($post['search']) && is_array($post['search'])
            ? $post['search']
            : [];

        $termRaw        = $search['term']          ?? '';
        $currentPageRaw = $search['current_page']  ?? 1;
        $perPageRaw     = $search['per_page']      ?? 10;

        $term = is_string($termRaw) ? $termRaw : '';
        $currentPage = is_numeric($currentPageRaw) ? (int) $currentPageRaw : 1;
        $perPage     = is_numeric($perPageRaw) ? (int) $perPageRaw : 10;

        // Kontrollera om söktermen är tom, och returnera tomma resultat om så är fallet
        if ($term === '') {
            return $this->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'term' => $term,
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => 0,
                ],
            ]);
        }

        // Utför sökningen i User-modellen med hjälp av QueryBuilder
        /** @var array{
         *     data: list<\App\Models\User>,
         *     search: array<string,mixed>
         * } $results
         */
        $results = User::with('status')
            ->getOnlySoftDeleted()
            ->search($term, ['first_name', 'last_name', 'email'], $perPage, $currentPage);

        $data = array_map(
            /**
             * @param \App\Models\User $user
             * @return array<string,mixed>
             */
            function (User $user): array {
                $arr = $user->toArray();
                $avatarRaw = $arr['avatar'] ?? null;
                $path = is_string($avatarRaw) && $avatarRaw !== ''
                    ? $avatarRaw
                    : '/images/graphics/avatar.png';

                $arr['avatar_url'] = versioned_file($path);
                return $arr;
            },
            $results['data']
        );

        return $this->json([
            'success' => true,
            'data' => $data,
            'meta' => $results['search'], // Metadata som t.ex. term, current_page, last_page
        ]);
    }
}
