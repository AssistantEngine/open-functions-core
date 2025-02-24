<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages;

use AssistantEngine\OpenFunctions\Core\Models\Messages\Content\MessageContent;
use AssistantEngine\OpenFunctions\Core\Models\Messages\Content\ToolCall;

/**
 * Assistant Message:
 * Messages sent by the model in response to user messages.
 * Can include tool calls and optional audio data.
 */
class AssistantMessage extends Message
{
    /**
     * @var ToolCall[]|null
     */
    protected $toolCalls;

    /**
     * @var array|null Audio data for the assistant message
     */
    protected $audio;

    public function __construct($content = null, ?string $name = null)
    {
        if ($content && !$content instanceof MessageContent) {
            $content = new MessageContent($content);
        }

        $this->role = 'assistant';
        $this->content = $content;
        $this->name = $name;
        $this->toolCalls = null;
        $this->audio = null;
    }

    /**
     * Set the tool calls for this message.
     *
     * @param ToolCall[]|null $toolCalls
     * @return $this
     */
    public function setToolCalls(?array $toolCalls): self
    {
        $this->toolCalls = $toolCalls;
        return $this;
    }

    /**
     * Add a single ToolCall to the message.
     *
     * @param ToolCall $toolCall
     * @return $this
     */
    public function addToolCall(ToolCall $toolCall): self
    {
        if ($this->toolCalls === null) {
            $this->toolCalls = [];
        }

        $this->toolCalls[] = $toolCall;
        return $this;
    }

    /**
     * Set the audio data for an assistant message.
     *
     * @param array|null $audio
     * @return $this
     */
    public function setAudio(?array $audio): self
    {
        $this->audio = $audio;
        return $this;
    }

    public function toArray(): array
    {
        $message = parent::toArray();

        if (!empty($this->toolCalls)) {
            $message['tool_calls'] = array_map(function(ToolCall $toolCall) {
                return $toolCall->toArray();
            }, $this->toolCalls);
        }

        if (!empty($this->audio)) {
            $message['audio'] = $this->audio;
        }

        return $message;
    }
}
