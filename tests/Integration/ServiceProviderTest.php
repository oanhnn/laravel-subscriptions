<?php

namespace Tests\Integration;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithFakeDateTime;
use Tests\TestCase;

/**
 * Class ServiceProviderTest
 *
 * @package     Tests\Integration
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 */
class ServiceProviderTest extends TestCase
{
    use RefreshDatabase;
    use WithFakeDateTime;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Set up before test
     */
    protected function setUp()
    {
        $this->setUpFakeDateTime();

        parent::setUp();

        $this->files = new Filesystem();
    }

    /**
     * Clear up after test
     */
    protected function tearDown()
    {
        $this->files->delete([
            $this->app->configPath('subscriptions.php'),
            $this->app->databasePath('migrations/2019_02_14_000000_create_subscriptions_tables.php'),
        ]);

        parent::tearDown();
    }

    /**
     * @return Carbon
     */
    protected function fakeDateTime(): \DateTimeInterface
    {
        return Carbon::create(2019, 02, 14, 0, 0, 0);
    }

    /**
     * Test file subscriptions.php is existed in config directory after run
     *
     * php artisan vendor:publish --provider="Laravel\\Subscriptions\\ServiceProvider"
     *                            --tag=laravel-subscriptions-config
     *
     * @test
     */
    public function it_can_publish_vendor_config()
    {
        $sourceFile = dirname(dirname(__DIR__)) . '/config/subscriptions.php';
        $targetFile = base_path('config/subscriptions.php');

        $this->assertFileNotExists($targetFile);

        $this->artisan('vendor:publish', [
            '--provider' => 'Laravel\\Subscriptions\\ServiceProvider',
            '--tag' => 'laravel-subscriptions-config',
        ]);

        $this->assertFileExists($targetFile);
        $this->assertEquals(file_get_contents($sourceFile), file_get_contents($targetFile));
    }

    /**
     * Test migration files is existed in migration directory after run
     *
     * php artisan vendor:publish --provider="Laravel\\Subscriptions\\ServiceProvider"
     *                            --tag=laravel-subscriptions-migrations
     *
     * @test
     */
    public function it_can_publish_vendor_migrations()
    {
        $sourceFile = dirname(dirname(__DIR__)) . '/database/migrations/subscriptions.php';
        $targetFile = database_path('migrations/2019_02_14_000000_create_subscriptions_tables.php');

        $this->assertFileNotExists($targetFile);

        $this->artisan('vendor:publish', [
            '--provider' => 'Laravel\\Subscriptions\\ServiceProvider',
            '--tag' => 'laravel-subscriptions-migrations',
        ]);

        $this->assertFileExists($targetFile);
        $this->assertEquals(file_get_contents($sourceFile), file_get_contents($targetFile));
    }

    /**
     * Test migrate database
     */
    public function it_can_migrate_database()
    {
        $this->artisan('vendor:publish', [
            '--provider' => 'Laravel\\Subscriptions\\ServiceProvider',
            '--tag' => 'laravel-subscriptions-migrations',
        ]);

        $this->assertFileExists(database_path('migrations/2019_02_14_000000_create_subscriptions_tables.php'));

        $this->artisan('migrate');
//
//        $this->assertDatabaseHas(config('subscriptions.tables.features'));
//        $this->assertDatabaseHas(config('subscriptions.tables.plans'));
//        $this->assertDatabaseHas(config('subscriptions.tables.plan_features'));
//        $this->assertDatabaseHas(config('subscriptions.tables.plan_subscriptions'));
//        $this->assertDatabaseHas(config('subscriptions.tables.plan_subscription_usage'));
    }

    /**
     * Test config values are merged
     *
     * @test
     */
    public function it_provides_default_config()
    {
        $config = config('subscriptions');

        $this->assertTrue(is_array($config));

        // TODO: assert default config values
    }
}
