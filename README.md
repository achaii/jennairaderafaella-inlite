# Jennairaderafaella/inlite

**jennairaderafaella/inlite** is a Laravel package designed to simplify maintenance with a lightweight version. It can be combined with other JavaScript frameworks, including Livewire. Additionally, this module can automatically initialize routes based on your preferences without interfering with other route functions. This package follows the Laravel MVC pattern and also supports the repository and/or service pattern methods. It is fully compatible with Laravel 11.
## Install
To install using Composer, run the following command:
```
composer require jennairaderafaella/inlite
```
Currently, Composer installation is still being prepared.

## Configuration
### For Laravel 11 and later:
To use the package, you can manually register it. Locate the `bootstrap/provider.php` file and add the following lines:

```PHP
    return [
        App\Providers\AppServiceProvider::class,
        \Jennairaderafaella\Inlite\InliteServiceProvider::class
    ];
```

### For Laravel 10:
Locate the config/app.php file and add the service provider in the appropriate section:
```PHP
    'providers' => ServiceProvider::defaultProviders()->merge([

    /*
    * Application Service Providers...
    */
    Jennairaderafaella\Inlite\InliteServiceProvider::class

    ])->toArray(),
```

## First Used
### Module Initialization:
To initialize the module, run the following command in your terminal:
```BASH
php artisan jenna:install
```
### Module Initialization:
To add a new module, execute this command:
```BASH
php artisan jenna:inlite namemodule
```
### Creating a Separate Folder for API and Views:
Sometimes, a module requires a dedicated folder to separate APIs and views. To achieve this, run the command below:
```BASH
php artian jenna:inlite/api namemodule
```
### Configuration js or/and css at directory modules
For Laravel Mix to build, configure the webpack.mix.js file and add the following line of code:
```
'resources/views/modules/**/**/*.js'
```
At vite folowing line this code, edit at resources/js/app.js:
```
import '../../../resources/views/modules/**/Assets/css/*.css';
import '../../../resources/views/modules/**/Assets/js/*.js';
```
## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.