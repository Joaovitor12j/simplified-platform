<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Core\Domain\Repositories\UserRepositoryInterface;
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
