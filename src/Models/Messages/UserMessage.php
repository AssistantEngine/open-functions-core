<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages;

use AssistantEngine\OpenFunctions\Core\Models\Messages\Content\MessageContent;

/**
 * User Message:
 * Messages sent by an end user, containing prompts or additional context information.
 */
class UserMessage extends Message
{
    public function __construct($content, ?string $name = null)
    {
        if (!$content instanceof MessageContent) {
            $content = new MessageContent($content);
        }

        $this->role = 'user';
        $this->content = $content;
        $this->name = $name;
    }
}