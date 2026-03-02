<?php

namespace Doppar\AI\Tool;

interface ToolInterface
{
    /**
     * Get the tool name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the tool description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get the tool parameters definition
     *
     * @return array<ToolParameter>
     */
    public function getParameters(): array;

    /**
     * Execute the tool with given arguments
     *
     * @param array<string, mixed> $arguments
     * @return ToolResult
     */
    public function execute(array $arguments): ToolResult;

    /**
     * Validate the arguments before execution
     *
     * @param array<string, mixed> $arguments
     * @return bool
     */
    public function validate(array $arguments): bool;
}
