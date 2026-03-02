<?php

namespace Doppar\AI\Tool;

use Doppar\AI\Tool\Exceptions\ToolException;

class ToolRegistry
{
    private static array $tools = [];
    private static array $toolFactories = [];
    private static array $instances = [];

    public static function register(string $name, string|\Closure $toolClassOrFactory): void
    {
        if (is_string($toolClassOrFactory)) {
            self::$tools[$name] = $toolClassOrFactory;
        } else {
            self::$toolFactories[$name] = $toolClassOrFactory;
        }
    }

    public static function get(string $name): ToolInterface
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        if (isset(self::$toolFactories[$name])) {
            $factory = self::$toolFactories[$name];
            self::$instances[$name] = $factory();
            return self::$instances[$name];
        }

        if (isset(self::$tools[$name])) {
            $className = self::$tools[$name];
            
            if (!class_exists($className)) {
                throw new ToolException("Tool class not found: {$className}");
            }

            self::$instances[$name] = new $className();
            return self::$instances[$name];
        }

        throw new ToolException("Tool not found: {$name}");
    }

    public static function has(string $name): bool
    {
        return isset(self::$tools[$name]) || isset(self::$toolFactories[$name]);
    }

    public static function all(): array
    {
        return array_merge(
            array_keys(self::$tools),
            array_keys(self::$toolFactories)
        );
    }

    public static function clear(): void
    {
        self::$tools = [];
        self::$toolFactories = [];
        self::$instances = [];
    }
}
