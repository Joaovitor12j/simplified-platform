<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private User $model
    ) {}

    public function findMany(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }
}
