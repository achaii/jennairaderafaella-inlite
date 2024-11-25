<?php

namespace Jennairaderafaella\Inlite\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

trait Configuration_For_Route
{

    /**
     * Initialize API routes and handle fallback for undefined routes.
     *
     * This method initializes API routes and sets a fallback response for undefined routes.
     * The fallback response returns a JSON error message with status code 404.
     *
     * @return void
     */
    public static function api()
    {
        self::get_route_init_controller_api();

        Route::fallback(function () {
            return response()->json([
                'error' => '404',
                'message' => 'Not Found'
            ], 404);
        });
    }

    /**
     * Initialize web routes and handle fallback for undefined routes.
     *
     * This method initializes web routes and sets a fallback response based on the
     * authentication status:
     * - If authenticated, returns a 404 error.
     * - If not authenticated, returns a 404 error.
     *
     * @return void
     */
    public static function web()
    {
        self::get_route_init_controller();

        Route::fallback(function () {
            return Auth::guard()->check() ? abort(404) : abort(404);
        });
    }

    /**
     * Initialize API and web route groups with their respective route files.
     *
     * This method initializes two route groups:
     * - 'api': Prefixes API routes with '/api' and applies 'api' middleware.
     * - 'web': Groups web routes and applies 'web' middleware.
     *
     * @return void
     */
    private static function route_initialize()
    {
        Route::middleware('web')
            ->group(__DIR__ . '/../Routes/Web.php');
    }

    /**
     * Initialize routes for controllers from default module directories.
     *
     * This method initializes routes for controllers based on modules of type 'controller'
     * from the default module directory ('I').
     *
     * @return void
     */
    private static function get_route_init_controller(): void
    {
        $default = self::directories_initialize_module()['custome'] ?? [];

        foreach ($default as $item) {
            if ($item && $item['modules'] && $item['modules-type'] == 'controller' && $item['modules-init'] === 'inlite') {
                self::get_route_for_controller($item);
            }
        }
    }

    /**
     * Initialize API routes for controllers from additional module directories.
     *
     * This method initializes routes for controllers based on modules of type 'controller'
     * from an additional module directory ('II') specifically for API routes.
     *
     * @return void
     */
    private static function get_route_init_controller_api(): void
    {
        $default = self::directories_initialize_module()['default']['II'] ?? [];
        foreach ($default as $item) {
            if ($item && $item['modules'] && $item['modules-type'] == 'controller' && $item['modules-init'] === 'inlite/api') {
                self::get_route_for_controller($item);
            }
        }
    }

    /**
     * Initialize routes for controllers based on module configuration.
     *
     * This method initializes routes for controllers based on the module configuration.
     * It sets up routes for each controller defined in the module.
     *
     * @param array $init Initialization data for the module.
     * @return void
     */
    private static function get_route_for_controller(array $init = []): void
    {
        Route::generate($init['modules-name'], ucwords($init['modules-name']), [
            'name' => $init['modules-name'] . '.' . $init['modules-name'],
            'middleware' => $init['modules-middleware'],
        ]);
    }

    /**
     * Convert module name to formatted string.
     *
     * This method converts a module name string into a formatted string suitable
     * for use in routes and views.
     *
     * @param string $data The module name to format.
     * @return string The formatted module name.
     */
    private static function name_of_modules(string $data)
    {
        return str_replace(' ', '_', ucwords(trim(str_replace('_', ' ', $data))));
    }

    /**
     * Replace underscores with hyphens in a string.
     *
     * This method replaces underscores with hyphens in a given string,
     * typically used for formatting file names.
     *
     * @param string $data The string to process.
     * @return string The processed string with underscores replaced by hyphens.
     */
    private static function replace_name(string $data)
    {
        return str_replace('_', '-', $data);
    }

    /**
     * Replace underscores with hyphens in a string and convert to lowercase.
     *
     * This method replaces underscores with hyphens in a given string and converts
     * the result to lowercase.
     *
     * @param string $data The string to process.
     * @return string The processed string with underscores replaced by hyphens and converted to lowercase.
     */
    private static function replace_to_lower(string $data)
    {
        return str_replace('_', '-', $data);
    }
}
