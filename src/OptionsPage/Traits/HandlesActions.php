<?php

namespace Aikon\RoleManager\OptionsPage\Traits;

use Aikon\RoleManager\Request;

trait HandlesActions
{
    /**
     * The middleware
     *
     * @var callable[]
     */
    public array $middleware = [];

    /**
     * Errors
     *
     * @var array<string, string>
     */
    public array $errors = [];

    /**
     * Add post action
     *
     * @param string $action_key The keý in request to check
     * @param string $action_value The value that the key should have
     * @param callable $callback The callback to run, receives the request as argument
     * @return void
     */
    public function post_action(string $action_key, string $action_value, callable $callback): void
    {
        $action = isset($_POST[$action_key]) && is_string($_POST[$action_key])
            ? sanitize_text_field($_POST[$action_key])
            : null;

        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            $action !== $action_value
        ) {
            return;
        }

        $request = $this->run_middleware();

        $callback($request);
    }

    /**
     * Add get action
     *
     * @param string $action_key The keý in request to check
     * @param string $action_value The value that the key should have
     * @param callable $callback The callback to run, receives the request as argument
     * @return void
     */
    public function get_action(string $action_key, string $action_value, callable $callback): void
    {
        $action = isset($_GET[$action_key]) && is_string($_GET[$action_key])
            ? sanitize_text_field($_GET[$action_key])
            : null;

        if (
            $_SERVER['REQUEST_METHOD'] !== 'GET' ||
            $action !== $action_value
        ) {
            return;
        }

        $this->run_middleware();

        $callback(new Request());
    }

    /**
     * Add an error
     * @param string $key The key of the error
     * @param string $message The message to show
     * @return void
     */
    public function add_error(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    /**
     *  Get errors
     * @param string|null $key The key of the error
     * @return array<string,string>|string
     */
    public function errors(?string $key = null): array|string
    {
        if ($key) {
            return $this->errors[$key] ?? '';
        }

        return $this->errors;
    }

    /**
     * Add middleware
     *
    * @param callable $callback The callback to run, receives the request as an argument and must return an instance of Request
     * @return void
     */
    public function middleware(callable $callback): void
    {
        $this->middleware[] = $callback;
    }

    /**
     * Run middleware
     *
     * @return Request
     */
    private function run_middleware(): Request
    {
        $request = new Request();
        foreach ($this->middleware as $middleware) {
            $result = $middleware($request);
            if ($result instanceof Request) {
                $request = $result;
            }
        }

        return $request;
    }
}
