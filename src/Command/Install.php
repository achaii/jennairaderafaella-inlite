<?php

namespace Jennairaderafaella\Inlite\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     * 
     * Defines the command to be run in the terminal: `php artisan jennai:install`
     *
     * @var string
     */
    protected $signature = 'jenna:install';

    /**
     * The console command description.
     * 
     * Provides a brief description of what the command does when listed in the `php artisan list` command.
     *
     * @var string
     */
    protected $description = 'Setup modules and dependencies.';

    /**
     * Execute the console command.
     * 
     * - Initializes the modules directory if necessary.
     * - Updates the Composer autoload configuration.
     * - Outputs success messages.
     *
     * @return void
     */
    public function handle()
    {
        // Output info to the console
        $this->info('Setting up modules...');

        // Initialize modules and update autoload configuration
        $this->initializeModules();
        $this->updateComposerAutoload();

        // Output success message to the console
        $this->info('Setup modules successfully.');
    }

    /**
     * Initialize modules directory if it doesn't exist.
     * 
     * - Copies required error and layout views from the package to the resources/views directory.
     * - Creates the modules directory if it doesn't exist.
     * - Moves the _modules.yaml file to the correct location.
     *
     * @return void
     */
    public function initializeModules()
    {
        if (env('MODULE_HIDDEN_TOKEN')) {
            return;
        }

        try {
            // Check if 'errors' directory exists, if not copy it
            $errorsPath = resource_path('views/errors');
            if (!File::exists($errorsPath)) {
                File::copyDirectory(
                    base_path('vendor/jennairaderafaella/inlite/src/dist/resources/views/errors'),
                    base_path('resources/views/errors')
                );
            }

            // Check if 'layouts' directory exists, if not copy it
            $layoutsPath = resource_path('views/layouts');
            if (!File::exists($layoutsPath)) {
                File::copyDirectory(
                    base_path('vendor/jennairaderafaella/inlite/src/dist/resources/views/layouts'),
                    resource_path('views/layouts')
                );
            }

            // Create 'modules' directory if it doesn't exist
            if (!file_exists('resources/views/modules')) {
                mkdir('resources/views/modules', 0755, true);
            }

            // Move _modules.yaml file to its destination
            if (!file_exists('resources/views/modules/_modules.yaml')) {
                File::copy(
                    base_path('vendor/jennairaderafaella/inlite/src/dist/resources/views/modules/_modules.yaml'),
                    resource_path('views/modules/_modules.yaml')
                );
            }

            $env = base_path('.env');
            $envExample = base_path('.env.example');

            // List of variables to add or update
            $variables = [
                'MODULE_HIDDEN_TOKEN' => 'c5326e51-2865-46cd-99fa-119169298b65',
                'MODULE_LOCALE' => 'en',
                'MODULE_TIMEZONE' => 'UTC',
                'MODULE_CIPHER_KEY' => '',
                'MODULE_CIPHER_SIGN' => ''
            ];
            
            // Function to update or append variables in an environment file
            function updateEnvFile($filePath, $variables) {
                if (file_exists($filePath)) {
                    $envContent = file_get_contents($filePath);
            
                    foreach ($variables as $key => $value) {
                        $pattern = "/^" . preg_quote($key, '/') . "=/m";
            
                        // Update value if the key exists, or append it if not
                        if (preg_match($pattern, $envContent)) {
                            $envContent = preg_replace($pattern, $key . "=" . $value, $envContent);
                        } else {
                            $envContent .= PHP_EOL . $key . "=" . $value;
                        }
                    }
            
                    // Write the updated content back to the file
                    file_put_contents($filePath, $envContent);
                }
            }
            
            // Update the .env file
            updateEnvFile($env, $variables);
            
            // Update the .env.example file if the variables are not already in .env
            if (file_exists($envExample)) {
                foreach ($variables as $key => $value) {
                    if (!env($key)) {
                        updateEnvFile($envExample, [$key => $value]);
                    }
                }
            }
            
        } catch (ProcessFailedException $exception) {
            // If directory creation fails, output error
            $this->error('Failed to create modules directory.');
            $this->error($exception->getMessage());
        }
    }

    /**
     * Update Composer Autoload configuration file if exists.
     * 
     * - Updates the composer.json file to include autoloading for the newly created modules directory.
     * - Runs the `composer dump-autoload` command to regenerate the autoloader.
     *
     * @return void
     */
    public function updateComposerAutoload()
    {
        // Path to the composer.json file
        $composerJsonFile = base_path('composer.json');

        // Read the existing composer.json file
        $composerJson = json_decode(file_get_contents($composerJsonFile), true);

        // Add the new autoload entry for the 'Modules' directory
        $composerJson['autoload']['psr-4']['Resources\\Views\\Modules\\'] = 'resources/views/modules';

        // Save the updated composer.json file
        file_put_contents($composerJsonFile, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Create a new Process instance to run the `composer dump-autoload` command
        $process = new Process(['composer', 'dump-autoload']);
        $process->setTimeout(null);  // No timeout

        try {
            // Run the `composer dump-autoload` command
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            // If the process fails, output error message
            $this->error('Failed to run dump-autoload.');
            $this->error($exception->getMessage());
        }
    }
}
