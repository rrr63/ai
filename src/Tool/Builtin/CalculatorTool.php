<?php

namespace Doppar\AI\Tool\Builtin;

use Doppar\AI\Tool\Tool;
use Doppar\AI\Tool\ToolParameter;
use Doppar\AI\Tool\ToolResult;

class CalculatorTool extends Tool
{
    protected string $name = 'calculator';
    protected string $description = 'Perform mathematical calculations. Supports basic operations (+, -, *, /, %), power, and square root.';

    protected function defineParameters(): array
    {
        return [
            ToolParameter::string('operation')
                ->description('The operation to perform: add, subtract, multiply, divide, modulo, power, sqrt')
                ->required()
                ->enum(['add', 'subtract', 'multiply', 'divide', 'modulo', 'power', 'sqrt'])
                ->build(),

            ToolParameter::number('a')
                ->description('First number')
                ->required()
                ->build(),

            ToolParameter::number('b')
                ->description('Second number (not required for sqrt)')
                ->default(0)
                ->build(),
        ];
    }

    protected function doExecute(array $arguments): mixed
    {
        $operation = $arguments['operation'];
        $a = $arguments['a'];
        $b = $arguments['b'] ?? 0;

        $result = match ($operation) {
            'add' => $a + $b,
            'subtract' => $a - $b,
            'multiply' => $a * $b,
            'divide' => $b != 0 ? $a / $b : throw new \RuntimeException('Division by zero'),
            'modulo' => $b != 0 ? $a % $b : throw new \RuntimeException('Modulo by zero'),
            'power' => pow($a, $b),
            'sqrt' => sqrt($a),
            default => throw new \RuntimeException("Unknown operation: {$operation}"),
        };

        return [
            'operation' => $operation,
            'input' => ['a' => $a, 'b' => $b],
            'result' => $result,
        ];
    }
}
