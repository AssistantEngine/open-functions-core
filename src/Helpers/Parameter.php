<?php

namespace AssistantEngine\OpenFunctions\Core\Helpers;

/**
 * Class Parameter
 *
 * Represents a parameter for a function definition.
 * Allows creation and configuration of parameters for complex schemas.
 *
 * Usage example:
 * $param = Parameter::string('name')->description('A user name')->required();
 * $paramArray = $param->toArray();
 *
 * Allows defining complex parameters using fluent methods for tool integrations.
 *
 * Properties:
 * @property string $name The parameter name.
 * @property string $type The type of the parameter, e.g., string, array.
 * @property string $description A description of the parameter's purpose.
 * @property array|null $enum An array of possible values for enum parameters.
 *
 * Methods:
 * - __construct(?string $name): Creates a new parameter instance.
 * - string(?string $name): Creates a new string type parameter.
 * - number(?string $name): Creates a new number type parameter.
 * - boolean(?string $name): Creates a new boolean type parameter.
 * - object(string $name): Creates a new object type parameter.
 * - array(string $name): Creates a new array type parameter.
 * - anyOf(string $name, array $schemas): Creates a parameter that can be one of the provided types.
 * - description(string $description): Sets the description.
 * - enum(array $values): Sets an array of valid enum values.
 * - required(): Marks this parameter as required.
 * - addProperty(Parameter $parameter): Adds a property to an object type parameter.
 * - setItems(Parameter $parameter): Sets the items of an array type parameter.
 * - getName(): Gets the name of the parameter.
 * - toArray(): Returns the parameter as an array representation.
 *
 */
class Parameter
{
    private $name;
    private $type; // Can be string, array, or anyOf
    private $description;
    private $enum;
    private $items;
    private $properties = [];
    private $requiredProperties = [];
    private $anyOf = [];
    private $nullable = false;

    /**
     * Constructor.
     *
     * @param ?string $name The name of the parameter.
     */
    public function __construct(?string $name)
    {
        $this->name = $name;
    }

    /**
     * Creates a string type parameter.
     *
     * @param ?string $name The name of the parameter (nullable for use in anyOf).
     * @return self
     */
    public static function string(?string $name): self
    {
        return (new self($name))->setType('string');
    }

    /**
     * Creates a number type parameter.
     *
     * @param ?string $name The name of the parameter (nullable for use in anyOf).
     * @return self
     */
    public static function number(?string $name): self
    {
        return (new self($name))->setType('number');
    }

    /**
     * Creates a boolean type parameter.
     *
     * @param string $name The name of the parameter.
     * @return self
     */
    public static function boolean(?string $name): self
    {
        return (new self($name))->setType('boolean');
    }

    /**
     * Creates an object type parameter.
     *
     * @param string $name The name of the parameter.
     * @return self
     */
    public static function object(?string $name): self
    {
        return (new self($name))->setType('object');
    }

    /**
     * Creates an array type parameter.
     *
     * @param string $name The name of the parameter.
     * @return self
     */
    public static function array(?string $name): self
    {
        return (new self($name))->setType('array');
    }

    /**
     * Creates a parameter that can be of multiple specified types (anyOf).
     *
     * @param string $name The name of the parameter.
     * @param array $schemas Array of schemas.
     * @return self
     */
    public static function anyOf(string $name, array $schemas): self
    {
        return (new self($name))->setAnyOf($schemas);
    }

    /**
     * Sets the description of the parameter.
     *
     * @param string $description
     * @return self
     */
    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Defines an enum with possible values for the parameter.
     *
     * @param array $values
     * @return self
     */
    public function enum(array $values): self
    {
        $this->enum = $values;
        return $this;
    }

    /**
     * Marks the parameter as required.
     *
     * @return self
     */
    public function required(): self
    {
        $this->requiredProperties[] = $this->name;
        return $this;
    }

    /**
     * Adds a property to an object type parameter.
     *
     * @param Parameter $parameter
     * @return self
     */
    public function addProperty(Parameter $parameter): self
    {
        $this->properties[$parameter->getName()] = $parameter->toArray();
        if ($parameter->isRequired()) {
            $this->requiredProperties[] = $parameter->getName();
        }
        return $this;
    }

    /**
     * Specifies the items type for an array parameter.
     *
     * @param Parameter $parameter
     * @return self
     */
    public function setItems(Parameter $parameter): self
    {
        $this->items = $parameter->toArray();
        return $this;
    }

    /**
     * Retrieves the parameter name.
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Converts the parameter to an array format for schema conformity.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (empty($this->type)) {
            throw new \Exception('Parameter type is required');
        }

        $param = [
            'type' => $this->getType(),
            'description' => $this->description,
        ];

        if (!empty($this->anyOf)) {
            return ['anyOf' =>  array_map(fn($schema) => $schema->toArray(), $this->anyOf)];
        } else {
            if ($this->enum) {
                $param['enum'] = $this->enum;
            }

            if ($this->type === 'array' && $this->items) {
                $param['items'] = $this->items;
            }

            if ($this->nullable) {
                $param['nullable'] = true;
            }

            if ($this->type === 'object') {
                $param['properties'] = $this->properties;
                $param['required'] = array_values(array_filter(
                    $this->requiredProperties ?? [],
                    function ($property) {
                        return array_key_exists($property, $this->properties);
                    }
                ));
                $param['additionalProperties'] = false;
            }
        }

        return $param;
    }

    /**
     * Sets the parameter type.
     *
     * @param $type
     * @return self
     */
    private function setType($type): self
    {
        $this->type = $type;
        return $this;
    }

    public function nullable()
    {
        $this->nullable = true;
        return $this;
    }

    private function getType()
    {
        return $this->type;
    }

    /**
     * Defines anyOf schema.
     *
     * @param array $schemas
     * @return self
     */
    private function setAnyOf(array $schemas): self
    {
        $this->setType('anyOf');
        $this->anyOf = $schemas;
        return $this;
    }

    /**
     * Checks if the parameter is required.
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return in_array($this->name, $this->requiredProperties);
    }
}
