<?php

namespace AssistantEngine\OpenFunctions\Core\Helpers;

/**
 * Class FunctionDefinition
 *
 * Represents a function definition for OpenAI's function calling and Structured Outputs.
 * Provides methods to define and structure parameters for robust schema adherence.
 *
 * Usage example:
 * $functionDef = new FunctionDefinition('MyFunction', 'Performs calculations');
 * $functionDef->addParameter(Parameter::string('input'));
 * $description = $functionDef->createFunctionDescription();
 * 
 * Outputs a function schema conforming to OpenAI standards for tool integration.
 *
 * Properties:
 * @property string $name The function name.
 * @property string $description A short description of the function's purpose.
 * @property array $parameters Definition of parameters used in the function.
 * @property array $required List of required parameter names.
 * @property bool $strict Indicates if the schema should enforce strict type checks.
 */
class FunctionDefinition
{
    private $name;
    private $description;
    private $parameters = [];
    private $required = [];
    private $strict = true; // Default to true as per OpenAI's recommendations

    /**
     * Constructor to initialize the function definition.
     *
     * @param string $name Name of the function.
     * @param string $description Description of the function.
     */
    public function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * Adds a parameter to the function definition.
     *
     * @param Parameter $parameter The parameter to add.
     * @return self Returns the current instance.
     */
    public function addParameter(Parameter $parameter): self
    {
        $this->parameters[$parameter->getName()] = $parameter->toArray();
        if ($parameter->isRequired()) {
            $this->required[] = $parameter->getName();
        }
        return $this;
    }

    /**
     * Sets the strictness of the schema definition.
     *
     * @param bool $strict True for strict mode.
     * @return self Returns the current instance.
     */
    public function setStrict(bool $strict): self
    {
        $this->strict = $strict;
        return $this;
    }

    /**
     * Creates the function description schema array.
     *
     * @return array The function schema definition.
     */
    public function createFunctionDescription(): array
    {
        $parametersSection = [
            'type' => 'object',
            'properties' => $this->parameters,
            'additionalProperties' => false
        ];

        if (!empty($this->required)) {
            $parametersSection['required'] = $this->required;
        }

        if (empty($this->parameters) && empty($this->required)) {
            $this->strict = false;
            $functionDefinition = [
                'name' => $this->name,
                'description' => $this->description,
                'strict' => $this->strict,
            ];
        } else {
            $functionDefinition = [
                'name' => $this->name,
                'description' => $this->description,
                'strict' => $this->strict,
                'parameters' => $parametersSection
            ];
        }

        return [
            'type' => 'function',
            'function' => $functionDefinition
        ];
    }
}
