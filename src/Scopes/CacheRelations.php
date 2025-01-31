<?php

namespace Ndinhbang\QueryCache\Scopes;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CacheRelations implements Scope
{
    public function __construct(
        protected DateTimeInterface|DateInterval|int|null $ttl = null,
        protected string|array                            $key = [],
        protected string|null                             $store = null,
        protected int|null                                $wait = null,
    )
    {
        //
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param BuilderContract $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(BuilderContract $builder, Model $model): void
    {
        // Since scopes are applied last, we can safely wrap the eager loaded relations
        // with a cache, but using a custom cache key for each of these, allowing the
        // next relationships to respect the callback and include this cache scope.
        $eager = $builder->getEagerLoads();
        if (empty($eager)) {
            return;
        }

        foreach ($eager as $key => $callback) {
            $eager[$key] = function (BuilderContract $eloquent) use ($callback): void {
                $callback($eloquent);
                // Always override the previous eloquent builder with the base cache parameters.
                // @phpstan-ignore-start
                $eloquent->getQuery()->cache(
                    $this->ttl,
                    $this->key,
                    $this->store,
                    $this->wait,
                    $eloquent,
                );
                // @phpstan-ignore-end
            };
        }

        $builder->setEagerLoads($eager);
    }
}
