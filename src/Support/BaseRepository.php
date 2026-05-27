<?php

namespace DevApps\LaravelModulesKit\Support;

use DevApps\LaravelModulesKit\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseRepository implements RepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function all(array $relations = []): Collection
    {
        return $this->query($relations)->get();
    }

    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->query($relations)->paginate($perPage);
    }

    public function findById(int $id, array $relations = []): ?Model
    {
        return $this->query($relations)->find($id);
    }

    public function findByUuid(string $uuid, array $relations = []): ?Model
    {
        return $this->query($relations)->where('uuid', $uuid)->first();
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    public function restore(Model $model): bool
    {
        if (!method_exists($model, 'restore')) {
            return false;
        }

        return (bool) $model->restore();
    }

    protected function query(array $relations = [])
    {
        return $this->model->newQuery()->with($relations);
    }
}
