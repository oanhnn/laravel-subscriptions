<?php

use Faker\Generator;
use Laravel\Subscriptions\Models\Plan;
use Laravel\Subscriptions\Period;

$factory->define(Plan::class, function (Generator $faker) {
    return [
        'name' => $faker->unique()->word,
        'description' => $faker->sentence,
        'price' => $faker->numberBetween(0, 9),
        'signup_fee' => $faker->numberBetween(0, 5),
        'interval_unit' => $faker->randomElement([Period::MONTH, Period::YEAR]),
        'interval_count' => 1,
        'trial_period' => $faker->numberBetween(0, 10),
        'trial_interval' => Period::DAY,
    ];
});
