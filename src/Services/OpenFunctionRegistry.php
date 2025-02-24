<?php

namespace AssistantEngine\OpenFunctions\Core\Services;

use AssistantEngine\OpenFunctions\Core\Contracts\AbstractOpenFunction;
use AssistantEngine\OpenFunctions\Core\Contracts\MessageListExtensionInterface;
use AssistantEngine\OpenFunctions\Core\Models\Messages\DeveloperMessage;
use AssistantEngine\OpenFunctions\Core\Models\Messages\MessageList;
use AssistantEngine\OpenFunctions\Core\Models\Responses\Response;
use Exception;

class OpenFunctionRegistry implements MessageListExtensionInterface
{
    const NAMESPACE_SEPARATOR = '_';

    /**
     * Public static property to hold the default namespace intro message.
     * This can be overridden from outside.
     *
     * @var string
     */
    public static string $namespaceIntro = 'Registered Tool namespaces:';

    /**
     * Internal mapping:
     * [
     *   'namespaceName.functionName' => [
     *      'openFunction'=> AbstractOpenFunction instance,
     *      'method'      => string,
     *      'definition'  => array,
     *   ],
     *   ...
     * ]
     *
     * @var array
     */
    protected array $registry = [];

    /**
     * Mapping of namespace names to their details.
     *
     * @var array
     * [
     *    'namespaceName' => [
     *         'description' => string
     *    ],
     *    ...
     * ]
     */
    protected array $namespaces = [];

    /**
     * Registers an open function.
     *
     * @param string               $namespaceName        The namespace to prefix all functions.
     * @param string               $namespaceDescription A description for this namespace.
     * @param AbstractOpenFunction $openFunction         The open function instance.
     *
     * @throws Exception If any function definition is missing a name.
     */
    public function registerOpenFunction(
        string $namespaceName,
        string $namespaceDescription,
        AbstractOpenFunction $openFunction
    ): void {
        $definitions = $openFunction->generateFunctionDefinitions();

        if (isset($definitions['function'])) {
            $definitions = [$definitions];
        }

        foreach ($definitions as $definition) {
            if (!isset($definition['function']['name'])) {
                throw new Exception("Function definition must contain a 'name' field.");
            }
            $originalName = $definition['function']['name'];
            $namespacedName = $namespaceName . self::NAMESPACE_SEPARATOR . $originalName;

            $definition['function']['name'] = $namespacedName;
            $definition['function']['description'] = "[{$namespaceName}] " . $definition['function']['description'];

            $this->registry[$namespacedName] = [
                'openFunction' => $openFunction,
                'method'       => $originalName,
                'definition'   => $definition,
            ];

            if (!isset($this->namespaces[$namespaceName])) {
                $this->namespaces[$namespaceName] = [
                    'description' => $namespaceDescription,
                ];
            }
        }
    }

    /**
     * Returns a developer message explaining the registered namespaces.
     *
     * @return DeveloperMessage
     */
    public function getNamespacesDeveloperMessage(): DeveloperMessage
    {
        $lines = [];
        // Use the static property as the introductory message.
        $lines[] = self::$namespaceIntro;
        foreach ($this->namespaces as $nsName => $nsDetails) {
            $lines[] = "- {$nsName}: {$nsDetails['description']}";
        }
        $content = implode("\n", $lines);

        return new DeveloperMessage($content);
    }

    /**
     * Returns all namespaced function definitions.
     *
     * @return array
     */
    public function getFunctionDefinitions(): array
    {
        $definitions = [];
        foreach ($this->registry as $entry) {
            $definitions[] = $entry['definition'];
        }
        return $definitions;
    }

    /**
     * Executes a function call for the specified namespaced function.
     *
     * The namespaced function name should be in the format "namespaceName.functionName".
     *
     * @param string $namespacedName The namespaced function name.
     * @param array  $arguments      The arguments to pass to the function.
     *
     * @return Response
     *
     * @throws Exception
     */
    public function executeFunctionCall(string $namespacedName, array $arguments): Response
    {
        if (!isset($this->registry[$namespacedName])) {
            throw new Exception("No function registered under the name '{$namespacedName}'.");
        }

        $entry = $this->registry[$namespacedName];
        $openFunction = $entry['openFunction'];
        $method = $entry['method'];

        return $openFunction->callMethod($method, $arguments);
    }

    /**
     * Extend the message list by prepending the namespaces developer message.
     *
     * @param MessageList $messageList
     * @return void
     */
    public function extend(MessageList $messageList): void
    {
        if (empty($this->registry)) {
            return;
        }

        $devMessage = $this->getNamespacesDeveloperMessage();
        $messageList->prependMessages([$devMessage]);
    }
}