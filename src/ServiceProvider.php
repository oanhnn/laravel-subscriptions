<?php

namespace Laravel\Subscriptions;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Laravel\Subscriptions\Models\Feature;
use Laravel\Subscriptions\Models\Plan;
use Laravel\Subscriptions\Models\PlanFeature;
use Laravel\Subscriptions\Models\PlanSubscription;
use Laravel\Subscriptions\Models\PlanSubscriptionUsage;
use PDO;

/**
 * Class ServiceProvider
 *
 * @package     Laravel\Subscriptions
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 */
class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // publishes
        if ($this->app->runningInConsole()) {
            $this->registerPublishesResources();
        }

        // other booting ...
        $this->registerBlueprintMacro();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // merges config
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/subscriptions.php', 'subscriptions');

        // register model
        $this->registerModel('Feature', Feature::class);
        $this->registerModel('Plan', Plan::class);
        $this->registerModel('PlanFeature', PlanFeature::class);
        $this->registerModel('PlanSubscription', PlanSubscription::class);
        $this->registerModel('PlanSubscriptionUsage', PlanSubscriptionUsage::class);
    }

    /**
     * @param string $name
     * @param string $altClassName
     */
    protected function registerModel(string $name, string $altClassName)
    {
        $class = $this->app['config']->get("subscriptions.models.{$name}", $altClassName);

        $this->app->singleton("laravel-subscriptions.{$name}", $class);

        if ($class !== $altClassName) {
            $this->app->alias("laravel-subscriptions.{$name}", $altClassName);
        }
    }

    /**
     * Register some macro for Blueprint
     */
    protected function registerBlueprintMacro()
    {
        // support json column for MySQL < 5.7.8
        if (!Blueprint::hasMacro('jsonable')) {
            Blueprint::macro('jsonable', function (string $column) {
                $driverName = DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
                $dbVersion = DB::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
                $isOldVersion = version_compare($dbVersion, '5.7.8', 'lt');

                $method = ($driverName === 'mysql' && $isOldVersion) ? 'text' : 'json';

                return $this->{$method}($column);
            });
        }
    }

    /**
     * Register some publishes resources
     */
    protected function registerPublishesResources()
    {
        $pkg = dirname(__DIR__);

        // config
        $this->publishes([
            $pkg . '/config/subscriptions.php' => base_path('config/subscriptions.php'),
        ], 'laravel-subscriptions-config');
        // migration
        $this->publishes([
            $pkg . '/database/migrations/subscriptions.php'
            => database_path('migrations/' . Carbon::now()->format('Y_m_d_His') . '_create_subscriptions_tables.php'),
        ], 'laravel-subscriptions-migrations');
//        // language
//        $this->publishes([
//            dirname(__DIR__) . '/resources/lang/subscriptions.php'
//            => resources_path('lang/subscriptions.php'),
//        ], 'laravel-subscriptions-lang');
    }
}
