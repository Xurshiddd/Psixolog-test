<?php

namespace App\Application\AdminGuests\Queries;

use App\Application\AdminGuests\Data\AdminGuestFilters;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminGuestQueryFactory
{
    public function makeListQuery(AdminGuestFilters $filters): Builder
    {
        $query = User::query()
            ->where('role', 'guest')
            ->whereNull('merged_into_user_id')
            ->select([
                'id',
                'name',
                'phone',
                'picture',
                'created_at',
            ])
            ->with([
                'guest:id,user_id,father_name,address,desired_position,application_status,applied_at',
                'usersCategory:id,name',
                'usersTestsResults' => fn ($query) => $query
                    ->select('modules.id', 'modules.name')
                    ->orderBy('modules.name'),
            ]);

        $this->applyFilters($query, $filters);

        return $query;
    }

    public function makeExcelExportQuery(AdminGuestFilters $filters): Builder
    {
        $query = User::query()
            ->where('role', 'guest')
            ->whereNull('merged_into_user_id')
            ->select([
                'id',
                'name',
                'phone',
                'created_at',
            ])
            ->with([
                'guest:id,user_id,father_name,address,desired_position,application_status',
                'usersTestsResults' => fn ($query) => $query->select('modules.id'),
            ]);

        $this->applyFilters($query, $filters);

        return $query;
    }

    private function applyFilters(Builder $query, AdminGuestFilters $filters): void
    {
        if ($filters->search !== null) {
            $search = $filters->search;
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($filters->desiredPosition !== null) {
            $query->whereHas('guest', function (Builder $builder) use ($filters): void {
                $builder->where('desired_position', $filters->desiredPosition);
            });
        }

        if ($filters->applicationStatus !== null) {
            $query->whereHas('guest', function (Builder $builder) use ($filters): void {
                $builder->where('application_status', $filters->applicationStatus);
            });
        }

        if ($filters->testStatus === 'submitted') {
            $query->whereHas('usersTestsResults');
        } elseif ($filters->testStatus === 'not_submitted') {
            $query->whereDoesntHave('usersTestsResults');
        }

        if ($filters->categoryId !== null) {
            $query->whereHas('usersCategory', function (Builder $builder) use ($filters): void {
                $builder->where('category_id', $filters->categoryId);
            });
        }
    }
}
