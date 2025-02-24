<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages\Content;

/**
 * Represents an image content part.
 */
class ImageContentPart extends ContentPart
{
    /**
     * @var string URL or base64 encoded data for the image.
     */
    protected $url;

    /**
     * @var string|null The detail level of the image.
     */
    protected $detail;

    /**
     * ImageContentPart constructor.
     *
     * @param string      $url
     * @param string|null $detail
     */
    public function __construct(string $url, ?string $detail = null)
    {
        $this->url = $url;
        $this->detail = $detail;
    }

    public function toArray(): array
    {
        $imageArray = [
            'type' => 'image_url',
            'image_url' => [
                'url' => $this->url
            ]
        ];

        if (!empty($this->detail)) {
            $imageArray['image_url']['detail'] = $this->detail;
        }

        return $imageArray;
    }
}
