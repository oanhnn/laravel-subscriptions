<?php

namespace Laravel\Subscriptions\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Laravel\Subscriptions\Models\Concerns\BelongsToPlan;

class PlanFeature extends Pivot
{
    use SoftDeletes;

    /**
     * PlanFeature constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setTable(Config::get('subscriptions.tables.plan_features', 'plan_features'));
        parent::__construct($attributes);
    }
}
