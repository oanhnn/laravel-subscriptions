<?php

namespace Laravel\Subscriptions\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Laravel\Subscriptions\Models\Plan;

/**
 * Trait BelongsToPlan
 *
 * @package     Laravel\Subscriptions\Models\Concerns
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 */
trait BelongsToPlan
{
    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param  string $related
     * @param  string $foreignKey
     * @param  string $ownerKey
     * @param  string $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null);

    /**
     * The model always belongs to a plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            Config::get('subscriptions.models.Plan', Plan::class),
            'plan_id',
            'id'
        );
    }

    /**
     * Scope models by plan id.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int|string|\Laravel\Subscriptions\Models\Plan $plan
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws \InvalidArgumentException
     */
    public function scopeByPlan(Builder $query, $plan): Builder
    {
        if (is_int($plan)) {
            return $query->where('plan_id', $plan);
        }

        if (is_string($plan)) {
            $plan = app(Plan::class)->query()->bySlug($plan)->first();
        }

        if ($plan instanceof Plan) {
            return $query->where('plan_id', $plan->getKey());
        }

        throw new InvalidArgumentException('Invalid plan argument');
    }
}
