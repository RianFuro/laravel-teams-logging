<?php

namespace RianFuro\LaravelTeamsLogging;

use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class LoggerServiceProvider extends ServiceProvider
{
    /**
     * Normalized Laravel Version
     *
     * @var string
     */
    protected $version;

    /**
     * True when booted.
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * True when enabled, false disabled an null for still unknown
     *
     * @var bool
     */
    protected $enabled = null;

    /**
     * True when this is a Lumen application
     *
     * @var bool
     */
    protected $is_lumen = false;

    /**
     * @param Application $app
     */
    public function __construct($app = null)
    {
        if (!$app) {
            $app = app();   //Fallback when $app is not given
        }
        $this->app      = $app;
        $this->version  = $app->version();
        $this->is_lumen = Str::contains($this->version, 'Lumen');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->publishes([
            __DIR__ . '/config/teams.php' => config_path('teams.php'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // $this->app->bind('logger', 'RianFuro\LaravelTeamsLogging\LoggerServiceProvider');
    }
}
