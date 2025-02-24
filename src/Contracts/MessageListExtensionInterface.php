<?php

namespace AssistantEngine\OpenFunctions\Core\Contracts;

use AssistantEngine\OpenFunctions\Core\Models\Messages\MessageList;

interface MessageListExtensionInterface
{
    /**
     * Extend the message list.
     *
     * @param MessageList $messageList The message list to extend.
     * @return void
     */
    public function extend(MessageList $messageList): void;
}