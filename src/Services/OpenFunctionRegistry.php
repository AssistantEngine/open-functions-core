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

    /**
     * Holds the currently active namespace for usage in meta mode.
     * If null => no namespace is active.
     */
    protected ?string $activeNamespace = null;

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
    public static string $namespaceIntro = 'Registered tool namespaces:';

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

        // If meta mode is enabled => first build the "meta" method definitions:
        $metaDefs = $this->getMetaMethodDefinitions();

        // Then, if there's an active namespace, also return those definitions
        if ($this->activeNamespace) {
            $nsDefs = $this->getNamespaceDefinitions($this->activeNamespace);
            return array_merge($metaDefs, $nsDefs);
        }

        // Otherwise, no active namespace => only the meta definitions
        return $metaDefs;
    }

    /**
     * Handles the meta calls OR dispatches them to the underlying open function objects.
     */
    public function executeFunctionCall(string $methodName, array $arguments = []): Response
    {
        // Define allowed meta methods.
        $allowedMetaMethods = ['activateNamespace', 'listAllNamespaces', 'listMethodsInNamespace'];

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
     * 3) Sets the active namespace for usage in meta mode.
     */
    public function activateNamespace(string $namespaceName): TextResponseItem
    {
        // Validate
        if (!isset($this->namespaces[$namespaceName])) {
            return new TextResponseItem("No such namespace: '{$namespaceName}'");
        }

        $this->activeNamespace = $namespaceName;
        return new TextResponseItem("Active namespace set to '{$namespaceName}'. Only that namespace’s methods + meta methods are exposed (while meta mode is on).");
    }

    /**
     * 4) Lists all namespace names & descriptions
     */
    public function listAllNamespaces(): TextResponseItem
    {
        if (empty($this->namespaces)) {
            return new TextResponseItem("No namespaces registered.");
        }
        $lines = [];
        foreach ($this->namespaces as $ns => $data) {
            $lines[] = "- {$ns}: " . ($data['description'] ?? 'No description');
        }
        return new TextResponseItem("Namespaces:\n" . implode("\n", $lines));
    }

    /**
     * 5) Lists methods available in a namespace (or the active one if not provided).
     */
    public function listMethodsInNamespace(?string $namespaceName = null): TextResponseItem
    {
        if (!$namespaceName) {
            // If none passed, use the active one (if any)
            $namespaceName = $this->activeNamespace;
        }

        if (!$namespaceName) {
            return new TextResponseItem("No namespace specified and no active namespace set.");
        }
        if (!isset($this->namespaces[$namespaceName])) {
            return new TextResponseItem("Namespace '{$namespaceName}' not registered.");
        }

        // Gather all the function names that belong to $namespaceName
        $methods = [];
        foreach ($this->registry as $namespacedName => $entry) {
            $parts = explode(self::NAMESPACE_SEPARATOR, $namespacedName, 2);
            if ($parts[0] === $namespaceName) {
                $methods[] = $parts[1];
            }
        }
        if (empty($methods)) {
            return new TextResponseItem("No methods found in namespace '{$namespaceName}'.");
        }
        $listStr = implode("\n- ", $methods);
        return new TextResponseItem("Methods in '{$namespaceName}':\n- {$listStr}");
    }

    //---------------------------------------------------------------------
    // Utility / Private Helpers
    //---------------------------------------------------------------------

    /**
     * Return an array of "meta" function definitions for the registry itself.
     * These describe the built-in methods like activateMetaMode(), activateNamespace(), etc.
     */
    protected function getMetaMethodDefinitions(): array
    {
        $metaDefs = [];


        // 3) activateNamespace(namespaceName)
        $def3 = new FunctionDefinition(
            'activateNamespace',
            'Set the active namespace. Only relevant in meta mode.'
        );
        $def3->addParameter(
            Parameter::string('namespaceName')->required()
                ->description("Name of the namespace to activate.")
        );
        $metaDefs[] = $def3->createFunctionDescription();

        // 4) listAllNamespaces()
        $def4 = new FunctionDefinition(
            'listAllNamespaces',
            'List all registered namespaces.'
        );
        $metaDefs[] = $def4->createFunctionDescription();

        // 5) listMethodsInNamespace(namespaceName?)
        $def5 = new FunctionDefinition(
            'listMethodsInNamespace',
            'List the methods within a given namespace. If none specified, uses the active namespace.'
        );
        $def5->addParameter(
            Parameter::string('namespaceName')
                ->nullable() // optional
                ->description("Optionally, which namespace to list. If omitted, uses the active one.")
        );
        $metaDefs[] = $def5->createFunctionDescription();

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