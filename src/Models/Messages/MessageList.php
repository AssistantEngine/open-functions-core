<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages;

use AssistantEngine\OpenFunctions\Core\Contracts\MessageListExtensionInterface;

class MessageList
{
    /**
     * @var Message[] An array of message objects.
     */
    protected $messages = [];

    /**
     * @var MessageListExtensionInterface[] Array of registered extensions.
     */
    protected $extensions = [];

    /**
     * Add messages to the list.
     *
     * @param Message[] $messages
     * @return $this
     */
    public function addMessages(array $messages): self
    {
        foreach ($messages as $message) {
            $this->messages[] = $message;
        }
        return $this;
    }

    /**
     * Add a single message.
     *
     * @param Message $message
     * @return $this
     */
    public function addMessage(Message $message): self
    {
        $this->messages[] = $message;
        return $this;
    }

    /**
     * Prepend messages to the list.
     *
     * @param Message[] $messages
     * @return $this
     */
    public function prependMessages(array $messages): self
    {
        $this->messages = array_merge($messages, $this->messages);
        return $this;
    }

    /**
     * Add an extension to the message list.
     *
     * @param MessageListExtensionInterface $extension
     * @return $this
     */
    public function addExtension(MessageListExtensionInterface $extension): self
    {
        $this->extensions[] = $extension;
        return $this;
    }

    /**
     * Convert the message list to an array.
     *
     * Before converting, run all registered extensions.
     *
     * @return array
     */
    public function toArray(): array
    {
        // Let each extension modify the message list (for example, by prepending messages)
        foreach ($this->extensions as $extension) {
            $extension->extend($this);
        }

        return array_map(function (Message $message) {
            return $message->toArray();
        }, $this->messages);
    }

    /**
     * Get all messages.
     *
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Count how many messages are in the list.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->messages);
    }
}