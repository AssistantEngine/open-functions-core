<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages\Content;


/**
 * Represents the content of a message. Can be a simple text or multiple parts (text, image, etc.).
 */
class MessageContent
{
    /**
     * @var ContentPart[] Array of content parts.
     */
    protected $parts = [];

    public function __construct(?string $text = null)
    {
        if ($text) {
            $this->addText($text);
        }
    }

    /**
     * Add a text part to the content.
     *
     * @param string $text
     * @return $this
     */
    public function addText(string $text): self
    {
        $this->parts[] = new TextContentPart($text);
        return $this;
    }

    /**
     * Add an image part to the content.
     *
     * @param string      $url   URL or base64 data of the image
     * @param string|null $detail Optional detail level for the image
     * @return $this
     */
    public function addImage(string $url, ?string $detail = null): self
    {
        $this->parts[] = new ImageContentPart($url, $detail);
        return $this;
    }

    /**
     * Convert the content to an associative array or a string.
     *
     * @return string|array|null
     */
    public function resolve()
    {
        if (empty($this->parts)) {
            return null;
        }

        // If only one part and it's text, return just the string.
        if (count($this->parts) === 1 && $this->parts[0] instanceof TextContentPart) {
            return $this->parts[0]->getText();
        }

        // Otherwise, return an array of parts
        $array = [];
        foreach ($this->parts as $part) {
            $array[] = $part->toArray();
        }

        return $array;
    }
}