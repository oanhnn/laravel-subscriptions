<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $tables = Config::get('subscriptions.tables');

        Schema::create($tables['features'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->jsonable('name');
            $table->jsonable('description')->nullable();
            $table->string('resettable_interval')->default('month');
            $table->smallInteger('resettable_period')->unsigned()->default(1);
            $table->mediumInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique('slug');
        });

        Schema::create($tables['plans'], function (Blueprint $table) {
            // Columns
            $table->increments('id');
            $table->string('slug');
            $table->jsonable('name');
            $table->jsonable('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('price')->default('0.00');
            $table->decimal('signup_fee')->default('0.00');
            $table->string('currency', 3);
            $table->smallInteger('trial_period')->unsigned()->default(0);
            $table->string('trial_interval')->default('day');
            $table->smallInteger('invoice_period')->unsigned()->default(0);
            $table->string('invoice_interval')->default('month');
            $table->smallInteger('grace_period')->unsigned()->default(0);
            $table->string('grace_interval')->default('day');
            $table->tinyInteger('prorate_day')->unsigned()->nullable();
            $table->tinyInteger('prorate_period')->unsigned()->nullable();
            $table->tinyInteger('prorate_extend_due')->unsigned()->nullable();
            $table->smallInteger('subscribers_limit')->unsigned()->nullable();
            $table->mediumInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique('slug');
        });

        Schema::create($tables['plan_features'], function (Blueprint $table) use ($tables) {
            // Columns
            $table->increments('id');
            $table->integer('plan_id')->unsigned();
            $table->integer('feature_id')->unsigned();
            $table->string('value');
            $table->smallInteger('resettable_period')->unsigned()->default(1);
            $table->string('resettable_interval')->default('month');
            $table->mediumInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();

            // Indexes
            $table->unique(['plan_id', 'slug']);
            $table->foreign('plan_id')->references('id')->on($tables['plans'])
                ->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::create($tables['plan_subscriptions'], function (Blueprint $table) use ($tables) {
            $table->increments('id');
            $table->morphs('subscriber');
            $table->integer('plan_id')->unsigned();
            $table->string('name');
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('cancels_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['subscriber_type', 'subscriber_id', 'name']);
            $table->foreign('plan_id')->references('id')->on($tables['plans'])
                ->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::create($tables['plan_subscription_usages'], function (Blueprint $table) use ($tables) {
            $table->increments('id');
            $table->integer('subscription_id')->unsigned();
            $table->integer('feature_id')->unsigned();
            $table->smallInteger('used')->unsigned();
            $table->dateTime('valid_until')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['subscription_id', 'feature_slug']);
            $table->foreign('subscription_id')->references('id')->on($tables['plan_subscriptions'])
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $tables = Config::get('subscriptions.tables');

        Schema::dropIfExists($tables['plan_subscription_usages']);
        Schema::dropIfExists($tables['plan_subscriptions']);
        Schema::dropIfExists($tables['plan_features']);
        Schema::dropIfExists($tables['plans']);
        Schema::dropIfExists($tables['features']);
    }
}
