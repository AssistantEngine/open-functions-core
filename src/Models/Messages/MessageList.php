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

    public function __construct(array $messages = [])
    {
        $this->messages = $messages;
    }

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

    public function addExtensions(array $extensions): self
    {
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
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
        // Create a clone of this message list so that extensions don't affect the original.
        $clone = clone $this;

        // Let each extension modify the cloned message list.
        foreach ($clone->extensions as $extension) {
            $extension->extend($clone);
        }

        // Convert the cloned list's messages to an array.
        return array_map(function (Message $message) {
            return $message->toArray();
        }, $clone->messages);
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