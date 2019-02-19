<?php

namespace Laravel\Subscriptions;

use Laravel\Subscriptions\Models\Feature;
use Laravel\Subscriptions\Models\Plan;

/**
 * Class SubscriptionBuilder
 *
 * @package     Laravel\Subscriptions
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 */
class PlanFeatureBuilder
{
    protected $plan;
    protected $feature;
    public function __construct(Plan $plan, Feature $feature)
    {
        $this->plan = $plan;
        $this->feature = $feature;
    }
}
