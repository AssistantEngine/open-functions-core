<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages;

use AssistantEngine\OpenFunctions\Core\Models\Messages\Content\MessageContent;

/**
 * Tool Message:
 * Messages sent by a tool in response to a tool call.
 * According to the documentation, tool messages must have:
 * - role = tool
 * - content (string or array)
 * - tool_call_id (string) - the tool call ID this message is responding to.
 */
class ToolMessage extends Message
{
    /**
     * @var string The ID of the tool call this message responds to.
     */
    protected $toolCallId;

    public function __construct($content, string $toolCallId)
    {
        $this->role = 'tool';
        $this->content = $content;
        $this->toolCallId = $toolCallId;
    }

    /**
     * Set the tool call ID for this tool message.
     *
     * @param string $toolCallId
     * @return $this
     */
    public function setToolCallId(string $toolCallId): self
    {
        $this->toolCallId = $toolCallId;
        return $this;
    }

    /**
     * Get the tool call ID.
     *
     * @return string
     */
    public function getToolCallId(): string
    {
        return $this->toolCallId;
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'tool_call_id' => $this->toolCallId,
            'content' => ($this->content instanceof MessageContent) ? $this->content->toArray() : $this->content,
        ];
    }
}
