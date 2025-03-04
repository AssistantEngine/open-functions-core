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
use AssistantEngine\OpenFunctions\Core\Models\Messages\DeveloperMessage;
use AssistantEngine\OpenFunctions\Core\Models\Messages\UserMessage;
use AssistantEngine\OpenFunctions\Core\Models\Messages\AssistantMessage;
use AssistantEngine\OpenFunctions\Core\Models\Messages\MessageList;

// Define an array of messages based on the OpenAI API schema.
// These primitives can be used to structure the conversation context.
$messages = [
    new DeveloperMessage("You are ChatGPT, a helpful assistant."),
    new UserMessage("What's the weather like today in Paris?"),
    new AssistantMessage("The current weather in Paris is sunny with a high of 24°C."),
    new UserMessage("Thanks! And can I order a coffee?")
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

## Contributing

We welcome contributions from the community! Feel free to submit pull requests, open issues, and help us improve the package.

## License

This project is licensed under the MIT License. Please see [License File](LICENSE.md) for more information.

