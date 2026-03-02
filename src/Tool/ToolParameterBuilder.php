<?php

namespace Doppar\AI\Tool;

class ToolParameterBuilder
{
    private string $description = '';
    private bool $required = false;
    private mixed $default = null;
    private ?array $enum = null;
    private ?int $minLength = null;
    private ?int $maxLength = null;
    private ?float $minimum = null;
    private ?float $maximum = null;
    private ?string $pattern = null;
    private ?ToolParameter $items = null;
    private ?array $properties = null;
    private ?int $minItems = null;
    private ?int $maxItems = null;

    public function __construct(
        private readonly string $name,
        private readonly ParameterType $type
    ) {}

    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;
        return $this;
    }

    public function default(mixed $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function enum(array $enum): self
    {
        $this->enum = $enum;
        return $this;
    }

    public function minLength(int $minLength): self
    {
        $this->minLength = $minLength;
        return $this;
    }

    public function maxLength(int $maxLength): self
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    public function min(float $minimum): self
    {
        $this->minimum = $minimum;
        return $this;
    }

    public function max(float $maximum): self
    {
        $this->maximum = $maximum;
        return $this;
    }

    public function pattern(string $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function items(ToolParameter $items): self
    {
        $this->items = $items;
        return $this;
    }

    public function properties(array $properties): self
    {
        $this->properties = $properties;
        return $this;
    }

    public function minItems(int $minItems): self
    {
        $this->minItems = $minItems;
        return $this;
    }

    public function maxItems(int $maxItems): self
    {
        $this->maxItems = $maxItems;
        return $this;
    }

    public function build(): ToolParameter
    {
        return new ToolParameter(
            name: $this->name,
            type: $this->type,
            description: $this->description,
            required: $this->required,
            default: $this->default,
            enum: $this->enum,
            minLength: $this->minLength,
            maxLength: $this->maxLength,
            minimum: $this->minimum,
            maximum: $this->maximum,
            pattern: $this->pattern,
            items: $this->items,
            properties: $this->properties,
            minItems: $this->minItems,
            maxItems: $this->maxItems,
        );
    }
}
