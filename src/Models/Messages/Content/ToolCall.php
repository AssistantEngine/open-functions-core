<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages\Content;

/**
 * Represents a single tool call.
 */
class ToolCall
{
    /**
     * @var string The ID of the tool call.
     */
    protected $id;

    /**
     * @var string The type of the tool call (currently should be 'function').
     */
    protected $type = 'function';

    /**
     * @var array The function details for the tool call.
     * [
     *   'name' => string,
     *   'arguments' => string (JSON)
     * ]
     */
    protected $function;

    /**
     * ToolCall constructor.
     *
     * @param string $id
     * @param string $functionName
     * @param string $functionArguments JSON string of arguments
     */
    public function __construct(string $id, string $functionName, string $functionArguments)
    {
        $this->id = $id;
        $this->function = [
            'name' => $functionName,
            'arguments' => $functionArguments
        ];
    }

    /**
     * Convert the tool call to an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'function' => $this->function
        ];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getFunction(): array
    {
        return $this->function;
    }

    public function getFunctionName(): string
    {
        return $this->function['name'];
    }

    public function getFunctionArgumentsAsArray(): array
    {
        return json_decode($this->function['arguments'], true);
    }
}