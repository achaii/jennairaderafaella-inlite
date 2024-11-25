<?php

namespace Jennairaderafaella\Inlite\Traits;

use Jennairaderafaella\Inlite\Middleware\Middleware as MainMiddleware;
use Illuminate\Routing\Controller as MainController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use ReflectionClass;
use ReflectionMethod;

trait Configuration_For_Init_Route
{

    protected Router $router;

    protected string $default_namespace;

    protected array $default_method = [
        'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'
    ];

    protected array $xdefault_method = [
        'XGET', 'XPOST', 'XPUT', 'XPATCH', 'XDELETE', 'XOPTIONS', 'XANY', 'VOLT', 'WIRE'
    ];

    protected $initialization_method;

    protected string|array $default_http_method;

    protected string $default_middleware;

    protected array $default_pattern = [
        ':any' => '([^/]+)',
        ':int' => '(\d+)',
        ':float' => '[+-]?([0-9]*[.])?[0-9]+',
        ':bool' => '(true|false|1|0)',
        'uuid' => '([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})',
        'date' => '([0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]))',
    ];

    /**
     * Automatically registers routes for controller methods based on configuration options.
     *
     * This method automatically registers routes for controller methods based on the provided
     * prefix, controller name, and additional options such as 'only', 'except', and 'patterns'.
     *
     * @param string $prefix The prefix for the routes.
     * @param string $controller The name of the controller class.
     * @param array $option Optional configuration options:
     *                      - 'only': Array of method names to include.
     *                      - 'except': Array of method names to exclude.
     *                      - 'patterns': Array of route patterns for method parameters.
     * @return void
     */
    public function auto(string $prefix, string $controller, array $option = []): void
    {
        $only = $option['only'] ?? [];
        $except = $option['except'] ?? [];
        $pattern = $option['patterns'] ?? [];

        $routeName = trim($options['as'] ?? ($options['name'] ?? trim($prefix, '/')), '.') . '.';

        if ($routeName === '.') {
            $routeName = '';
        }

        $this->router->group(array_merge($option, [
            'prefix' => $prefix,
            'as' => $routeName,
        ]), function () use ($controller, $only, $except, $pattern) {
            $class_mapping = $this->get_reflection_class($controller);

            if (class_exists($class_mapping)) {
                $class_reference = new ReflectionClass($class_mapping);

                foreach ($class_reference->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    // Skip base controller methods, non-public methods, and magic methods
                    if (
                        in_array($method->class, [MainController::class, "{$this->default_namespace}\\Http\\Controllers"]) ||
                        $method->getDeclaringClass()->getParentClass()->getName() === MainController::class ||
                        !$method->isPublic() || str_starts_with($method->name, '__')
                    ) {
                        continue;
                    }

                    $method_name = $method->name;
                    // Apply only and except filters
                    if ((!empty($only) && !in_array($method_name, $only)) ||
                        (!empty($except) && in_array($method_name, $except))
                    ) {
                        continue;
                    }

                    [$http_method, $route_name, $middleware] = $this->get_http_method($method_name);
                    [$end_points, $route_pattern] = $this->get_reflection_method($method, $pattern);

                    $end_point = implode('/', $end_points);

                    $handler = [$class_reference->getName(), $method->name];
                    $routePath = ($route_name !== $this->initialization_method ? $route_name : '') . "/{$end_point}";

                    $this->router
                        ->addRoute(array_map(fn ($method) => strtoupper($method), $http_method), $routePath, $handler)
                        ->where($route_pattern)->name("{$method->name}")->middleware($middleware);
                }
            }
        });
    }

    /**
     * Set initial configuration values for the router.
     *
     * This method sets initial configuration values for the router object,
     * including the router instance, default method name, default namespace,
     * default middleware, default route patterns, and default HTTP method.
     *
     * @param object $router The router instance to configure.
     * @return void
     */
    private function configuration(Router $router): void
    {
        $this->router = $router;
        $this->initialization_method = 'index';
        $this->default_namespace = '';
        $this->default_middleware = MainMiddleware::class;
        $this->default_pattern = $this->default_pattern ?? [];
        $this->default_http_method = $this->default_method ?: '*';
    }

