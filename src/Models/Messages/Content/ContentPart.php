<?php

namespace AssistantEngine\OpenFunctions\Core\Models\Messages\Content;

/**
 * Abstract base class for content parts.
 */
abstract class ContentPart
{
    abstract public function toArray(): array;
}