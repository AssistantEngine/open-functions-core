<?php

namespace AssistantEngine\OpenFunctions\Core\Services;

use AssistantEngine\OpenFunctions\Core\Contracts\AbstractOpenFunction;
use AssistantEngine\OpenFunctions\Core\Contracts\MessageListExtensionInterface;
use AssistantEngine\OpenFunctions\Core\Helpers\FunctionDefinition;
use AssistantEngine\OpenFunctions\Core\Helpers\Parameter;
use AssistantEngine\OpenFunctions\Core\Models\Responses\Response;
use AssistantEngine\OpenFunctions\Core\Models\Responses\TextResponseItem;
use AssistantEngine\OpenFunctions\Core\Models\Messages\DeveloperMessage;
use AssistantEngine\OpenFunctions\Core\Models\Messages\MessageList;
use Exception;

/**
 * The Registry is BOTH:
 *  - a container for all namespaces and their function definitions
 *  - an AbstractOpenFunction providing "meta" methods to activate or deactivate meta mode,
 *    list namespaces, pick an active namespace, etc.
 */
class OpenFunctionRegistry extends AbstractOpenFunction implements MessageListExtensionInterface
{
    const NAMESPACE_SEPARATOR = '_';

    /**
     * Whether the "meta mode" is currently enabled.
     * If false => all function definitions are returned (the old behavior).
     * If true  => only the meta definitions + (optionally) the active namespace's definitions are returned.
     */
    protected bool $metaEnabled = false;

    // Now we track an array of activated functions
    protected array $activeFunctions = [];

    /**
     * Internally stores:
     * [
     *   'namespaceName.functionName' => [
     *       'openFunction' => AbstractOpenFunction,
     *       'method'       => string,  // The actual method on $openFunction
     *       'definition'   => array,   // The function definition array
     *   ],
     * ]
     */
    protected array $registry = [];

    /**
     * Namespaces info:
     * [
     *   'namespaceName' => [
     *       'description' => string,
     *   ],
     *   ...
     * ]
     */
    protected array $namespaces = [];

    /**
     * A short message to show when listing namespaces in the developer message.
     */
    public static string $namespaceIntro = 'Function names are prefixed with a tool group name and underscore. You need to active a function before using. Registered tool groups:';

    /**
     * Register a single open function under a given namespace.
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

    public function getFunctionDefinitions(): array
    {
        return $this->generateFunctionDefinitions();
    }

    /**
     * Generate function definitions for:
     *  - The "meta" methods (if meta mode is enabled),
     *  - Potentially the active namespace's definitions (if meta mode is enabled *and* an active namespace is set),
     *  - Or all definitions (if meta mode is disabled).
     */
    public function generateFunctionDefinitions(): array
    {
        // If meta mode is disabled => return ALL definitions from every namespace.
        if (!$this->metaEnabled) {
            return $this->getAllRegistryDefinitions();
        }

        $metaDefs = $this->getMetaMethodDefinitions();

        $activeDefs = [];
        foreach ($this->activeFunctions as $functionName) {
            if (isset($this->registry[$functionName])) {
                $activeDefs[] = $this->registry[$functionName]['definition'];
            }
        }
        return array_merge($metaDefs, $activeDefs);
    }

    /**
     * Handles the meta calls OR dispatches them to the underlying open function objects.
     */
    public function executeFunctionCall(string $methodName, array $arguments = []): Response
    {
        // Only allow the meta method "activateFunction" in meta mode.
        $allowedMetaMethods = ['activateFunction'];

        // If meta mode is enabled and the method is one of the meta methods...
        if ($this->metaEnabled && in_array($methodName, $allowedMetaMethods, true)) {
            return parent::callMethod($methodName, $arguments);
        }

        // Otherwise, attempt to look up the namespaced function.
        if (!isset($this->registry[$methodName])) {
            return new Response(
                Response::STATUS_ERROR,
                [new TextResponseItem("No function registered under '{$methodName}'.")]
            );
        }

        $entry       = $this->registry[$methodName];
        $openFunction = $entry['openFunction'];
        $actualMethod = $entry['method'];

        return $openFunction->callMethod($actualMethod, $arguments);
    }

    public function enableMetaMode(): self
    {
        $this->metaEnabled = true;

        return $this;
    }

    public function disableMetaMode(): self
    {
        $this->metaEnabled = false;

        return $this;
    }

    //---------------------------------------------------------------------
    // "Meta" methods (only relevant if metaEnabled = true)
    //---------------------------------------------------------------------

    /**
     * Activate a function by its namespaced name.
     * Multiple functions can be activated, but duplicate activations are prevented.
     *
     * @param string $functionName The namespaced function name to activate.
     * @return TextResponseItem A response message indicating the activation result.
     */
    public function activateFunction(string $functionName): TextResponseItem
    {
        if (!isset($this->registry[$functionName])) {
            return new TextResponseItem("No such function: '{$functionName}'.");
        }

        if (in_array($functionName, $this->activeFunctions, true)) {
            return new TextResponseItem("Function '{$functionName}' is already activated.");
        }

        $this->activeFunctions[] = $functionName;
        return new TextResponseItem("Function '{$functionName}' has been activated. Activated functions: " . implode(', ', $this->activeFunctions));
    }
    //---------------------------------------------------------------------
    // Utility / Private Helpers
    //---------------------------------------------------------------------

    /**
     * Returns the meta method definition for activateFunction.
     *
     * The enum of valid function names is built from those functions that have not yet been activated.
     */
    protected function getMetaMethodDefinitions(): array
    {
        $metaDefs = [];

        // Allow only functions that are not already activated.
        $availableFunctions = array_diff(array_keys($this->registry), $this->activeFunctions);

        $def = new FunctionDefinition(
            'activateFunction',
            'Activate a function from the registry. Multiple functions can be activated, but duplicates are ignored.'
        );
        $def->addParameter(
            Parameter::string('functionName')->required()
                ->enum(array_values($availableFunctions))
                ->description("Choose one of the valid function names to active them.")
        );
        $metaDefs[] = $def->createFunctionDescription();

        return $metaDefs;
    }
    /**
     * Return all definitions from every namespace in the registry.
     */
    protected function getAllRegistryDefinitions(): array
    {
        $results = [];
        foreach ($this->registry as $entry) {
            $results[] = $entry['definition'];
        }
        return $results;
    }

    /**
     * Return only the definitions from a particular namespace.
     */
    protected function getNamespaceDefinitions(string $namespaceName): array
    {
        $results = [];
        foreach ($this->registry as $namespacedName => $entry) {
            // The first part is the namespace
            $parts = explode(self::NAMESPACE_SEPARATOR, $namespacedName, 2);
            if ($parts[0] === $namespaceName) {
                $results[] = $entry['definition'];
            }
        }
        return $results;
    }


    //---------------------------------------------------------------------
    // The extension part: prepend a DeveloperMessage listing namespaces
    //---------------------------------------------------------------------

    public function extend(MessageList $messageList): void
    {
        // If you want, you can show a developer message listing known namespaces each time
        if (empty($this->registry)) {
            return;
        }
        $messageList->prependMessages([$this->getNamespacesDeveloperMessage()]);
    }

    /**
     * Create a developer message describing the namespaces.
     */
    public function getNamespacesDeveloperMessage(): DeveloperMessage
    {
        $lines   = [self::$namespaceIntro];
        foreach ($this->namespaces as $nsName => $nsInfo) {
            $lines[] = "- {$nsName}: {$nsInfo['description']}";
        }
        return new DeveloperMessage(implode("\n", $lines));
    }
}