<?php

namespace Laravel\Subscriptions;

use Laravel\Subscriptions\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Class SubscriptionBuilder
 *
 * @package     Laravel\Subscriptions
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 */
class SubscriptionBuilder
{
    /**
     * The subscriber model that is subscribing.
     *
     * @var \Illuminate\Database\Eloquent\Model|\Laravel\Subscriptions\Models\Contracts\Subscriber
     */
    protected $subscriber;

    /**
     * The plan model that the subscriber is subscribing to.
     *
     * @var \Laravel\Subscriptions\Models\Plan
     */
    protected $plan;

    /**
     * The subscription name.
     *
     * @var string
     */
    protected $name;

    /**
     * Custom number of trial interval to apply to the subscription.
     *
     * This will override the plan trial interval.
     *
     * @var int|null
     */
    protected $trialInterval;

    /**
     * Custom number of trial period to apply to the subscription.
     *
     * This will override the plan trial period.
     *
     * @var int|null
     */
    protected $trialPeriod;

    /**
     * Do not apply trial to the subscription.
     *
     * @var bool
     */
    protected $skipTrial = false;

    /**
     * Create a new subscription builder instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $subscriber
     * @param  string $name Subscription name
     * @param  \Laravel\Subscriptions\Models\Plan $plan
     */
    public function __construct(Model $subscriber, string $name, Plan $plan)
    {
        $this->subscriber = $subscriber;
        $this->name = $name;
        $this->plan = $plan;
    }

    /**
     * Specify the trial duration period in days.
     *
     * @param  int $period
     * @param  string $interval
     * @return self
     * @throws \InvalidArgumentException
     */
    public function trialsOn(int $period = 1, string $interval = 'day')
    {
        if (!Period::isValidInterval($interval)) {
            // TODO: add exception message
            throw new InvalidArgumentException();
        }

        $this->trialInterval = $interval;
        $this->trialPeriod = $period;

        return $this;
    }

    /**
     * Do not apply trial to the subscription.
     *
     * @return self
     */
    public function skipTrial()
    {
        $this->skipTrial = true;

        return $this;
    }

    /**
     * Create a new subscription.
     *
     * @param  array $attributes
     * @return \Laravel\Subscriptions\Models\PlanSubscription
     * @throws \Throwable
     */
    public function create(array $attributes = [])
    {
        $trialEndsAt = null;

        if (!$this->skipTrial) {
            if (!is_null($this->trialPeriod) && $this->trialPeriod > 0) {
                $trial = new Period($this->trialInterval, $this->trialPeriod, now());
                $trialEndsAt = $trial->getEndAt();
            } elseif ($this->plan->hasTrial()) {
                $trial = new Period($this->plan->trial_interval, $this->plan->trial_period, now());
                $trialEndsAt = $trial->getEndAt();
            }
        }

        $period = new Period($this->plan->invoice_interval, $this->plan->invoice_period, $trialEndsAt);

        return $this->subscriber->subscriptions()->create(array_merge([
            'name' => $this->name,
            'plan_id' => $this->plan->getKey(),
            'trial_ends_at' => $trialEndsAt,
            'starts_at' => $period->getStartAt(),
            'ends_at' => $period->getEndAt(),
        ], $attributes));
    }
}
