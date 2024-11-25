<?php

namespace Jennairaderafaella\Inlite\Traits;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Collection;
use Symfony\Component\Yaml\Yaml;
use Jennairaderafaella\Inlite\View\{
    AppLayout as App,
    GuestLayout as Guest
};

trait Configuration_For_Scan_Directory
{

    private $getDefaultDirsModulesI = [],
        $getDefaultDirsModulesII = [];

    /**
     * Initialize module directories.
     *
     * This method initializes module directories based on stored or
     * scanned module data. It scans and filters module directories ('DirectoriesI' and 'DirectoriesII')
     * and saves the results in session for later use.
     *
     * @return array The initialized directories data.
     */
    public static function initialize()
    {
        return self::directories_initialize_module();
    }

    /**
     * Initialize Blade components for application and guest layouts.
     *
     * This method registers Blade components for the application layout (`app-layout`) and
     * guest layout (`guest-layout`).
     *
     * @return void
     */
    private static function initialize_view(): void
    {
        Blade::component('app-layout', App::class);
        Blade::component('guest-layout', Guest::class);
    }

    /**
     * Initialize module directories and configuration.
     *
     * This method initializes module directories and configuration based on stored or
     * scanned module data. It scans and filters module directories ('DirectoriesI' and 'DirectoriesII')
     * and saves the results in session for later use.
     *
     * @return array The initialized directories and configuration data.
     */
    private static function directories_initialize_module()
    {
        // Check if the module directory has been initialized in the session
        if (Session::has('directories_initialize_module')) {
            return Session::get('directories_initialize_module');
        }

        $getCombineDir = [];
        $getDefaultDirI = [];
        $getDefaultDirII = [];
        $directoriesModule = Session::get('get_directories_module') ?? self::get_directory_module();

        // Process directory
        foreach (['DirectoriesI', 'DirectoriesII'] as $dirName) {
            foreach ($directoriesModule[$dirName] as $dir) {
                $pluginKey = ($dirName === 'DirectoriesI') ? 'PluginI' : 'PluginII';
                $namespacePath = ($dirName === 'DirectoriesI') ? 'Resources/Views/Modules/' : 'Resources/Views/Modules/Api/';
                $pluginJson = self::get_module_plugin_json($dir, $pluginKey);

                if ($pluginJson !== null) {
                    ($dirName === 'DirectoriesI' ? $getDefaultDirI[] = $pluginJson : $getDefaultDirII[] = $pluginJson);
                    $getCombineDir[] = $pluginJson + ['modules-namespace' => $namespacePath . $dir];
                }
            }
        }

        // Remove null elements
        $getDefaultDirI = array_filter($getDefaultDirI);
        $getDefaultDirII = array_filter($getDefaultDirII);
        $getCombineDir = array_filter($getCombineDir);

        // Save results in session
        $directoriesInitializeModule = array_merge(
            self::arr('default', array_merge(
                self::arr('I', $getDefaultDirI, false),
                self::arr('II', $getDefaultDirII, false)
            ), false),
            self::arr('custome', $getCombineDir, false)
        );

        Session::put('directories_initialize_module', $directoriesInitializeModule);

        return $directoriesInitializeModule;
    }

    /**
     * Scan and retrieve module directories.
     *
     * This method scans and retrieves module directories ('DirectoriesI' and 'DirectoriesII').
     * It filters hidden directories and files based on configuration and saves the results
     * in session for later use.
     *
     * @return array The scanned and filtered module directories.
     */
    private static function get_directory_module()
    {
        $modulePath = resource_path('views/modules');
        if (!File::exists($modulePath)) {
            return array_merge(
                self::arr('DirectoriesI', [], false),
                self::arr('DirectoriesII', [], false)
            );
        }

        $apiModulePath = $modulePath . '/Api';
        $hiddenToken = env('MODULE_HIDDEN_TOKEN') . '_modules.yaml';

        // Initialize directory if it doesn't exist
        if (!file_exists($modulePath)) {
            self::initialize_view();
        }

        // Check and return directory from session if available
        if (Session::has('get_directories_module')) {
            return Session::get('get_directories_module');
        }

        // Get list of hidden directories and files
        $hiddenPackages = self::get_module_json()['hide-package'] ?? [];
        $moduleHidden = self::get_module_json_hidden() ?? [];

        // Scan and filter main directory
        $directoriesI = self::scan_and_filter($modulePath, $hiddenPackages, $hiddenToken, $moduleHidden);

        // Scan and filter API directory if available
        $directoriesII = file_exists($apiModulePath) ? self::scan_and_filter($apiModulePath, $hiddenPackages, $hiddenToken, $moduleHidden) : [];

        // Combine results and save them in session
        $directories = array_merge(
            self::arr('DirectoriesI', $directoriesI, false),
            self::arr('DirectoriesII', $directoriesII, false)
        );

        Session::put('get_directories_module', $directories);

        return $directories;
    }

