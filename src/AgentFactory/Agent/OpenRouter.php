<?php

namespace Doppar\AI\AgentFactory\Agent;

use InvalidArgumentException;
use Symfony\AI\Platform\Platform;
use Symfony\AI\Platform\Message\Message;
use Doppar\AI\AgentFactory\AgentInterface;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Bridge\OpenRouter\PlatformFactory;

class OpenRouter implements AgentInterface
{
    /**
     * The AI platform instance for executing OpenRouter calls
     *
     * @var Platform
     */
    private Platform $platform;

    /**
     * A collection of structured messages sent to the model
     *
     * @var MessageBag
     */
    private MessageBag $messages;

    /**
     * Constructor.
     *
     * @param string $key 
     * @param string $model
     */
    public function __construct(private string $key, private string $model)
    {
        $this->platform = PlatformFactory::create($this->key);
    }

    /**
     * Factory method to create an agent instance.
     *
     * @param string $key
     * @param string $model
     * @param array $config
     * @return AgentInterface
     */
    public static function create(string $key, string $model, $config = []): AgentInterface
    {
        return new self($key, $model);
    }

    /**
     * Sets and hydrates messages for the model invocation.
     *
     * @param array<int, array{role: string, content: string}> $messages
     *     Array of messages, each containing:
     *     - role: "system"|"user"
     *     - content: string
     * @return $this
     */
    public function setMessage(array $messages): mixed
    {
        $this->messages = $this->hydrateMessages($messages);

        return $this;
    }

    /**
     * Executes the model call using the current messages and parameters.
     *
     * @param array<string, mixed> $params
     * @param bool $complete
     * @param ?string $textInput
     * @return mixed
     */
    public function execute(array $params, bool $complete = false, ?string $textInput = null): mixed
    {
        $result = $this->platform->invoke($this->model, $textInput ?? $this->messages, $params);

        return $complete ? $result : $result->asText();
    }

    /**
     * Converts raw message arrays into a MessageBag.
     *
     * @param array<int, array{role: string, content: string}> $data
     * @return MessageBag
     * @throws InvalidArgumentException
     */
    public function hydrateMessages(array $data): MessageBag
    {
        $messages = [];

        foreach ($data as $item) {
            if (!isset($item['role'], $item['content'])) {
                continue;
            }

            switch ($item['role']) {
                case 'system':
                    $messages[] = Message::forSystem($item['content']);
                    break;
                case 'user':
                    $messages[] = Message::ofUser($item['content']);
                    break;
                case 'assistant':
                    $messages[] = Message::ofAssistant($item['content']);
                    break;
                default:
                    throw new InvalidArgumentException("Unknown role : {$item['role']}");
            }
        }

        return new MessageBag(...$messages);
    }

    /**
     * Execute with tools support (function calling)
     * OpenRouter uses the same format as OpenAI
     *
     * @param \Doppar\AI\Tool\ToolCollection $tools
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function executeWithTools(\Doppar\AI\Tool\ToolCollection $tools, array $params): mixed
    {
        $formattedTools = [];
        foreach ($tools->all() as $tool) {
            $formattedTools[] = $this->formatToolForOpenRouter($tool);
        }

        $params['tools'] = $formattedTools;
        $params['tool_choice'] = 'auto';

        $result = $this->platform->invoke($this->model, $this->messages, $params);

        // Check if we got tool calls
        try {
            $toolCalls = $result->asToolCalls();
            
            // Execute each tool call
            $executor = new \Doppar\AI\Tool\ToolExecutor();
            $toolResultsText = [];
            
            foreach ($toolCalls as $toolCall) {
                $toolName = $toolCall->getName();
                $arguments = $toolCall->getArguments();
                
                $tool = $tools->get($toolName);
                if ($tool) {
                    $toolResult = $executor->execute($tool, $arguments);
                    $toolResultsText[] = "Tool '{$toolName}' result: " . json_encode($toolResult->data);
                }
            }
            
            // Add tool results as a user message and call again
            $messages = $this->messages->getMessages();
            $messages[] = Message::ofUser(
                "Tool execution results:\n" . implode("\n", $toolResultsText) . 
                "\n\nPlease provide a natural language response based on these results."
            );
            
            $newMessageBag = new MessageBag(...$messages);
            
            // Remove tools from params for the second call
            unset($params['tools']);
            unset($params['tool_choice']);
            
            $finalResult = $this->platform->invoke($this->model, $newMessageBag, $params);
            
            return $finalResult->asText();
        } catch (\Exception $e) {
            // If no tool calls, return as text
            return $result->asText();
        }
    }

    /**
     * Format a tool for OpenRouter function calling
     * OpenRouter uses OpenAI-compatible format
     *
     * @param \Doppar\AI\Tool\ToolInterface $tool
     * @return array
     */
    private function formatToolForOpenRouter(\Doppar\AI\Tool\ToolInterface $tool): array
    {
        $parameters = $tool->getParameters();
        $properties = [];
        $required = [];

        foreach ($parameters as $param) {
            $properties[$param->name] = $param->toOpenAIFormat();
            
            if ($param->required) {
                $required[] = $param->name;
            }
        }

        return [
            'type' => 'function',
            'function' => [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                ],
            ],
        ];
    }
}