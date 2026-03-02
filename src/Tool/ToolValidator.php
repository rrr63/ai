<?php

namespace Doppar\AI\Tool;

use Doppar\AI\Tool\Exceptions\ValidationException;

class ToolValidator
{
    public static function validate(ToolInterface $tool, array $arguments): bool
    {
        return $tool->validate($arguments);
    }

    public static function validateArguments(array $parameters, array $arguments): bool
    {
        foreach ($parameters as $parameter) {
            if ($parameter->required && !isset($arguments[$parameter->name])) {
                throw new ValidationException("Required parameter '{$parameter->name}' is missing");
            }
        }

        return true;
    }
}
