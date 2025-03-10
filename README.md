<div align="center">

![Preview](media/preview.png)

</div>

# OpenFunctions Core

This library provides a set of primitives that simplify LLM calling. It offers an easy way to define messages and message lists, create tool definitions, and execute tool calls. By abstracting these core functionalities, **OpenFunctions Core** helps you reduce boilerplate code and quickly integrate advanced tool-calling capabilities into your LLM-powered applications.

## Key Features

- ✅ **Structured Conversation Management** - Offers robust primitives for messages and message lists.
- ✅ **Unified LLM Tool Interface** - **OpenFunctions** provide a standardized API for defining and invoking tools within LLM-powered applications.
- ✅ **Function Registry & Meta-mode** - Manages multiple **OpenFunctions** and namespaces seamlessly, with support for dynamic activation and deactivation to keep tool definitions clear and concise.
- ✅ **Extensible Architecture** - Designed to be highly customizable, allowing you to extend or replace components with your own implementations.
- ✅ **Developer-Friendly Abstractions** - Reduces boilerplate code through intuitive helper classes for defining functions, parameters, and responses.
- ✅ **Pre-built Integrations** - Includes ready-to-use implementations for popular platforms such as Slack, GitHub, Bitbucket, Trello, and Jira Service Desk.


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

In some scenarios, you may want to use the same **OpenFunction** more than once with different configurations, or you might have different **OpenFunctions** that define methods with the same name. To handle these cases, the library provides an **OpenFunction Registry**, which itself is also an **OpenFunction**. This registry allows you to register each function under a unique namespace, ensuring that even if functions share the same underlying method name, they remain distinct.

For example, suppose you want one WeatherOpenFunction instance to operate in Celsius (the default) and another in Fahrenheit. You could register them as follows:

```php
<?php
use AssistantEngine\OpenFunctions\Core\Examples\WeatherOpenFunction;
use AssistantEngine\OpenFunctions\Core\Tools\OpenFunctionRegistry;

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
$toolDefinitions = $registry->generateFunctionDefinitions();

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

##### Meta Mode

The above example ensures that even if multiple functions are registered under similar method names, each one is uniquely namespaced and easily distinguishable. In some scenarios, however, the total number of methods you want to provide to the LLM may exceed the maximum number it can ingest in a single call. To address this, the **OpenFunction Registry** offers a meta-mode.

```php
use AssistantEngine\OpenFunctions\Core\Examples\DeliveryOpenFunction;
use AssistantEngine\OpenFunctions\Core\Tools\OpenFunctionRegistry;

$registry = new OpenFunctionRegistry(true, 'This is the registry where you can control active functions');

$burger = new DeliveryOpenFunction([
    'Classic Burger',
    'Cheese Burger',
    'Bacon Burger',
    'Veggie Burger',
    'Double Burger'
]);

$pizza = new DeliveryOpenFunction([
    'Margherita',
    'Pepperoni',
    'Hawaiian',
    'Veggie',
    'BBQ Chicken',
    'Meat Lovers'
]);

$sushi = new DeliveryOpenFunction([
    'California Roll',
    'Spicy Tuna Roll',
    'Salmon Nigiri',
    'Eel Avocado Roll',
    'Rainbow Roll',
    'Vegetable Roll'
]);

$registry->registerOpenFunction(
    'burger',
    'This is a nice burger place.',
    $burger
);

$registry->registerOpenFunction(
    'pizza',
    'This is a nice pizza place',
    $pizza
);

$registry->registerOpenFunction(
    'sushi',
    'This is a nice sushi place',
    $sushi
);
```

In **meta-mode**, only three core registry functions are initially registered to the LLM. These functions give the LLM the ability to activate and deactivate additional methods on the fly. This approach keeps the number of tool definitions sent to the LLM both limited and understandable. The following image demonstrates an activated meta-mode within the **[Filament Assistant Plugin](https://github.com/AssistantEngine/filament-assistant)**:

![Meta Mode](media/meta-mode.png)

The three registry functions provided in meta-mode are:

| **Method**       | **Description**                                                                                                 | **Parameters**                                                                                  |
|------------------|-----------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------|
| **listFunctions**    | Lists all available functions grouped by namespace, including their names, descriptions, and namespace details. | None                                                                                            |
| **activateFunction** | Activates one or more functions from the registry. Duplicate activations are ignored.                           | **functionNames**: *array of string* (required) An array of valid function names to activate.   |
| **deactivateFunction** | Deactivates one or more functions from the registry.                                                            | **functionNames**: *array of string* (required) An array of valid function names to deactivate. |

##### Registry Presenter

It can be a good idea to inform the LLM about the different namespaces and give a little more context. In order to do this, you might want to add a dedicated developer message to the message list that explains the namespaces. To achieve this dynamically, the concept of a **Message List Extension** is implemented. Extensions implement a specific interface and are invoked when the message list is built, giving you the opportunity to modify or extend the messages.

#### Message List Extensions

Sometimes it’s useful to add a message that explains context details to the LLM. For instance, you might want to inform the model about the namespaces registered by your tool. To achieve this dynamically, you can create an extension that implements the **MessageListExtensionInterface**. Once added to the message list, the extension is invoked automatically during the conversion process, allowing you to inject extra messages.

For example, the **RegistryPresenter** class implements this interface and in its **extend()** method it prepends a developer message that lists the namespaces:

```php
/**
 * Extend the message list by prepending a developer message with namespace details.
 *
 * @param MessageList $messageList
 * @return void
 */
