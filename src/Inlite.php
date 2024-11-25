<?php

namespace Jennairaderafaella\Inlite;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\File;
use Illuminate\Routing\Router;
use Jennairaderafaella\Inlite\Traits\{
    Configuration_For_Scan_Directory as B,
    Configuration_For_Route as D,
    Configuration_For_Init_Route as J,
};

class Inlite
{
    // Importing traits for modular functionality
    use B, D, J;

    protected Router $router;

    /**
     * Constructor method
     * 
     * @param Container $app The application container instance
     */
    public function __construct(protected Container $app)
    {
        // Initialize the router from the application container
        $this->router = $app->get('router');
    }

    /**
     * Initialize the application by setting up directories, views, and routes.
     * 
     * - Checks if the Modules directory exists and initializes it.
     * - Sets up views, routes, timezone, and pagination configuration.
     */
    public function initialization()
    {
        $inlitePath = resource_path("views/modules");

        // Path to the Modules directory
        if (File::exists($inlitePath)) {
            // Initialize module directories
            self::directories_initialize_module();
        }

        // Initialize views, routes, timezone, and pagination
        self::initialize_view();
        self::route_initialize();
    }
    
    /**
     * Initialize PHP files inside specific directories within the Modules directory.
     * 
     * - Defines base paths where PHP files will be loaded from.
     * - Scans predefined folders (e.g., Controllers, Models) for PHP files.
     * - Includes each PHP file found.
     */
    public function initialization_php_inside_modules()
    {
        // Base paths to search for PHP files inside Modules directory
        $basePaths = [
            resource_path("views/modules"),
            resource_path("views/modules/Api"),
        ];

        // Loop through each base path
        foreach ($basePaths as $basePath) {
            // Folders to include PHP files from
            $folders = ['Http/Controllers', 'Repositories', 'Models', 'Services', 'Helpers'];
            foreach ($folders as $folder) {
                // Include PHP files using glob based on folder path
                foreach (glob($basePath . "/*/{$folder}/*.php") as $file) {
                    include_once($file);
                }
            }
        }
    }
}
