<?php

namespace Doppar\AI\Tool;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ToolExecutor
{
    private ?LoggerInterface $logger = null;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function execute(ToolInterface $tool, array $arguments): ToolResult
    {
        $this->logger->info("Executing tool: {$tool->getName()}", [
            'tool' => $tool->getName(),
            'arguments' => $arguments,
        ]);

        $retryCount = method_exists($tool, 'getRetryCount') ? $tool->getRetryCount() : 0;

        if ($retryCount > 0) {
            return $this->executeWithRetry($tool, $arguments, $retryCount);
        }

        return $tool->execute($arguments);
    }

    public function executeWithRetry(ToolInterface $tool, array $arguments, int $maxRetries): ToolResult
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt <= $maxRetries) {
            try {
                $result = $tool->execute($arguments);

                if ($result->success) {
                    if ($attempt > 0) {
                        $this->logger->info("Tool succeeded after {$attempt} retries", [
                            'tool' => $tool->getName(),
                            'retries' => $attempt,
                        ]);
                    }

                    return new ToolResult(
                        success: $result->success,
                        data: $result->data,
                        error: $result->error,
                        metadata: array_merge($result->metadata, ['retries' => $attempt])
                    );
                }

                $lastError = $result->error;
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                $this->logger->warning("Tool execution failed, attempt {$attempt}/{$maxRetries}", [
                    'tool' => $tool->getName(),
                    'error' => $e->getMessage(),
                ]);
            }

            $attempt++;

            if ($attempt <= $maxRetries) {
                $backoff = min(pow(2, $attempt - 1), 10);
                sleep($backoff);
            }
        }

        $this->logger->error("Tool failed after {$maxRetries} retries", [
            'tool' => $tool->getName(),
            'error' => $lastError,
        ]);

        return ToolResult::error(
            $lastError ?? 'Unknown error',
            ['retries' => $attempt - 1]
        );
    }

    public function validateArguments(ToolInterface $tool, array $arguments): bool
    {
        return ToolValidator::validate($tool, $arguments);
    }
}
