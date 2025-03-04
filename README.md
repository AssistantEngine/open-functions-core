# Open Functions Core

This library provides a set of primitives that simplify LLM calling. It offers an easy way to define messages and message lists, create tool definitions, and execute tool calls. By abstracting these core functionalities, OpenFunctions Core helps you reduce boilerplate code and quickly integrate advanced tool-calling capabilities into your LLM-powered applications.


## Installation
Install the package via Composer:

```bash
composer require assistant-engine/open-functions-core
```

## Usage

You can use this library for the following challenges:

```php
// A common llm call
$response = $client->chat()->create([
    'model'         => 'gpt-4o',
    'messages'      => $messages, // 1. Building the messages array
    'tools'         => $functionDefinitions, // 2. Collect the right function definitions
]);

if (isset($response->choices[0]->message->toolCalls)) {
    foreach ($response->choices[0]->message->toolCalls as $toolCall) {
        // 3. Executing the requested tool call
    }
}
```

## Messages

Based on the OpenAI schema, the available message types are exposed as primitives. You can use these as building blocks to structure your conversation. The following example shows how to define an array of messages, add them to a MessageList, and convert that list to an array for use in an LLM call.

```php
<?php
use AssistantEngine\OpenFunctions\Core\Models\Messages\Content\ToolCall;
use AssistantEngine\OpenFunctions\Core\Models\Messages\DeveloperMessage;
use AssistantEngine\OpenFunctions\Core\Models\Messages\ToolMessage;
use AssistantEngine\OpenFunctions\Core\Models\Messages\UserMessage;
use AssistantEngine\OpenFunctions\Core\Models\Messages\AssistantMessage;
use AssistantEngine\OpenFunctions\Core\Models\Messages\MessageList;

// Define an array of messages based on the OpenAI API schema.
// These primitives can be used to structure the conversation context.
$messages = [
    new DeveloperMessage("You are a helpful assistant."),
    new UserMessage("What's the weather like today in Paris?"),
    (new AssistantMessage())
        ->addToolCall(new ToolCall("tool_call_1", "getWeather", json_encode(["cityName" => "Paris"]))),
    new ToolMessage("The weather in Paris is sunny with a temperature of 24°C.", "tool_call_1"),
    new AssistantMessage("The current weather in Paris is sunny with a high of 24°C."),
    new UserMessage("Thanks!")
];

// Create a MessageList and add the messages array
$messageList = new MessageList();
$messageList->addMessages($messages);

// Convert the MessageList to an array for use in an API call
$conversationArray = $messageList->toArray();

// These definitions can now be used as the tools parameter in your OpenAI client call.
$response = $client->chat()->create([
    'model'    => 'gpt-4o',
    'messages' => $conversationArray
]);

```

## Tool Calling

In order to enable tool calling, the common challenge is to both define the function definitions and expose the methods to make tool calling possible. To address this, the concept of an **OpenFunction** is introduced. Inside an **OpenFunction**, you generate the function definitions and implement the methods that the LLM can invoke. The **AbstractOpenFunction** class provides convenience methods such as callMethod() to wrap the output in a standardized response and handle errors consistently.

### Function Definitions

Each class that extends the abstract open function must implement the generateFunctionDefinitions() method. This method is responsible for describing the functions that your tool exposes. To build these descriptions, you can use the provided helper classes:

- **FunctionDefinition:** This class is used to create a structured schema for a function. It accepts a function name and a short description and can include details about parameters.
- **Parameter:** This helper is used to define parameters for your function. It allows you to set the type (e.g., string, number, boolean) and additional details like description and whether the parameter is required.

For example, here’s a simple implementation:

```php
<?php
use AssistantEngine\OpenFunctions\Core\Contracts\AbstractOpenFunction;
use AssistantEngine\OpenFunctions\Core\Helpers\FunctionDefinition;
use AssistantEngine\OpenFunctions\Core\Helpers\Parameter;
use AssistantEngine\OpenFunctions\Core\Models\Responses\TextResponseItem;

class WeatherOpenFunction extends AbstractOpenFunction
{
    /**
     * Generate function definitions.
     *
     * This method returns a schema defining the "getWeather" function.
     * It requires a cityName parameter to fetch the current weather.
     *
     * @return array
     */
    public function generateFunctionDefinitions(): array
    {
        // Create a function definition for getWeather.
        $functionDef = new FunctionDefinition('getWeather', 'Returns the current weather for a given city.');
        
        // Add a required parameter for the city name.
        $functionDef->addParameter(
            Parameter::string('cityName')
                ->description('The name of the city to get the weather for.')
                ->required()
        );
        
        // Return the function definition as an array.
        return [$functionDef->createFunctionDescription()];
    }
}
```