    /**
     * Create an associative array based on the provided parameters.
     *
     * This function creates an associative array based on the provided parameters.
     * If $status is false, it returns an array with [$name => $data].
     * If $status is true, it creates a Collection from $data and optionally paginates it based on $perPage and $pageName.
     *
     * @param string $name The key name for the associative array.
     * @param array $data The data to be stored in the array.
     * @param bool $status Whether to paginate the data (default: false).
     * @param int|null $perPage Number of items per page for pagination (optional).
     * @param string|null $pageName Name of the page query parameter for pagination (optional).
     * @return array The associative array based on the provided parameters.
     */
    public static function arr(string $name, $data = [], bool $status = false, int $perPage = null, string $pageName = null): array
    {
        if (!$status) {
            return [$name => $data];
        }

        $collection = new Collection($data);

        return [
            $name => $perPage ? $collection->paginate($perPage, null, null, $pageName) : $collection
        ];
    }

    /**
     * Retrieve module configuration from YAML file.
     *
     * This method searches for a YAML file in the module directory and parses its contents.
     * It renames the YAML file if necessary and returns its parsed contents as an array.
     *
     * @return array The parsed module configuration from YAML.
     */
    private static function get_module_json()
    {
        $modulePath = resource_path('views/modules');
        $files = scandir($modulePath);
        $filename = null;

        // Search for YAML file
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'yaml') {
                $filename = $file;
                break;
            }
        }

        // If a YAML file is found, rename it if necessary
        if ($filename) {
            $newFilename = env('MODULE_HIDDEN_TOKEN') . '_modules.yaml';
            if ($filename !== $newFilename) {
                rename($modulePath . '/' . $filename, $modulePath . '/' . $newFilename);
            }

            // Full path to the renamed file
            $path = $modulePath . '/' . $newFilename;

            // Parse and return the contents of the YAML file
            return Yaml::parseFile($path) ?: [];
        }

        // Return an empty array if no YAML file is found
        return [];
    }

    /**
     * Scan and filter directories based on criteria.
     *
     * This method scans a directory and filters out unwanted directories and files based on
     * provided criteria such as hidden packages, tokens, and modules.
     *
     * @param string $path The directory path to scan.
     * @param array $hiddenPackages List of hidden packages.
     * @param string $hiddenToken Hidden token filename.
     * @param array $hiddenModules List of hidden modules.
     * @return array The filtered list of directories.
     */
    private static function scan_and_filter($path, $hiddenPackages, $hiddenToken, $hiddenModules)
    {
        $scanned = scandir($path);
        $result = [];
        foreach ($scanned as $item) {
            if ($item === '.' || $item === '..' || !is_dir("$path/$item") || in_array($item, $hiddenPackages) || $item === $hiddenToken || in_array($item, $hiddenModules)) {
                continue;
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Retrieve and parse plugin JSON configuration from module directory.
     *
     * This method retrieves and parses the plugin JSON configuration from the specified
     * module directory based on the mode ('PluginI' or 'PluginII').
     *
     * @param string $init The module directory to retrieve from.
     * @param string $mode The mode of the plugin ('PluginI' or 'PluginII').
     * @return array The parsed plugin JSON configuration.
     */
    private static function get_module_plugin_json(string $init, string $mode)
    {
        $basePath = '/views/modules/';
        $path = resource_path($basePath . ($mode === 'PluginI' ? '' : 'Api/') . $init . '/' . self::get_module_json()['name-package-yaml']);
        $parsedYaml = Yaml::parseFile($path);
        return $parsedYaml !== false ? $parsedYaml : [];
    }

    /**
     * Retrieve list of hidden modules from module configuration.
     *
     * This method retrieves the list of hidden modules from the module configuration JSON.
     * If no hidden modules are defined, it returns an empty array.
     *
     * @return array The list of hidden modules.
     */
    public static function get_module_json_hidden()
    {
        return self::get_module_json()['module-hidden'] ? self::get_module_json()['module-hidden'] : [];
    }
    
    /**
     * Retrieve the "name-package-yaml" property from the module JSON configuration.
     * 
     * @return mixed The value of the "name-package-yaml" property from the module's JSON file.
     */
    public static function get_module_name_package_yaml()
    {
        // Access and return the "name-package-yaml" field from the module JSON configuration
        return self::get_module_json()['name-package-yaml'];
    }

}
