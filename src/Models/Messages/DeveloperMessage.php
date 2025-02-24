<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages;

use AssistantEngine\OpenFunctions\Core\Models\Messages\Content\MessageContent;

/**
 * Developer Message:
 * Developer-provided instructions that the model should follow.
 * With o1 models and newer, developer messages replace system messages.
 */
class DeveloperMessage extends Message
{
    public function __construct($content, ?string $name = null)
    {
        if (!$content instanceof MessageContent) {
            $content = new MessageContent($content);
        }

        $this->role = 'developer';
        $this->content = $content;
        $this->name = $name;
    }
}
