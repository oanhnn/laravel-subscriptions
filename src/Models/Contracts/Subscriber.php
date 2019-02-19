<?php

namespace Laravel\Subscriptions\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Subscriptions\Models\Plan;
use Laravel\Subscriptions\Models\PlanSubscription;

/**
 * Interface Subscriber
 *
 * @package     Laravel\Subscriptions\Models\Contracts
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 */
interface Subscriber
{
    /**
     * The subscriber may have many subscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function subscriptions(): MorphMany;

    /**
     * Get a subscription by name.
     *
     * @param  string $subscription The Subscription name
     * @return \Laravel\Subscriptions\Models\PlanSubscription|null
     */
    public function subscription(string $subscription): ?PlanSubscription;

    /**
     * Check if the subscriber has a given subscription.
     *
     * @param  string $subscription The Subscription name
     * @param  string|null $planSlug
     * @return bool
     */
    public function subscribed(string $subscription, string $planSlug = null): bool;

    /**
     * Subscribe user to a new plan.
     *
     * @param  string $subscription The Subscription name
     * @param  \Laravel\Subscriptions\Models\Plan $plan
     * @return \Laravel\Subscriptions\SubscriptionBuilder
     */
    public function newSubscription(string $subscription, Plan $plan);

    /**
     * Get subscription usage manager instance.
     *
     * @param  string $subscription The Subscription name
     * @return \Laravel\Subscriptions\SubscriptionUsageManager
     */
    public function subscriptionUsage(string $subscription);
}
