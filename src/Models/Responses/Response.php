<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Responses;

class Response
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR   = 'error';

    /**
     * @var ResponseItem[] A list of response item objects.
     */
    public array $content = [];

    /**
     * Indicates whether this response represents an error.
     *
     * @var bool
     */
    public bool $isError = false;

    /**
     * The response status.
     *
     * @var string
     */
    public string $status;

    /**
     * Constructor.
     *
     * @param string $status  Should be either self::STATUS_SUCCESS or self::STATUS_ERROR.
     * @param array  $content A list of response items (instances of ResponseItem).
     */
    public function __construct(string $status, array $content = [])
    {
        $this->status  = $status;
        $this->content = $content;
        $this->isError = ($status === self::STATUS_ERROR);
    }

    /**
     * Converts the complete response into an associative array.
     *
     * Each response item is converted via its own toArray() method.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'isError' => $this->isError,
            'content' => $this->contentToArray(),
        ];
    }

    public function contentToArray(): array
    {
        $items = [];

        foreach ($this->content as $item) {
            // Each item is assumed to be an object that implements toArray()
            if (is_object($item) && method_exists($item, 'toArray')) {
                $items[] = $item->toArray();
            } else {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Creates a Response instance from an associative array.
     *
     * The array is expected to have the same structure as produced by toArray().
     *
     * @param array $data The associative array representation of a Response.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $contentItems = [];

        if (isset($data['content']) && is_array($data['content'])) {
            foreach ($data['content'] as $itemData) {
                // Ensure we have an array with a 'type' key
                if (is_array($itemData) && isset($itemData['type'])) {
                    switch ($itemData['type']) {
                        case ResponseItem::TYPE_TEXT:
                            if (isset($itemData['text'])) {
                                $contentItems[] = new TextResponseItem($itemData['text']);
                            }
                            break;
                        case ResponseItem::TYPE_BINARY:
                            if (isset($itemData['filename'], $itemData['blob'])) {
                                $mimeType = $itemData['mimeType'] ?? null;
                                $contentItems[] = new BinaryResponseItem($itemData['filename'], $itemData['blob'], $mimeType);
                            }
                            break;
                        default:

                    }
                }
            }
        }

        // Determine the status based on the isError flag.
        $status = (!empty($data['isError']) && $data['isError'] === true)
            ? self::STATUS_ERROR
            : self::STATUS_SUCCESS;

        return new self($status, $contentItems);
    }
}
