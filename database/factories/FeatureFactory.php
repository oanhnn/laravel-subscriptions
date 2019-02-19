<?php

use Faker\Generator;
use Laravel\Subscriptions\Models\Feature;
use Laravel\Subscriptions\Period;

$factory->define(Feature::class, function (Generator $faker) {
    return [
        'name' => $faker->unique()->word,
        'description' => $faker->sentence,
        'resettable_interval' => $faker->randomElement([null, Period::DAY, Period::WEEK, Period::MONTH, Period::YEAR]),
        'resettable_period' => $faker->numberBetween(0, 2),
    ];
});
