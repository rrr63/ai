<?php

namespace Doppar\AI\Tool;

use Doppar\AI\Tool\Exceptions\ValidationException;

abstract class Tool implements ToolInterface
{
    protected string $name = '';
    protected string $description = '';
    protected int $retryCount = 0;
    protected int $timeout = 30;
    protected bool $cacheEnabled = false;
    protected int $cacheTtl = 0;
    protected array $permissions = [];
    protected array $validators = [];
    protected array $errorHandlers = [];

    public function __construct()
    {
        if (empty($this->name)) {
            throw new \RuntimeException('Tool name must be defined');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getParameters(): array
    {
        return $this->defineParameters();
    }

    public function execute(array $arguments): ToolResult
    {
        $startTime = microtime(true);

        try {
            if (!$this->validate($arguments)) {
                throw new ValidationException('Invalid arguments provided');
            }

            foreach ($this->validators as $validator) {
                $validator($arguments);
            }

            $result = $this->doExecute($arguments);

            $duration = microtime(true) - $startTime;

            if ($result instanceof ToolResult) {
                return new ToolResult(
                    success: $result->success,
                    data: $result->data,
                    error: $result->error,
                    metadata: array_merge($result->metadata, ['duration' => $duration])
                );
            }

            return ToolResult::success($result, ['duration' => $duration]);
        } catch (\Exception $e) {
            foreach ($this->errorHandlers as $handler) {
                $handler($e);
            }

            $duration = microtime(true) - $startTime;
            return ToolResult::error($e->getMessage(), ['duration' => $duration]);
        }
    }

    public function validate(array $arguments): bool
    {
        $parameters = $this->getParameters();

        foreach ($parameters as $parameter) {
            if ($parameter->required && !isset($arguments[$parameter->name])) {
                throw new ValidationException("Required parameter '{$parameter->name}' is missing");
            }

            if (isset($arguments[$parameter->name])) {
                $this->validateParameter($parameter, $arguments[$parameter->name]);
            }
        }

        return true;
    }

    protected function validateParameter(ToolParameter $parameter, mixed $value): void
    {
        switch ($parameter->type) {
            case ParameterType::STRING:
                if (!is_string($value)) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be a string");
                }
                if ($parameter->minLength !== null && strlen($value) < $parameter->minLength) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be at least {$parameter->minLength} characters");
                }
                if ($parameter->maxLength !== null && strlen($value) > $parameter->maxLength) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be at most {$parameter->maxLength} characters");
                }
                if ($parameter->pattern !== null && !preg_match($parameter->pattern, $value)) {
                    throw new ValidationException("Parameter '{$parameter->name}' does not match required pattern");
                }
                if ($parameter->enum !== null && !in_array($value, $parameter->enum)) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be one of: " . implode(', ', $parameter->enum));
                }
                break;

            case ParameterType::INTEGER:
                if (!is_int($value)) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be an integer");
                }
                if ($parameter->minimum !== null && $value < $parameter->minimum) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be at least {$parameter->minimum}");
                }
                if ($parameter->maximum !== null && $value > $parameter->maximum) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be at most {$parameter->maximum}");
                }
                break;

            case ParameterType::NUMBER:
                if (!is_numeric($value)) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be a number");
                }
                if ($parameter->minimum !== null && $value < $parameter->minimum) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be at least {$parameter->minimum}");
                }
                if ($parameter->maximum !== null && $value > $parameter->maximum) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be at most {$parameter->maximum}");
                }
                break;

            case ParameterType::BOOLEAN:
                if (!is_bool($value)) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be a boolean");
                }
                break;

            case ParameterType::ARRAY:
                if (!is_array($value)) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be an array");
                }
                if ($parameter->minItems !== null && count($value) < $parameter->minItems) {
                    throw new ValidationException("Parameter '{$parameter->name}' must have at least {$parameter->minItems} items");
                }
                if ($parameter->maxItems !== null && count($value) > $parameter->maxItems) {
                    throw new ValidationException("Parameter '{$parameter->name}' must have at most {$parameter->maxItems} items");
                }
                break;

            case ParameterType::OBJECT:
                if (!is_array($value) && !is_object($value)) {
                    throw new ValidationException("Parameter '{$parameter->name}' must be an object");
                }
                break;
        }
    }

    public function withRetry(int $count): self
    {
        $this->retryCount = $count;
        return $this;
    }

    public function withTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function withCache(int $ttl): self
    {
        $this->cacheEnabled = true;
        $this->cacheTtl = $ttl;
        return $this;
    }

    public function requirePermission(string $permission): self
    {
        $this->permissions[] = $permission;
        return $this;
    }

    public function validateBefore(\Closure $validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    public function onError(\Closure $handler): self
    {
        $this->errorHandlers[] = $handler;
        return $this;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    abstract protected function defineParameters(): array;

    abstract protected function doExecute(array $arguments): mixed;
}
