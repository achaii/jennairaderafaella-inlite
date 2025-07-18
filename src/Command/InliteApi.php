<?php

namespace Jennairaderafaella\Inlite\Command;

use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\File;
use Jennairaderafaella\Inlite\Inlite;
use Symfony\Component\Process\Exception\ProcessFailedException;

class InliteApi extends Command
{
    /**
     * The name and signature of the console command.
     * 
     * Defines the command to be run in the terminal: `php artisan jennai:create {mode} {type} {name}`
     * {mode} determines if the module is created as 'inlite/api' or 'inlite'.
     * {name} is the name of the module to be created.
     *
     * @var string
     */
    protected $signature = 'jenna:inlite/api {name : The name of the module}';

    /**
     * The console command description.
     * 
     * Describes the purpose of the command when listed in the `php artisan list` command.
     *
     * @var string
     */
    protected $description = 'Create a new module type only controller Modules directory. (e. jenna:inlite/api new_module)';

    /**
     * Execute the console command.
     * 
     * Retrieves the provided mode and name arguments, then calls `createModule()` 
     * to create the module in the specified mode.
     *
     * @return void
     */
    public function handle()
    {
        // Retrieve the mode and module name from the arguments
        $name = $this->argument('name');

        // Proceed to create the module based on the provided arguments
        $this->createModule($name);
    }

    /**
     * Create a new module directory in the specified mode.
     * 
     * Depending on the mode ('inlite/api' or 'inlite'), this method creates 
     * the module in the appropriate directory, sets up its configuration file 
     * (_modules.yaml), and copies necessary files from the package's default 
     * template.
     *
     * @param string $mode The mode of module creation ('inlite/api' or 'inlite').
     * @param string $name The name of the module to be created.
     * @return void
     */
    protected function createModule(string $name)
    {
        // Check if mode and name are provided, otherwise display an error
        if (!$name) {
            $this->error('Name is missing.');
            return;
        }

        try {

            $words = explode('_', $name);

            $words = array_map('ucfirst', $words);

            $words = implode('_', $words);

            // Check if the directory already exists, if not, create it
            if (!file_exists('resources/views/modules/Api/' . ucfirst($words))) {
                mkdir('resources/views/modules/Api/' . ucfirst($words), 0755, true);
            }

            // Create the module configuration file (_modules.yaml)
            touch(resource_path('views/modules/Api/' . ucfirst($words) . '/' . Inlite::get_module_name_package_yaml()));

            $configFile = resource_path('views/modules/Api/' . ucfirst($words) . '/' . Inlite::get_module_name_package_yaml());

            // Prepare the module configuration data
            $config = [
                "modules" => true,
                "modules-name" => "$name",
                "modules-name-route" => "",
                "modules-middleware" => [
                    "guest",
                ],
                "modules-development" => false,
                "modules-development-route" => "/dev",
                "modules-map" => "menu-1",
                "modules-type" => "controller",
                "controller" => [
                    [
                        "enable" => true,
                        "name" => "$name",
                    ]
                ],
            ];

            // Write the YAML configuration to the file
            $options = Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE;
            file_put_contents($configFile, Yaml::dump($config, 4, 2, $options));

            // Copy the default template files from the package to the new module's directory
            $source = File::directories(base_path('vendor/jennairaderafaella/the-moduless/src/dist/resources/views/modules/Api/NameModule'));
            $destination = resource_path('views/modules/Api/' . ucfirst($words));

            foreach ($source as $subfolder) {
                $folderName = basename($subfolder);

                $destinationPath = $destination . '/' . $folderName;

                // Copy the directories from the package's template to the module's directory
                File::copyDirectory($subfolder, $destinationPath);
            }
            // Output success message to the console
            $this->info('Module created successfully.');
        } catch (ProcessFailedException $exception) {
            // Handle errors during module creation
            $this->error('Failed created modules.');
        }
    }
}