public function extend(MessageList $messageList): void
{
    if (empty($this->registry->getNamespaces())) {
        return;
    }
    $messageList->prependMessages([$this->getNamespacesDeveloperMessage()]);
}
```

To add an extension to your message list, simply use the addExtension() method on your MessageList instance. Here’s how you can register the **RegistryPresenter** as an extension:

```php
use AssistantEngine\OpenFunctions\Core\Models\Messages\MessageList;
use AssistantEngine\OpenFunctions\Core\Tools\OpenFunctionRegistry;
use AssistantEngine\OpenFunctions\Core\Presenter\RegistryPresenter;

// Instantiate your registry and register any open functions as needed.
$registry = new OpenFunctionRegistry();
// (Assume functions are registered here...)

// Create a message list and add the registry as an extension.
$messageList = new MessageList();
$messageList->addExtension(new RegistryPresenter($registry),);

// When converting to an array, the registry extension prepends the developer message.
$conversationArray = $messageList->toArray();
```

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
$response = $weatherFunction->callMethod('getWeather', ['cityName' => 'New York']);

// Output the response as an array.
print_r($response->toArray());
```

and if you used the registry

```php
<?php
// Execute the function call using the registry.
$response = $registry->callMethod('celsius_getWeather', ['cityName' => 'New York']);
// Output the response as an array.
print_r($response->toArray());
```

so if you would use it within the llm loop it could look like something like this

```php
$response = $client->chat()->create([
    'model'         => 'gpt-4o',
    'messages'      => $conversationArray, 
    'tools'         => $toolDefinitions,
]);

if (isset($response->choices[0]->message->toolCalls)) {
    foreach ($response->choices[0]->message->toolCalls as $toolCall) {
        $namespacedName = $toolCall['function']['name'] ?? null;
        $argumentsJson = $toolCall['function']['arguments'] ?? '{}';
    
        $response = $registry->executeFunctionCall($namespacedName, json_decode($argumentsJson, true));
    }
}
```

### Available Open Function Implementations

In addition to creating your own **OpenFunction**, there are several ready-to-use implementations available.
Here’s a quick overview:

- **[Memory](https://github.com/AssistantEngine/open-functions-memory)**:  Provides a standardized API for storing, updating, retrieving, and removing conversational memories.
- **[Notion](https://github.com/AssistantEngine/open-functions-notion)**: Connects to your Notion workspace and enables functionalities such as listing databases, retrieving pages, and managing content blocks.
- **[GitHub](https://github.com/AssistantEngine/open-functions-github)**: Integrates with GitHub to allow repository operations like listing branches, reading files, and committing changes.
- **[Bitbucket](https://github.com/AssistantEngine/open-functions-bitbucket)**: Provides an interface similar to GitHub’s, enabling you to interact with Bitbucket repositories to list files, read file contents, and commit modifications.
- **[Trello](https://github.com/AssistantEngine/open-functions-trello)**: Enables interactions with Trello boards, lists, and cards, facilitating project management directly within your assistant.
- **[Slack](https://github.com/AssistantEngine/open-functions-slack)**: Seamlessly connects your assistant to Slack and perform actions like listing channels, posting messages, replying to threads, adding reactions, and retrieving channel history and user profiles.
- **[Jira Service Desk](https://github.com/AssistantEngine/open-functions-jira-service-desk)**: Integrates with Jira Service Desk to interact with service requests—enabling you to create, update, and manage requests (cards), list queues, add comments, transition statuses, and manage priorities.

## More Repositories

We’ve created more repositories to make AI integration even simpler and more powerful! Check them out:

- **[Filament Assistant](https://github.com/AssistantEngine/filament-assistant)**: Add conversational AI capabilities directly into Laravel Filament.

> We are a young startup aiming to make it easy for developers to add AI to their applications. We welcome feedback, questions, comments, and contributions. Feel free to contact us at [contact@assistant-engine.com](mailto:contact@assistant-engine.com).


## Consultancy & Support

Do you need assistance integrating Filament Assistant into your Laravel Filament application, or help setting it up?  
We offer consultancy services to help you get the most out of our package, whether you’re just getting started or looking to optimize an existing setup.

Reach out to us at [contact@assistant-engine.com](mailto:contact@assistant-engine.com).

## Contributing

We welcome contributions from the community! Feel free to submit pull requests, open issues, and help us improve the package.

## License

This project is licensed under the MIT License. Please see [License File](LICENSE.md) for more information.

