<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Responses;

/**
 * Class for text-based response items.
 *
 * Example output:
 * [
 *   "type" => "text",
 *   "text" => "Operation successful: 12345"
 * ]
 */
class TextResponseItem extends ResponseItem {
    /**
     * The text message.
     *
     * @var string
     */
    private string $text;

    /**
     * Constructor.
     *
     * @param string $text The text message.
     */
    public function __construct(string $text) {
        parent::__construct(ResponseItem::TYPE_TEXT);

        $this->text = $text;
    }

    /**
     * Convert the text response item to an associative array.
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'type' => $this->type,
            'text' => $this->text,
        ];
    }
}