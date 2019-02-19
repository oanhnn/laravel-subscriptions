<?php

namespace Laravel\Subscriptions\Events;

use Laravel\Subscriptions\Models\PlanSubscription;

/**
 * Class SubscriptionEvent
 *
 * @package     Laravel\Subscriptions\Events
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 */
abstract class SubscriptionEvent
{
    /**
     * @var PlanSubscription
     */
    protected $subscription;

    /**
     * Create a new event instance.
     *
     * @param  \Laravel\Subscriptions\Models\PlanSubscription $subscription
     */
    public function __construct(PlanSubscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * @return PlanSubscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }
}
