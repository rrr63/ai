<?php

namespace Doppar\AI\Tool;

class ToolCollection
{
    private array $tools = [];

    public function add(ToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    public function get(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function all(): array
    {
        return $this->tools;
    }

    public function count(): int
    {
        return count($this->tools);
    }

    public function toArray(): array
    {
        return array_map(function (ToolInterface $tool) {
            return [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters' => array_map(
                    fn(ToolParameter $param) => $param->toArray(),
                    $tool->getParameters()
                ),
            ];
        }, $this->tools);
    }
}
