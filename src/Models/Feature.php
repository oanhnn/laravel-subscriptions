<?php

namespace Laravel\Subscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Laravel\Subscriptions\Models\Concerns\HasSlug;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Translatable\HasTranslations;

/**
 * Class Feature
 *
 * @package     Laravel\Subscriptions\Models
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 *
 * @property int                $id
 * @property array              $name
 * @property array              $description
 * @property string             $slug
 * @property string             $resettable_interval
 * @property int                $resettable_period
 * @property int                $sort_order
 * @property \DateTimeInterface $created_at
 * @property \DateTimeInterface $updated_at
 * @property \DateTimeInterface $deleted_at
 * @property-read \Illuminate\Support\Collection $plans
 *
 * @method static \Illuminate\Database\Eloquent\Builder bySlug(string $slug)
 */
class Feature extends Model
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
        'resettable_interval',
        'resettable_period',
        'sort_order',
    ];

    /** @var array */
    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'slug' => 'string',
        'resettable_interval' => 'array',
        'resettable_period' => 'integer',
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
     * Feature constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setTable(Config::get('subscriptions.tables.features', 'features'));
        parent::__construct($attributes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(
            Config::get('subscriptions.models.Plan', Plan::class),
            Config::get('subscriptions.tables.plan_features', 'plan_features'),
            'plan_id',
            'feature_id'
        )->as(Config::get('subscriptions.models.PlanFeature', PlanFeature::class));
    }

    /**
     * Find feature
     *
     * @param int|string|\Laravel\Subscriptions\Models\Feature $feature
     * @return Feature|null
     */
    final public static function findFeature($feature): ?Feature
    {
        if (is_int($feature)) {
            $feature = app(Feature::class)->find($feature);
        }

        if (is_string($feature)) {
            $feature = app(Feature::class)->bySlug($feature)->first();
        }

        return $feature instanceof Feature ? $feature : null;
    }
}
