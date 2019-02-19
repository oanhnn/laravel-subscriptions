<?php

namespace Laravel\Subscriptions\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Laravel\Subscriptions\Events\SubscriptionCanceled;
use Laravel\Subscriptions\Models\Concerns\BelongsToPlan;
use Laravel\Subscriptions\Period;
use Laravel\Subscriptions\SubscriptionAbility;
use LogicException;

/**
 * Class PlanSubscription
 *
 * @package     Laravel\Subscriptions\Models
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 *
 * @property int                $id
 * @property int                $subscriber_id
 * @property string             $subscriber_type
 * @property int                $plan_id
 * @property string             $name
 * @property \DateTimeInterface $trial_ends_at
 * @property \DateTimeInterface $starts_at
 * @property \DateTimeInterface $ends_at
 * @property \DateTimeInterface $cancels_at
 * @property \DateTimeInterface $canceled_at
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 * @property \DateTimeInterface $deleted_at
 * @property-read \Laravel\Subscriptions\Models\Plan $plan
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Subscriptions\Models\PlanSubscriptionUsage $usages
 * @property-read \Illuminate\Database\Eloquent\Model|\Laravel\Subscriptions\Models\Contracts\Subscriber $subscriber
 *
 * @method static \Illuminate\Database\Eloquent\Builder byPlan(int|string|\Laravel\Subscriptions\Models\Plan $plan)
 * @method static \Illuminate\Database\Eloquent\Builder findEndedPeriod()
 * @method static \Illuminate\Database\Eloquent\Builder findEndedTrial()
 * @method static \Illuminate\Database\Eloquent\Builder findEndingPeriod(int $dayRange = 3)
 * @method static \Illuminate\Database\Eloquent\Builder findEndingTrial(int $dayRange = 3)
 */
class PlanSubscription extends Model
{
    use BelongsToPlan;
    use SoftDeletes;

    /** @var string */
    protected $primaryKey = 'id';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'subscriber_id',
        'subscriber_type',
        'plan_id',
        'name',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancels_at',
        'canceled_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'subscriber_id' => 'integer',
        'subscriber_type' => 'string',
        'plan_id' => 'integer',
        'name' => 'string',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancels_at' => 'datetime',
        'canceled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * @var array
     */
    protected $with = ['plan'];

