<?php

namespace Jennairaderafaella\Inlite;

use Illuminate\Support\ServiceProvider;
use Jennairaderafaella\Inlite\Inlite as Module;
use Jennairaderafaella\Inlite\Command\{
    Install,
    InliteWeb,
    InliteApi,
};

class InliteServiceProvider extends ServiceProvider
{
    /**
     * Boot method for the service provider.
     * 
     * - Performs initialization tasks for the module.
     * - Registers artisan commands for managing modules.
     */
    public function boot()
    {
        // Retrieve the module instance from the application container
        $module = $this->app[Module::class];

        // Perform initialization tasks for the module
        $module->initialization();

        // Register artisan commands related to module installation and creation
        $this->commands([
            Install::class,
            InliteWeb::class,
            InliteApi::class,
        ]);
    }

    /**
     * Register method for the service provider.
     * 
     * - Registers the module as a singleton in the application container.
     * - Initializes PHP files inside module directories.
     * - Sets up router-related custom configurations.
     */
    public function register()
    {
        // Retrieve the module instance from the application container
        $module = $this->app[Module::class];

        // Initialize PHP files inside the Modules directories
        $module->initialization_php_inside_modules();

        // Register the Module class as a singleton in the application container
        $this->app->singleton(Module::class, function ($app) {
            return new Module($app);
        });

        // Register router configurations
        $this->router();
    }

    /**
     * Register a macro for the router to generate routes dynamically.
     * 
     * - Adds a custom macro 'generate' to the router.
     * - The macro allows generating routes automatically based on a prefix, controller, and options.
     *
     * @return void
     */
    private function router()
    {
        // Retrieve the module instance from the application container
        $module = $this->app[Module::class];

        // Define a custom macro 'generate' on the router
        $this->app['router']->macro('generate', function (string $prefix, string $controller, array $option = []) use ($module) {
            // Call the 'auto' method on the Module instance to generate routes
            return $module->auto($prefix, $controller, $option);
        });
    }
}
