<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages\Content;


/**
 * Represents a text content part.
 */
class TextContentPart extends ContentPart
{
    /**
     * @var string
     */
    protected $text;

    /**
     * TextContentPart constructor.
     * @param string $text
     */
    public function __construct(string $text)
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    public function toArray(): array
    {
        return [
            'type' => 'text',
            'text' => $this->text,
        ];
    }
}