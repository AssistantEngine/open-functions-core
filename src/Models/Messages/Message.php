<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages;

use AssistantEngine\OpenFunctions\Core\Models\Messages\Content\MessageContent;

abstract class Message
{
    /**
     * @var string The role of the message's author.
     * Defined by subclasses.
     */
    protected $role;

    /**
     * @var MessageContent The content of the message.
     */
    protected $content;

    /**
     * @var string|null An optional name for the participant.
     */
    protected $name;

    /**
     * Convert the message to an associative array suitable for JSON serialization
     * or sending to the OpenAI API.
     *
     * @return array
     */
    public function toArray(): array
    {
        $message = [
            'role' => $this->role,
        ];

        if ($this->content) {
            $contentParts = $this->content->resolve();
            if ($contentParts !== null) {
                $message['content'] = $contentParts;
            }
        }

        if (!empty($this->name)) {
            $message['name'] = $this->name;
        }

        return $message;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Get the message content.
     *
     * @return MessageContent
     */
    public function getContent(): MessageContent
    {
        return $this->content;
    }

    /**
     * Set the name of the participant who created the message (optional).
     *
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of the participant for this message.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}