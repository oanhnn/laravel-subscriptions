<?php

namespace Laravel\Subscriptions;

use Laravel\Subscriptions\Models\Feature;
use Laravel\Subscriptions\Models\PlanSubscription;

/**
 * Class SubscriptionAbility
 *
 * @package     Laravel\Subscriptions
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 */
class SubscriptionAbility
{
    /**
     * Subscription model instance.
     *
     * @var \Laravel\Subscriptions\Models\PlanSubscription
     */
    protected $subscription;

    /**
     * Create a new Subscription instance.
     *
     * @param \Laravel\Subscriptions\Models\PlanSubscription $subscription
     */
    public function __construct(PlanSubscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Determine if the feature is enabled and has
     * available uses.
     *
     * @param  int|string|\Laravel\Subscriptions\Models\Feature $feature
     * @return bool
     */
    public function canUse($feature): bool
    {
        // Get features and usage
        $value = $this->value($feature);

        if (is_null($value)) {
            return false;
        }

        // Match "bool" type value
        if ($this->enabled($feature) === true) {
            return true;
        }

        // If the feature value is zero, let's return false
        // since there's no uses available. (useful to disable
        // countable features)
        if ($value === '0') {
            return false;
        }

        // Check for available uses
        return $this->remainings($feature) > 0;
    }

    /**
     * Get how many times the feature has been used.
     *
     * @param  int|string|\Laravel\Subscriptions\Models\Feature $feature
     * @return int
     */
    public function consumed($feature): int
    {
        $feature = Feature::findFeature($feature);

        if (!$this->subscription->relationLoaded('usage')) {
            $this->subscription->usages()->getEager();
        }

        /** @var \Laravel\Subscriptions\Models\PlanSubscriptionUsage $usage */
        foreach ($this->subscription->usages as $usage) {
            if ($usage->feature_id === $feature->getKey() && !$usage->isExpired()) {
                return (int) $usage->used;
            }
        }

        return 0;
    }

    /**
     * Get the available uses.
     *
     * @param  int|string|\Laravel\Subscriptions\Models\Feature $feature
     * @return int
     */
    public function remainings($feature): int
    {
        return (int)$this->value($feature) - $this->consumed($feature);
    }

    /**
     * Check if subscription plan feature is enabled.
     *
     * @param  int|string|\Laravel\Subscriptions\Models\Feature $feature
     * @return bool
     */
    public function enabled($feature): bool
    {
        $value = $this->value($feature);

        if (is_null($value)) {
            return false;
        }

        // If value is one of the positive words configured then the
        // feature is enabled.
        if (in_array(strtoupper($value), config('subscriptions.positive_words'))) {
            return true;
        }

        return false;
    }

    /**
     * Get feature value.
     *
     * @param  int|string|\Laravel\Subscriptions\Models\Feature $feature
     * @param  mixed $default
     * @return mixed
     */
    public function value($feature, $default = null)
    {
        $feature = Feature::findFeature($feature);

        if ($feature) {
            if (!$this->subscription->plan->relationLoaded('features')) {
                $this->subscription->plan->features()->getEager();
            }

            /** @var \Laravel\Subscriptions\Models\Feature $pFeature */
            foreach ($this->subscription->plan->features as $pFeature) {
                if ($pFeature->getKey() === $feature->getKey()) {
                    return $pFeature->pivot->value;
                }
            }
        }

        return $default;
    }
}