Once you have implemented your open functions (such as the WeatherOpenFunction), you can generate their function definitions and pass them to the OpenAI client as the tools parameter. For example:

```php
<?php
use AssistantEngine\OpenFunctions\Core\Examples\WeatherOpenFunction;

// Instantiate the WeatherOpenFunction.
$weatherFunction = new WeatherOpenFunction();

// Generate the function definitions. This creates a schema for functions like "getWeather" and "getForecast".
$functionDefinitions = $weatherFunction->generateFunctionDefinitions();

// These definitions can now be used as the tools parameter in your OpenAI client call.
$response = $client->chat()->create([
    'model'    => 'gpt-4o',
    'messages' => $conversationArray,
    'tools'    => $functionDefinitions,
]);

// Process the response and execute any tool calls as needed.
```

#### Open Function Registry

In some scenarios, you may want to use the same **OpenFunction** more than once with different configurations, or you might have different **OpenFunctions** that define methods with the same name. To handle these cases, the library provides an **OpenFunction Registry**. This registry allows you to register each function under a unique namespace, ensuring that even if functions share the same underlying method name, they remain distinct.

For example, suppose you want one WeatherOpenFunction instance to operate in Celsius (the default) and another in Fahrenheit. You could register them as follows:

```php
<?php
use AssistantEngine\OpenFunctions\Core\Examples\WeatherOpenFunction;
use AssistantEngine\OpenFunctions\Core\Services\OpenFunctionRegistry;

// Create an instance of the registry.
$registry = new OpenFunctionRegistry();

// Instantiate two WeatherOpenFunction instances.
// For this example, imagine the WeatherOpenFunction can be configured to use different temperature units.
// The first instance is set for Celsius (default), and the second for Fahrenheit.
$weatherCelsius = new WeatherOpenFunction("celsius"); // Configured to return temperatures in Celsius.
$weatherFahrenheit = new WeatherOpenFunction("fahrenheit"); // Imagine this instance is configured to return Fahrenheit.

// Register the functions under different namespaces.
// The registry automatically prefixes function names with the namespace (e.g., "celsius_getWeather", "fahrenheit_getWeather").
$registry->registerOpenFunction('celsius', 'Weather functions using Celsius.', $weatherCelsius);
$registry->registerOpenFunction('fahrenheit', 'Weather functions using Fahrenheit.', $weatherFahrenheit);

// Retrieve all namespaced function definitions to pass to the OpenAI client.
$toolDefinitions = $registry->getFunctionDefinitions();

// Use these tool definitions in the client call.
$response = $client->chat()->create([
    'model'    => 'gpt-4o',
    'messages' => $conversationArray,
    'tools'    => $toolDefinitions,
]);

// Later, when the client calls a function, the registry will use the namespaced function name 
// (e.g., "celsius_getWeather" or "fahrenheit_getWeather") to invoke the correct method.
```

In this example, the registry ensures that even though both WeatherOpenFunction instances share the same method names (like getWeather), they are uniquely identified by their namespaces (celsius and fahrenheit). This separation allows you to call the appropriate function based on the desired temperature unit without any naming collisions.

### Function Calling

Implement all callable methods within your **OpenFunction** class. Each method should return a string, a text response, a binary response, or a list of responses. The callMethod in the abstract class ensures the output is consistently wrapped.

For example, in WeatherOpenFunction:

```php
<?php
use AssistantEngine\OpenFunctions\Core\Contracts\AbstractOpenFunction;
use AssistantEngine\OpenFunctions\Core\Helpers\FunctionDefinition;
use AssistantEngine\OpenFunctions\Core\Helpers\Parameter;
use AssistantEngine\OpenFunctions\Core\Models\Responses\TextResponseItem;

class WeatherOpenFunction extends AbstractOpenFunction
{
    /**
     * Returns the current weather for the given city.
     *
     * @param string $cityName
     * @return TextResponseItem
     */
    public function getWeather(string $cityName)
    {
        $weathers = ['sunny', 'rainy', 'cloudy', 'stormy', 'snowy', 'windy'];
        $weather = $weathers[array_rand($weathers)];
    
        return new TextResponseItem("The weather in {$cityName} is {$weather}.");
    }
    
    // ...
}
```

To invoke the function:

```php
// Instantiate the WeatherOpenFunction.
$weatherFunction = new WeatherOpenFunction();

// Call the 'getWeather' method via callMethod.
$response = $weatherFunction->callMethod('getWeather', ['Paris']);

// Output the response as an array.
print_r($response->toArray());
```

## Contributing

We welcome contributions from the community! Feel free to submit pull requests, open issues, and help us improve the package.

## License

This project is licensed under the MIT License. Please see [License File](LICENSE.md) for more information.