    /**
     * Retrieve the fully qualified class name based on module configuration.
     *
     * This method retrieves the fully qualified class name of a controller based on
     * the provided $class name and module configuration.
     *
     * @param string $class The name of the controller class.
     * @return string|null The fully qualified class name if found, null otherwise.
     */
    private function get_reflection_class($class)
    {
        $package = self::directories_initialize_module()['custome'];

        foreach ($package as $item) {
            if (!$item){
                continue;
            }

            if ($item['modules-type'] === 'controller' && $item['modules-name'] === strtolower($class)) {
                return str_replace(['/', '.php'], ['\\', ''], $item['modules-namespace']) . '\\Http\\Controllers\\' . ucwords($item['modules-name']);
            }
        }

        return null;
    }

    /**
     * Determine HTTP method, route name, and middleware for a controller method.
     *
     * This method determines the HTTP method, route name, and middleware for a controller
     * method based on its name and configured default and extra methods.
     *
     * @param string $method_name The name of the controller method.
     * @return array An array containing HTTP method, route name, and middleware.
     */
    private function get_http_method(string $method_name): array
    {
        $http_method = $this->default_http_method;
        $middleware = null;

        foreach (array_merge($this->default_method, $this->xdefault_method) as $method) {
            $method = strtolower($method);
            if (stripos($method_name, $method, 0) === 0) {
                if (in_array($method, ['volt', 'wire'])) {
                    $http_method = ['GET', 'HEAD'];
                } elseif ($method !== 'xany') {
                    $http_method = [ltrim($method, 'x')];
                }
                $middleware = strpos($method, 'x') === 0 ? $this->default_middleware : null;
                $method_name = lcfirst(preg_replace('/' . $method . '_?/i', '', $method_name, 1));
                break;
            }
        }

        $method_name = strtolower(preg_replace('%([a-z]|[0-9])([A-Z])%', '\1-\2', $method_name));
        return [$http_method, $method_name, $middleware];
    }

    /**
     * Retrieve endpoint structure and route patterns for a method based on reflection.
     *
     * This method retrieves the endpoint structure and route patterns for a method
     * based on its reflection information and provided patterns.
     *
     * @param ReflectionMethod $method The reflection method object.
     * @param array $pattern Optional route patterns for method parameters.
     * @return array An array containing endpoint structure and route patterns.
     */
    private function get_reflection_method(ReflectionMethod $method, array $pattern = []): array
    {
        $route_pattern = [];
        $end_point = [];
        $merged_pattern = array_merge($this->default_pattern, $pattern);

        foreach ($method->getParameters() as $param) {
            $param_name = $param->getName();
            $type_hint = $param->hasType() ? $param->getType()->getName() : null;

            if ($this->get_route_param($type_hint)) {
                $route_pattern[$param_name] = $merged_pattern[$param_name] ?? ($this->default_pattern[":{$type_hint}"] ?? $this->default_pattern[':any']);
                $end_point[] = $param->isOptional() ? "{{$param_name}?}" : "{{$param_name}}";
            }
        }

        return [$end_point, $route_pattern];
    }

    /**
     * Check if a given type is a valid route parameter type.
     *
     * This method checks if a given type is a valid route parameter type,
     * considering primitive types, Eloquent models, enums, and other types.
     *
     * @param string|null $type The type to check.
     * @return bool True if the type is a valid route parameter type, false otherwise.
     */
    private function get_route_param(?string $type): bool
    {
        if (is_null($type) || in_array($type, ['int', 'float', 'string', 'bool', 'mixed'])) {
            return true;
        }
        if (class_exists($type) && is_subclass_of($type, Model::class)) {
            return true;
        }
        if (function_exists('enum_exists') && enum_exists($type)) {
            return true;
        }
        return false;
    }
}
