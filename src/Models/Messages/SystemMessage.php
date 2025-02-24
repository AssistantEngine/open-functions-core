<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages;

use AssistantEngine\OpenFunctions\Core\Models\Messages\Content\MessageContent;

/**
 * System Message:
 * Developer-provided instructions that the model should follow, typically for older models.
 * For o1 models and newer, use developer messages instead.
 */
class SystemMessage extends Message
{
    public function __construct($content, ?string $name = null)
    {
        if (!$content instanceof MessageContent) {
            $content = new MessageContent($content);
        }

        $this->role = 'system';
        $this->content = $content;
        $this->name = $name;
    }
}