    /**
     * Subscription Ability Manager instance.
     *
     * @var \Laravel\Subscriptions\SubscriptionAbility
     */
    protected $ability;

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setTable(Config::get('subscriptions.tables.plan_subscriptions', 'plan_subscriptions'));
        parent::__construct($attributes);
    }

    /**
     * Boot function for using with User Events.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Set period if it wasn't set
            if (!$model->ends_at) {
                $model->setNewPeriod();
            }
        });

        static::saved(function ($model) {
            /** @var PlanSubscription $model */
            if ($model->getOriginal('plan_id') && $model->getOriginal('plan_id') !== $model->plan_id) {
                Event::dispatch(new SubscriptionPlanChanged($model));
            }
        });
    }

    /**
     * Get the owning user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subscriber(): MorphTo
    {
        return $this->morphTo('subscriber', 'subscriber_type', 'subscriber_id');
    }

    /**
     * The subscription may have many usage.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usages(): HasMany
    {
        return $this->hasMany(
            Config::get('subscriptions.models.PlanSubscriptionUsage', PlanSubscriptionUsage::class),
            'subscription_id',
            'id'
        );
    }

    /**
     * Get Subscription Ability instance.
     *
     * @return \Laravel\Subscriptions\SubscriptionAbility
     */
    public function ability()
    {
        if (is_null($this->ability)) {
            return new SubscriptionAbility($this);
        }

        return $this->ability;
    }

    /**
     * Check if subscription is active.
     *
     * @return bool
     */
    public function active(): bool
    {
        return !$this->isEnded() || $this->onTrial();
    }

    /**
     * Check if subscription is inactive.
     *
     * @return bool
     */
    public function inactive(): bool
    {
        return !$this->active();
    }

    /**
     * Check if subscription is currently on trial.
     *
     * @return bool
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at ? now()->lt($this->trial_ends_at) : false;
    }

    /**
     * Check if subscription is canceled.
     *
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->canceled_at ? now()->gte($this->canceled_at) : false;
    }

    /**
     * Check if subscription period has ended.
     *
     * @return bool
     */
    public function isEnded(): bool
    {
        return $this->ends_at ? now()->gte($this->ends_at) : false;
    }

    /**
     * Cancel subscription.
     *
     * @param  bool $immediately
     * @return self
     * @throws \Throwable
     */
    public function cancel($immediately = false)
    {
        $this->cancels_at = now();

        if ($immediately) {
            $this->canceled_at = $this->cancels_at;
            $this->ends_at = $this->canceled_at;
        }

        $this->saveOrFail();

        static::getEventDispatcher()->dispatch(new SubscriptionCanceled($this));

        return $this;
    }

    /**
     * Change subscription plan.
     *
     * @param  int|string|\Laravel\Subscriptions\Models\Plan $plan
     * @return self
     * @throws \InvalidArgumentException
     * @throws \Throwable
     */
    public function changePlan($plan)
    {
        $plan = Plan::findPlan($plan);

        if (is_null($plan)) {
            throw new \InvalidArgumentException();
        }

        // TODO: check plan activate

        // If plans does not have the same billing frequency
        // (e.g., invoice_interval and invoice_period) we will update
        // the billing dates starting today, and since we are basically creating
        // a new billing cycle, the usage data will be cleared.
        if ($this->plan->invoice_interval !== $plan->invoice_interval
            || $this->plan->invoice_period !== $plan->invoice_period) {
            $this->setNewPeriod($plan->invoice_interval, $plan->invoice_period);
            $this->usage()->delete();
        }

        // Attach new plan to subscription
        $this->plan_id = $plan->getKey();
        $this->saveOrFail();

        return $this;
    }

    /**
     * Renew subscription period.
     *
     * @return self
     * @throws LogicException
     * @throws \Exception|\Throwable
     */
    public function renew()
    {
        if ($this->isEnded() && $this->isCanceled()) {
            throw new LogicException('Unable to renew canceled ended subscription.');
        }

        $subscription = $this;

        $this->getConnection()->transaction(function () use ($subscription) {
            // Clear usage data
            $subscription->usage()->delete();

            // Renew period
            $subscription->setNewPeriod();
            $subscription->canceled_at = null;
            $subscription->save();
        });

        return $this;
    }

    /**
     * Get bookings of the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  \Illuminate\Database\Eloquent\Model $subscriber
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfSubscriber(Builder $query, Model $subscriber): Builder
    {
        return $query->where('subscriber_type', $subscriber->getMorphClass())
            ->where('subscriber_id', $subscriber->getKey());
    }

    /**
     * Scope subscriptions with ending trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int $dayRange
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndingTrial(Builder $query, int $dayRange = 3): Builder
    {
        $from = now();
        $to = now()->addDays($dayRange);

        return $query->whereBetween('trial_ends_at', [$from, $to]);
    }

    /**
     * Scope subscriptions with ended trial.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedTrial(Builder $query): Builder
    {
        return $query->where('trial_ends_at', '<=', now());
    }

    /**
     * Scope subscriptions with ending periods.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int $dayRange
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndingPeriod(Builder $query, int $dayRange = 3): Builder
    {
        $from = now();
        $to = now()->addDays($dayRange);

        return $query->whereBetween('ends_at', [$from, $to]);
    }

    /**
     * Scope subscriptions with ended periods.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindEndedPeriod(Builder $query): Builder
    {
        return $query->where('ends_at', '<=', now());
    }

    /**
     * Set new subscription period.
     *
     * @param  string $invoiceInterval
     * @param  int $invoicePeriod
     * @param  string $start
     * @return self
     */
    protected function setNewPeriod(?string $invoiceInterval = null, ?int $invoicePeriod = null, $start = '')
    {
        if (empty($invoiceInterval)) {
            $invoiceInterval = $this->plan->invoice_interval;
        }

        if (empty($invoicePeriod)) {
            $invoicePeriod = $this->plan->invoice_period;
        }

        $period = new Period($invoiceInterval, $invoicePeriod, $start);

        $this->starts_at = $period->getStartAt();
        $this->ends_at = $period->getEndAt();

        return $this;
    }

    /**
     * Record feature usage.
     *
     * @param  string $featureSlug
     * @param  int $uses
     * @param  bool $incremental
     * @return \Laravel\Subscriptions\Models\PlanSubscriptionUsage
     */
    public function recordFeatureUsage(
        string $featureSlug,
        int $uses = 1,
        bool $incremental = true
    ): PlanSubscriptionUsage {
        /** @var \App\Models\PlanFeature $feature */
        $feature = $this->plan->features()->where('slug', $featureSlug)->first();

        /** @var \App\Models\PlanSubscriptionUsage $usage */
        $usage = $this->usage()->firstOrNew([
            'subscription_id' => $this->getKey(),
            'feature_id' => $feature->getKey(),
        ]);

        if ($feature->resettable_period) {
            // Set expiration date when the usage record is new or doesn't have one.
            if (is_null($usage->valid_until)) {
                // Set date from subscription creation date so the reset
                // period match the period specified by the subscription's plan.
                $usage->valid_until = $feature->getResetTime($this->created_at);
            } elseif ($usage->isExpired()) {
                // If the usage record has been expired, let's assign
                // a new expiration date and reset the uses to zero.
                $usage->valid_until = $feature->getResetTime($usage->valid_until);
                $usage->used = 0;
            }
        }

        $usage->used = max($incremental ? $usage->used + $uses : $uses, 0);

        $usage->save();

        return $usage;
    }

    /**
     * Reduce usage.
     *
     * @param string $featureSlug
     * @param int $uses
     * @return \App\Models\PlanSubscriptionUsage|null
     */
    public function reduceFeatureUsage(string $featureSlug, int $uses = 1): ?PlanSubscriptionUsage
    {
        $usage = $this->usage()->byFeatureSlug($featureSlug)->first();

        if (is_null($usage)) {
            return null;
        }

        $usage->used = max($usage->used - $uses, 0);

        $usage->save();

        return $this->recordFeatureUsage($featureSlug, -$uses);
    }

    /**
     * Determine if the feature can be used.
     *
     * @param string $featureSlug
     *
     * @return bool
     */
    public function canUseFeature(string $featureSlug): bool
    {
        $featureValue = $this->getFeatureValue($featureSlug);
        $usage = $this->usage()->byFeatureSlug($featureSlug)->first();

        if ($featureValue === 'true') {
            return true;
        }

        // If the feature value is zero, let's return false since
        // there's no uses available. (useful to disable countable features)
        if ($usage->expired() || is_null($featureValue) || $featureValue === '0' || $featureValue === 'false') {
            return false;
        }

        // Check for available uses
        return $this->getFeatureRemainings($featureSlug) > 0;
    }

    /**
     * Get how many times the feature has been used.
     *
     * @param  string $featureSlug
     * @return int
     */
    public function getFeatureUsage(string $featureSlug): int
    {
        /** @var \App\Models\PlanSubscriptionUsage $usage */
        $usage = $this->usage()->byFeatureSlug($featureSlug)->first();

        return !$usage->isExpired() ? $usage->used : 0;
    }

    /**
     * Get the available uses.
     *
     * @param  string $featureSlug
     * @return int
     */
    public function getFeatureRemainings(string $featureSlug): int
    {
        return $this->getFeatureValue($featureSlug) - $this->getFeatureUsage($featureSlug);
    }

    /**
     * Get feature value.
     *
     * @param  string $featureSlug
     * @return mixed
     */
    public function getFeatureValue(string $featureSlug)
    {
        $feature = $this->plan->features()->where('slug', $featureSlug)->first();

        return $feature->value ?? null;
    }
}
