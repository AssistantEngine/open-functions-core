<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Responses;

/**
 * Class for binary-based response items.
 *
 * This class holds a single binary content.
 * Example output:
 * [
 *   "type" => "binary",
 *   "filename" => "image.png",
 *   "mimeType" => "image/png",  // Optional
 *   "blob" => "iVBORw0KGgoAAAANSUhEUgAA..."
 * ]
 */
class BinaryResponseItem extends ResponseItem {
    /**
     * The URI of the binary resource.
     *
     * @var string
     */
    private string $filename;

    /**
     * Optional MIME type of the binary resource.
     *
     * @var string|null
     */
    private ?string $mimeType;

    /**
     * Optional base64 encoded binary data.
     *
     * @var string|null
     */
    private ?string $blob;

    /**
     * Constructor.
     *
     * @param string      $filename      The URI of the resource.
     * @param string|null $mimeType Optional MIME type.
     * @param string|null $blob     Optional base64 encoded binary data.
     */
    public function __construct(string $filename, string $blob, ?string $mimeType = null) {
        parent::__construct(ResponseItem::TYPE_BINARY);

        $this->filename = $filename;
        $this->mimeType = $mimeType;
        $this->blob = $blob;
    }

    /**
     * Convert the binary response item to an associative array.
     *
     * @return array
     */
    public function toArray(): array {
        $result = [
            'type' => $this->type,
            'filename'  => $this->filename,
            'blob' => $this->blob,
        ];

        if ($this->mimeType !== null) {
            $result['mimeType'] = $this->mimeType;
        }

        return $result;
    }
}