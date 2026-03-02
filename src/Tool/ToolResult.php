<?php

namespace Doppar\AI\Tool;

class ToolResult
{
    public function __construct(
        public readonly bool $success,
        public readonly mixed $data,
        public readonly ?string $error = null,
        public readonly array $metadata = []
    ) {}

    /**
     * Create a successful result
     *
     * @param mixed $data
     * @param array $metadata
     * @return self
     */
    public static function success(mixed $data, array $metadata = []): self
    {
        return new self(
            success: true,
            data: $data,
            error: null,
            metadata: array_merge([
                'duration' => 0.0,
                'cached' => false,
                'retries' => 0,
            ], $metadata)
        );
    }

    /**
     * Create an error result
     *
     * @param string $error
     * @param array $metadata
     * @return self
     */
    public static function error(string $error, array $metadata = []): self
    {
        return new self(
            success: false,
            data: null,
            error: $error,
            metadata: array_merge([
                'duration' => 0.0,
                'cached' => false,
                'retries' => 0,
            ], $metadata)
        );
    }

    /**
     * Get the execution duration
     *
     * @return float
     */
    public function getDuration(): float
    {
        return $this->metadata['duration'] ?? 0.0;
    }

    /**
     * Check if result was cached
     *
     * @return bool
     */
    public function isCached(): bool
    {
        return $this->metadata['cached'] ?? false;
    }

    /**
     * Get number of retries
     *
     * @return int
     */
    public function getRetries(): int
    {
        return $this->metadata['retries'] ?? 0;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'error' => $this->error,
            'metadata' => $this->metadata,
        ];
    }
}
