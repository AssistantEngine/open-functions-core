<?php

namespace AssistantEngine\OpenFunctions\Core\Helpers;

/**
 * Class SchemaDefinition
 *
 * Represents a structured output schema definition.
 *
 * Usage example:
 * $schemaDef = new SchemaDefinition('content_compliance', $parameter);
 * $schemaDescription = $schemaDef->createSchemaDescription();
 *
 * The resulting array will look like:
 * [
 *     'type' => 'json_schema',
 *     'json_schema' => [
 *         'name' => 'content_compliance',
 *         'schema' => $parameter->toArray(),
 *         'strict' => true
 *     ]
 * ];
 */
class SchemaDefinition
{
    /**
     * @var string The name of the schema.
     */
    private string $name;

    /**
     * @var Parameter The parameter that defines the schema.
     */
    private Parameter $schema;

    /**
     * @var bool Indicates if strict mode should be enforced.
     */
    private bool $strict;

    /**
     * SchemaDefinition constructor.
     *
     * @param string $name The schema name.
     * @param Parameter $schema A Parameter instance defining the schema structure.
     * @param bool $strict Whether to enforce strict type checks (default: true).
     */
    public function __construct(string $name, Parameter $schema, bool $strict = true)
    {
        $this->name   = $name;
        $this->schema = $schema;
        $this->strict = $strict;
    }

    /**
     * Creates the schema description array.
     *
     * @return array The structured schema definition.
     */
    public function createSchemaDescription(): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name'   => $this->name,
                'schema' => $this->schema->toArray(),
                'strict' => $this->strict,
            ],
        ];
    }
}