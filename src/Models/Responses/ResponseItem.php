<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Responses;

/**
 * Abstract base class for response items.
 */
abstract class ResponseItem
{
    const TYPE_TEXT = 'text';
    const TYPE_BINARY = 'binary';

    /**
     * The type of the response item.
     *
     * @var string
     */
    protected string $type;

    /**
     * Constructor.
     *
     * @param string $type The type of the response item.
     */
    public function __construct(string $type) {
        $this->type = $type;
    }

    /**
     * Convert the response item to an associative array.
     *
     * @return array
     */
    abstract public function toArray(): array;
}