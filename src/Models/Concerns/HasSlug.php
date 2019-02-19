<?php

namespace Laravel\Subscriptions\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Sluggable\HasSlug as Sluggable;
use Spatie\Sluggable\SlugOptions;

trait HasSlug
{
    use Sluggable;

    /**
     * Get the options for generating the slug.
     *
     * @return \Spatie\Sluggable\SlugOptions
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->doNotGenerateSlugsOnUpdate()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * Get models of the given slug.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $slug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }
}
