<?php

declare(strict_types=1);

namespace Aikon\RoleManager;

class Request
{
    /** @var array<mixed> */
    private array $request;

    /** @var array<string,string> */
    private array $errors = [];

    /** @var array<string,string|string[]> */
    private array $rules = [];

    /** @var array<string,mixed> */
    private array $validatedData = [];

    /**
     * Initialize the class
     */
    public function __construct()
    {
        $this->request = $_REQUEST;
    }

    /**
     * Set validation rules for specific keys
     *
     * @param array<string,string|string[]> $rules Array of keys with their validation rules
     * @return self
     */
    public function validate(array $rules): self
    {
        $this->rules = $rules;

        foreach ($rules as $key => $ruleString) {
            $value = $this->request[$key] ?? null;
            $ruleArray = is_string($ruleString) ? explode('|', $ruleString) : $ruleString;

            if ($this->validateValue($value, $ruleArray)) {
                $this->validatedData[$key] = $value;
            } else {
                $this->errors[$key] = "Validation failed for '$key'";
            }
        }

        return $this;
    }

    /**
     * Validate a single value against an array of rules
     * @param mixed $value The value to validate
     * @param array<string> $rules Array of rules to validate against
     */
    private function validateValue($value, array $rules): bool
    {
        foreach ($rules as $rule) {
            $parameters = [];

            // Handle rules with parameters (e.g., minlength:2)
            if (str_contains($rule, ':')) {
                [$rule, $paramString] = explode(':', $rule);
                $parameters = explode(',', $paramString);
            }

            switch (strtolower($rule)) {
                case 'required':
                    if (empty($value)) {
                        return false;
                    }
                    break;

                case 'array':
                    if (!is_array($value)) {
                        return false;
                    }
                    if (
                        !empty($parameters[0])
                    ) {
                        foreach ($value as $item) {
                            $valid = $this->validateValue($item, $parameters);

                            if (!$valid) {
                                return false;
                            }
                        }
                    }
                    break;

                case 'string':
                    if (!is_string($value)) {
                        return false;
                    }
                    break;

                case 'numeric':
                    if (!is_numeric($value)) {
                        return false;
                    }
                    break;

                case 'int':
                    if (!filter_var($value, FILTER_VALIDATE_INT)) {
                        return false;
                    }
                    break;

                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        return false;
                    }
                    break;

                case 'url':
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        return false;
                    }
                    break;

                case 'minlength':
                    if (
                        !isset($parameters[0]) ||
                        !is_string($value) ||
                        strlen($value) < (int)$parameters[0]
                    ) {
                        return false;
                    }
                    break;

                case 'maxlength':
                    if (
                        !isset($parameters[0]) ||
                        !is_string($value) ||
                        strlen($value) > (int)$parameters[0]
                    ) {
                        return false;
                    }
                    break;

                case 'length':
                    if (
                        !isset($parameters[0]) ||
                        !is_string($value) ||
                        strlen($value) !== (int)$parameters[0]
                    ) {
                        return false;
                    }
                    break;

                case 'min':
                    if (!isset($parameters[0]) || $value < (float)$parameters[0]) {
                        return false;
                    }
                    break;

                case 'max':
                    if (!isset($parameters[0]) || $value > (float)$parameters[0]) {
                        return false;
                    }
                    break;

                case 'regex':
                    if (
                        !is_string($value) ||
                        !isset($parameters[0]) ||
                        !preg_match($parameters[0], $value)
                    ) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Get the validated value or the default
     *
     * @param string $key The key to look for
     * @param mixed $default Default value if key doesn't exist or validation fails
     * @return mixed The value or default
     */
    public function get(string $key, mixed $default = false): mixed
    {
        // If we have validated data, use that
        if (isset($this->validatedData[$key])) {
            return $this->validatedData[$key];
        }

        // If we have rules but the key wasn't validated successfully, return default
        if (isset($this->rules[$key]) && !isset($this->validatedData[$key])) {
            return $default;
        }

        // Otherwise, return the raw value or default
        return $this->request[$key] ?? $default;
    }

    /**
     * Get a value as a string with validation
     *
     * @param string $key The key to look for
     * @param string $default Default value if key doesn't exist or validation fails
     * @return string The value or default
     */
    public function string(string $key, string $default = ''): string
    {
        $val = $this->get($key, $default);
        return is_string($val) ? $val : $default;
    }

    /**
     * Get a value as an integer with validation
     *
     * @param string $key
     * @param integer $default
     * @return integer
     */
    public function int(string $key, int $default = 0): int
    {
        $val = $this->get($key, $default);
        return is_numeric($val) ? (int) $val : $default;
    }

    /**
     * Check if there are any validation errors
     *  @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all validation errors
     * @return array<string,string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
