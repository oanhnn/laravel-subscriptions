<?php

namespace Laravel\Subscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Laravel\Subscriptions\Models\Concerns\HasSlug;
use Laravel\Subscriptions\Period;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Translatable\HasTranslations;

/**
 * Class Plan
 *
 * @package     Laravel\Subscriptions\Models
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 *
 * @property int                $id
 * @property array              $name
 * @property array              $description
 * @property string             $slug
 * @property bool               $is_active
 * @property float              $price
 * @property float              $signup_fee
 * @property int                $currency
 * @property int                $trial_period
 * @property string             $trial_interval
 * @property int                $invoice_period
 * @property string             $invoice_interval
 * @property int                $grace_period
 * @property string             $grace_interval
 * @property int                $prorate_day
 * @property int                $prorate_period
 * @property int                $prorate_extend_due
 * @property int                $subscribers_limit
 * @property int                $sort_order
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 * @property \DateTimeInterface $deleted_at
 * @property-read \Illuminate\Support\Collection $features
 *
 * @method static \Illuminate\Database\Eloquent\Builder bySlug(string $slug)
 */
class Plan extends Model
{
    use HasSlug;
    use HasTranslations;
    use SoftDeletes;
    use SortableTrait;

    /** @var string */
    protected $primaryKey = 'id';

    /** @var array */
    protected $fillable = [
        'name',
        'description',
        'slug',
        'is_active',
        'price',
        'signup_fee',
        'currency',
        'trial_period',
        'trial_interval',
        'invoice_period',
        'invoice_interval',
        'grace_period',
        'grace_interval',
        'prorate_day',
        'prorate_period',
        'prorate_extend_due',
        'subscribers_limit',
        'sort_order',
    ];

    /** @var array */
    protected $casts = [
        'slug' => 'string',
        'is_active' => 'bool',
        'price' => 'float',
        'signup_fee' => 'float',
        'currency' => 'string',
        'trial_period' => 'integer',
        'trial_interval' => 'string',
        'invoice_period' => 'integer',
        'invoice_interval' => 'string',
        'grace_period' => 'integer',
        'grace_interval' => 'string',
        'prorate_day' => 'integer',
        'prorate_period' => 'integer',
        'prorate_extend_due' => 'integer',
        'subscribers_limit' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatable = [
        'name',
        'description',
    ];
    /**
     * The sortable settings.
     *
     * @var array
     */
    public $sortable = [
        'order_column_name' => 'sort_order',
    ];

    /**
     * Plan constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setTable(Config::get('subscriptions.tables.plans', 'plans'));
        parent::__construct($attributes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(
            Config::get('subscriptions.models.Feature', Feature::class),
            Config::get('subscriptions.tables.plan_features', 'plan_features'),
            'feature_id',
            'plan_id'
        )->as(Config::get('subscriptions.models.PlanFeature', PlanFeature::class));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(
            Config::get('subscriptions.models.PlanSubscription', PlanSubscription::class),
            'plan_id',
            'id'
        );
    }

    /**
     * Check if plan is free
     *
     * @return bool
     */
    public function isFree(): bool
    {
        return (float) $this->price <= 0.00;
    }

    /**
     * Check if plan has trial.
     *
     * @return bool
     */
    public function hasTrial(): bool
    {
        return Period::isValidInterval($this->trial_interval) && $this->trial_period > 0;
    }

    /**
     * Check if plan has grace.
     *
     * @return bool
     */
    public function hasGrace(): bool
    {
        return Period::isValidInterval($this->grace_interval) && $this->grace_period > 0;
    }

    /**
     * Activate the plan.
     *
     * @return $this
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
        return $this;
    }

    /**
     * Deactivate the plan.
     *
     * @return $this
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
        return $this;
    }

    /**
     * Get plan feature by the given slug.
     *
     * @param  string $slug
     * @return Feature|null
     */
    public function getFeatureBySlug(string $slug): ?Feature
    {
        return $this->features()->where('slug', $slug)->first();
    }

    /**
     * Find plan
     *
     * @param int|string|\Laravel\Subscriptions\Models\Plan $plan
     * @return Feature|null
     */
    final public static function findPlan($plan): ?Plan
    {
        if (is_int($plan)) {
            $plan = app(Feature::class)->find($plan);
        }

        if (is_string($plan)) {
            $plan = app(Plan::class)->bySlug($plan)->first();
        }

        return $plan instanceof Plan ? $plan : null;
    }
}
