<?php

namespace AssistantEngine\OpenFunctions\Core\Presenter;

use AssistantEngine\OpenFunctions\Core\Contracts\MessageListExtensionInterface;
use AssistantEngine\OpenFunctions\Core\Models\Messages\MessageList;
use AssistantEngine\OpenFunctions\Core\Models\Messages\DeveloperMessage;
use AssistantEngine\OpenFunctions\Core\Tools\OpenFunctionRegistry;

class RegistryPresenter implements MessageListExtensionInterface
{
    /**
     * The registry
     *
     * @var OpenFunctionRegistry
     */
    private OpenFunctionRegistry $registry;

    /**
     * Intro message for the namespaces.
     *
     * @var string
     */
    public static string $namespaceIntro = 'Function names are prefixed with a namespace. Dont prefix functions yourself use the functions names you receive. Registered namespaces:';
    public static string $namespaceIntroMeta = 'Function names are prefixed with a namespace. Dont prefix functions yourself use the functions names you receive. You need to activate functions before using. Registered namespaces:';

    /**
     * Constructor.
     *
     * Accepts an instance of OpenFunctionRegistry and retrieves its internal registry and namespaces.
     *
     * @param OpenFunctionRegistry $registry
     */
    public function __construct(OpenFunctionRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Extend the message list by prepending a developer message with namespace details.
     *
     * @param MessageList $messageList
     * @return void
     */
    public function extend(MessageList $messageList): void
    {
        if (empty($this->namespaces)) {
            return;
        }
        $messageList->prependMessages([$this->getNamespacesDeveloperMessage()]);
    }

    /**
     * Create a developer message listing the namespaces.
     *
     * @return DeveloperMessage
     */
    public function getNamespacesDeveloperMessage(): DeveloperMessage
    {
        if ($this->registry->metaModeEnabled()) {
            $lines = [self::$namespaceIntroMeta];
        } else {
            $lines = [self::$namespaceIntro];
        }

        foreach ($this->registry->getNamespaces() as $nsName => $nsInfo) {
            $lines[] = "- {$nsName}: {$nsInfo['description']}";
        }
        return new DeveloperMessage(implode("\n", $lines));
    }
}