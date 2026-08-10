<?php

namespace App\Application\AdminGuests\Services;

use App\Application\AdminGuests\Data\AdminGuestFilters;
use App\Application\AdminGuests\Queries\AdminGuestQueryFactory;
use App\Models\Guest;
use App\Models\User;
use App\Services\LookupCacheService;

class BuildAdminGuestPages
{
    public function __construct(
        private AdminGuestQueryFactory $adminGuestQueryFactory,
        private LookupCacheService $lookupCacheService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexProps(AdminGuestFilters $filters): array
    {
        $guests = $this->adminGuestQueryFactory
            ->makeListQuery($filters)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return [
            'guests' => $guests,
            'positions' => $this->distinctValues('desired_position'),
            'statuses' => $this->distinctValues('application_status'),
            'categories' => $this->lookupCacheService->categories(),
            'filters' => $filters->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showProps(User $user, AdminGuestFilters $filters): array
    {
        $user->load(['guest', 'usersTestsResults', 'usersCategory', 'passport']);

        return [
            'guest' => $user,
            'results' => $user->usersTestsResults,
            'allCategories' => $this->lookupCacheService->categories(),
            'filters' => $filters->toArray(),
            'page' => $filters->page,
        ];
    }

    /**
     * @return list<string>
     */
    private function distinctValues(string $column): array
    {
        return Guest::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }
}
