<?php

namespace Laravel\Subscriptions;

use Laravel\Subscriptions\Models\Feature;
use Laravel\Subscriptions\Models\PlanSubscription;

/**
 * Class SubscriptionUsageManager
 *
 * @package     Laravel\Subscriptions
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 */
class SubscriptionUsageManager
{
    /**
     * Subscription model instance.
     *
     * @var \Laravel\Subscriptions\Models\PlanSubscription
     */
    protected $subscription;

    /**
     * Create new Subscription Usage Manager instance.
     *
     * @param \Laravel\Subscriptions\Models\PlanSubscription $subscription
     */
    public function __construct(PlanSubscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Record usage.
     *
     * This will create or update a usage record.
     *
     * @param  int|string|\Laravel\Subscriptions\Models\Feature $feature
     * @param  int $uses
     * @param  bool $incremental
     * @return \Laravel\Subscriptions\Models\PlanSubscriptionUsage
     * @throws \Throwable
     */
    public function record($feature, int $uses = 1, bool $incremental = true)
    {
        /** @var \Laravel\Subscriptions\Models\Feature $feature */
        $feature = Feature::findFeature($feature);

        $usage = $this->subscription->usages()->firstOrNew([
            'feature_id' => $feature->getKey(),
        ]);

        if ($feature->isResettable()) {
            // Set expiration date when the usage record is new or doesn't have one.
            if (is_null($usage->valid_until)) {
                // Set date from subscription creation date so the reset period match the period specified
                // by the subscription's plan.
                $usage->valid_until = $feature->getResetTime($this->subscription->created_at);
            } elseif ($usage->isExpired()) {
                // If the usage record has been expired, let's assign
                // a new expiration date and reset the uses to zero.
                $usage->valid_until = $feature->getResetTime($usage->valid_until);
                $usage->used = 0;
            }
        }

        $usage->used = max($incremental ? $usage->used + $uses : $uses, 0);

        $usage->saveOrFail();

        return $usage;
    }

    /**
     * Reduce usage.
     *
     * @param int $featureId
     * @param int $uses
     * @return \Laravel\Subscriptions\Models\PlanSubscriptionUsage
     * @throws \Throwable
     */
    public function reduce($featureId, $uses = 1)
    {
        return $this->record($featureId, -$uses);
    }

    /**
     * Clear usage data.
     *
     * @return self
     */
    public function clear()
    {
        $this->subscription->usages()->delete();

        return $this;
    }
}
