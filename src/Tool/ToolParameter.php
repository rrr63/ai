<?php

namespace Doppar\AI\Tool;

class ToolParameter
{
    public function __construct(
        public readonly string $name,
        public readonly ParameterType $type,
        public readonly string $description = '',
        public readonly bool $required = false,
        public readonly mixed $default = null,
        public readonly ?array $enum = null,
        public readonly ?int $minLength = null,
        public readonly ?int $maxLength = null,
        public readonly ?float $minimum = null,
        public readonly ?float $maximum = null,
        public readonly ?string $pattern = null,
        public readonly ?ToolParameter $items = null,
        public readonly ?array $properties = null,
        public readonly ?int $minItems = null,
        public readonly ?int $maxItems = null,
    ) {}

    /**
     * Create a string parameter
     *
     * @param string $name
     * @return ToolParameterBuilder
     */
    public static function string(string $name): ToolParameterBuilder
    {
        return new ToolParameterBuilder($name, ParameterType::STRING);
    }

    /**
     * Create an integer parameter
     *
     * @param string $name
     * @return ToolParameterBuilder
     */
    public static function integer(string $name): ToolParameterBuilder
    {
        return new ToolParameterBuilder($name, ParameterType::INTEGER);
    }

    /**
     * Create a number parameter
     *
     * @param string $name
     * @return ToolParameterBuilder
     */
    public static function number(string $name): ToolParameterBuilder
    {
        return new ToolParameterBuilder($name, ParameterType::NUMBER);
    }

    /**
     * Create a boolean parameter
     *
     * @param string $name
     * @return ToolParameterBuilder
     */
    public static function boolean(string $name): ToolParameterBuilder
    {
        return new ToolParameterBuilder($name, ParameterType::BOOLEAN);
    }

    /**
     * Create an array parameter
     *
     * @param string $name
     * @return ToolParameterBuilder
     */
    public static function array(string $name): ToolParameterBuilder
    {
        return new ToolParameterBuilder($name, ParameterType::ARRAY);
    }

    /**
     * Create an object parameter
     *
     * @param string $name
     * @return ToolParameterBuilder
     */
    public static function object(string $name): ToolParameterBuilder
    {
        return new ToolParameterBuilder($name, ParameterType::OBJECT);
    }

    /**
     * Convert to array format
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'type' => $this->type->value,
            'description' => $this->description,
        ];

        if ($this->enum !== null) {
            $result['enum'] = $this->enum;
        }

        if ($this->minLength !== null) {
            $result['minLength'] = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $result['maxLength'] = $this->maxLength;
        }

        if ($this->minimum !== null) {
            $result['minimum'] = $this->minimum;
        }

        if ($this->maximum !== null) {
            $result['maximum'] = $this->maximum;
        }

        if ($this->pattern !== null) {
            $result['pattern'] = $this->pattern;
        }

        if ($this->items !== null) {
            $result['items'] = $this->items->toArray();
        }

        if ($this->properties !== null) {
            $result['properties'] = array_map(
                fn($prop) => $prop instanceof ToolParameter ? $prop->toArray() : $prop,
                $this->properties
            );
        }

        if ($this->minItems !== null) {
            $result['minItems'] = $this->minItems;
        }

        if ($this->maxItems !== null) {
            $result['maxItems'] = $this->maxItems;
        }

        return $result;
    }

    /**
     * Convert to OpenAI format
     *
     * @return array
     */
    public function toOpenAIFormat(): array
    {
        return $this->toArray();
    }
}
