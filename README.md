# Open Functions Core

**Open Functions** provide a standardized way to implement and invoke functions for tool calling with large language models (LLMs). They encapsulate both the generation of structured function definitions and the actual function execution within a unified interface. At the heart of this framework is the *AbstractOpenFunction* class, which standardizes:

- **Function Definition Generation:**
Using built-in helpers like *FunctionDefinition* and *Parameter*, developers can define function schemas that are fully compliant with LLM tool-calling requirements. This ensures that every function exposes its parameters, types, and descriptions in a consistent format.
- **Function Invocation:**
The *AbstractOpenFunction* class includes a standardized method (callMethod) to invoke the actual implementation of the function. This method guarantees that results are wrapped into a Response object, handling single responses, arrays of response items, and even exceptions in a unified manner.
- **Message Handling:**
Additional helper classes are provided for working with messages (*UserMessage*, *AssistantMessage*, *DeveloperMessage*, and *ToolMessage*composr) and managing message lists. This makes it easier to integrate function calls into conversation flows with LLMs.

Together, these components simplify the process of integrating tool calling capabilities into your application, reducing boilerplate and ensuring that both the function definitions and their invocations follow a clear, predictable standard.

## Table of Contents
- [Features](#features)
- [Installation](#installation)
- [Basic Concepts](#basic-concepts)
  - [AbstractOpenFunction](#abstractopenfunction)
  - [FunctionDefinition & Parameter](#functiondefinition--parameter)
  - [OpenFunctionRegistry](#openfunctionregistry)
  - [Messages & MessageList](#messages--messagelist)
- [Usage Examples](#usage-examples)
  - [Registering and Calling Functions](#registering-and-calling-functions)
  - [Using OpenAI PHP SDK to Call a Function](#using-openai-php-sdk-to-call-a-function)
  - [Utilizing the Function Registry with OpenAI](#utilizing-the-function-registry-with-openai)
  - [Working with Responses](#working-with-responses)
- [Examples](#examples)
  - [DeliveryOpenFunction](#deliveryopenfunction)
  - [WeatherOpenFunction](#weatheropenfunction)
- [Contributing](#contributing)
- [License](#license)

## Features
- **AbstractOpenFunction**: Base class for creating custom Open Functions.
- **FunctionDefinition and Parameter**: Helpers to generate valid function definitions for LLM function calling.
- **OpenFunctionRegistry**: Namespace and manage multiple Open Functions.
- **Message classes**: Build conversation history in an object-oriented way (UserMessage, AssistantMessage, DeveloperMessage, etc.).
- **MessageList and Extensions**: Manage message lists and hook in additional messages (e.g., developer instructions about available namespaces).
- **Response Objects**: Standardized function call return data (success/failure, multiple items, binary/text, etc.).

## Installation
Install the package via Composer:

```bash
composer require assistant-engine/open-functions-core
```

## Basic Concepts
### AbstractOpenFunction
The `AbstractOpenFunction` class is the foundation for all Open Functions. Extend this class to implement your functions. It:

- Provides a `callMethod(...)` handler that wraps output into a Response.
- Declares an abstract `generateFunctionDefinitions()` method for function definitions.
- Ensures consistent error handling in case of invalid function calls or exceptions.

#### Example: HelloWorld Open Function Using FunctionDefinition and Parameter

```php
<?php
use AssistantEngine\OpenFunctions\Core\Contracts\AbstractOpenFunction;
use AssistantEngine\OpenFunctions\Core\Models\Responses\TextResponseItem;
use AssistantEngine\OpenFunctions\Core\Helpers\FunctionDefinition;
use AssistantEngine\OpenFunctions\Core\Helpers\Parameter;

class HelloWorldOpenFunction extends AbstractOpenFunction
{
    /**
     * Generate function definitions.
     *
     * This method returns a schema that defines the "helloWorld" function.
     */
    public function generateFunctionDefinitions(): array
    {
        // Create a new function definition for helloWorld.
        $functionDef = new FunctionDefinition(
            'helloWorld',
            'Returns a friendly greeting.'
        );

        // In this simple example, no parameters are required.
        // If parameters were needed, you could add them like this:
        // $functionDef->addParameter(Parameter::string("name")
        //     ->description("Optional name to greet")
        //     ->required());
        
        // Return the function schema as an array.
        return [$functionDef->createFunctionDescription()];
    }

    /**
     * The actual implementation of the function.
     *
     * @return TextResponseItem A text response containing the greeting.
     */
    public function helloWorld()
    {
        return new TextResponseItem("Hello, world!");
    }
}

// --- Usage Example ---

// Instantiate the open function.
$helloFunction = new HelloWorldOpenFunction();

// Generate function definitions to be used as the "tools" parameter.
$tools = $helloFunction->generateFunctionDefinitions();

// Example: Using the tools in an OpenAI API call (pseudo-code)
// Assume you have an OpenAI client that accepts a "tools" parameter.
$client = new OpenAIClient('YOUR_API_KEY');
$response = $client->chat()->create([
    'model'    => 'gpt-4o',
    'messages' => [
        ['role' => 'user', 'content' => 'Greet me please.']
    ],
    'tools'    => $tools,
]);

// Output the response content.
print_r($response);

```

In this example:
- HelloWorldOpenFunction extends the abstract open function class.
- The generateFunctionDefinitions method uses the FunctionDefinition helper to create the function schema. Although no parameters are required here, the code shows how to add one using the Parameter helper if needed.
- The helloWorld method implements the function to return a “Hello, world!” greeting.
- Finally, the tool definitions are extracted and passed as the tools parameter when calling the OpenAI API.

### FunctionDefinition & Parameter
These classes help build a JSON schema definition for your function:

```php
use AssistantEngine\OpenFunctions\Core\Helpers\FunctionDefinition;
use AssistantEngine\OpenFunctions\Core\Helpers\Parameter;

// Create a function definition with name "myFunction" and a short description
$funcDef = new FunctionDefinition('myFunction', 'A function that does something');

// Add a required string parameter called "inputValue"
$funcDef->addParameter(
    Parameter::string('inputValue')
        ->description('An input for the function')
        ->required()
);

// Generate the final definition array for usage with OpenAI
$definition = $funcDef->createFunctionDescription();
```

### OpenFunctionRegistry
The OpenFunctionRegistry is a centralized place to:
1.	Register one or more AbstractOpenFunction instances under a specific namespace (e.g., delivery, weather).
2.	Retrieve all aggregated function definitions.
3.	Execute a namespaced function call by name (e.g., delivery_orderProduct).

The registry also implements MessageListExtensionInterface, meaning it can automatically prepend a developer message about the registered namespaces if you add it as an extension in your MessageList.

### Messages & MessageList
All conversation elements (user input, system instructions, developer instructions, tool/assistant responses) are represented by:
- **UserMessage:** The user’s input.
- **AssistantMessage:** The model’s response.
- **SystemMessage:** Traditional system-level instructions for older OpenAI models.
- **DeveloperMessage:** Developer instructions for the newer o1 models (replacement for SystemMessage).
- **ToolMessage:** Responses from a tool to the LLM.

These are stored and managed in a **MessageList**, which can be converted to an array suitable for the OpenAI Chat API.

## Usage Examples

### Registering and Calling Functions

Below is a minimal example using the DeliveryOpenFunction and WeatherOpenFunction from the repository’s Examples folder. We will:
1.	Instantiate our Open Functions.
2.	Register them with a namespace in the OpenFunctionRegistry.
3.	Fetch their combined function definitions.
4.	Execute a test call locally using the executeFunctionCall method.

```php
use AssistantEngine\OpenFunctions\Core\Examples\DeliveryOpenFunction;
use AssistantEngine\OpenFunctions\Core\Examples\WeatherOpenFunction;
use AssistantEngine\OpenFunctions\Core\Services\OpenFunctionRegistry;

$deliveryFunction = new DeliveryOpenFunction(['Pizza', 'Burger', 'Sushi']);
$weatherFunction  = new WeatherOpenFunction();

$registry = new OpenFunctionRegistry();
// Register DeliveryOpenFunction under the "delivery" namespace
$registry->registerOpenFunction(
    'delivery',
    'Handles product delivery, orders, and shipping details.',
    $deliveryFunction
);
// Register WeatherOpenFunction under the "weather" namespace
$registry->registerOpenFunction(
    'weather',
    'Handles weather and forecasting services.',
    $weatherFunction
);

// Access all function definitions (namespaced)
$allDefinitions = $registry->getFunctionDefinitions();
print_r($allDefinitions);

// Execute a sample function call (simulate an LLM request to "delivery_orderProduct")
$response = $registry->executeFunctionCall('delivery_orderProduct', [
    'productName' => 'Pizza',
    'quantity'    => 2
]);

// The response is a standard Response object
if ($response->isError) {
    echo "Error: " . print_r($response->toArray(), true);
} else {
    echo "Success: " . print_r($response->toArray(), true);
}
```

### Using OpenAI PHP SDK to Call a Function

Suppose you have a conversation with a user, and you suspect that the user’s prompt will trigger a function call. With the OpenAI PHP SDK (or a similar library), you can provide function definitions and messages.

Below is an example (pseudo-code) of how you might call the OpenAI Chat Completions endpoint and let it auto-call a function:

```php
use OpenAI;

// 1. Prepare your messages
$messageList = new MessageList();

// Add a user message
$messageList->addMessage(new UserMessage("I want to order 2 Burgers"));

// 2. Convert messages to array
$messagesArray = $messageList->toArray();

// 3. Prepare the function definitions from your registry
$functionDefinitions = $registry->getFunctionDefinitions();

// 4. Call the Chat API
$client = OpenAI::client('YOUR_OPENAI_API_KEY');

$response = $client->chat()->create([
    'model'         => 'gpt-4o',
    'messages'      => $messagesArray,
    'tools'         => $functionDefinitions, // Provide all your known function definitions
]);

// 5. Parse the response
// The model might either return text or a function call (tool call).
if (isset($response->choices[0]->message->toolCalls)) {
    foreach ($response->choices[0]->message->toolCalls as $toolCall) {
        // Extract the function name (already namespaced) and arguments
        $functionName = $toolCall->function->name;
        $functionArgs = json_decode($toolCall->function->arguments, true);

        // 6. Execute the function via the registry
        $toolResponse = $registry->executeFunctionCall($functionName, $functionArgs);

        // 7. Return the tool's response back to the conversation (or handle it as you wish)
        print_r($toolResponse->toArray());
    }
} else {
    // Regular text response from the model
    echo $response->choices[0]->message->content;
}
```

### Utilizing the Function Registry with OpenAI

In the above example, we manually provided the function definitions. Another approach is to let the OpenFunctionRegistry automatically insert a developer message about the namespaces. You do this by adding the registry as a MessageList extension:

```php
$messageList = new MessageList();
$messageList->addExtension($registry); // <-- This will prepend a DeveloperMessage automatically

$messageList->addMessage(new UserMessage("What's the weather in Berlin for the next 3 days?"));

// Now when you do $messageList->toArray(), 
// it has a developer message listing the registered namespaces 
// plus your user message.
$messagesArray = $messageList->toArray();

$functionDefinitions = $registry->getFunctionDefinitions();

// Use $messagesArray and $functionDefinitions in an OpenAI chat call as before...
```

When the toArray() method is called, the registry’s extension will run and prepend a developer message enumerating the available tool namespaces. That helps the model know which functions exist and are available.

### Working with Responses

All function calls return a Response object, which contains:
- isError (boolean) indicating if the function call succeeded or failed.
- content (an array of ResponseItem objects).

A ResponseItem can be:
- TextResponseItem: contains plain text.
- BinaryResponseItem: contains base64 or binary data.

**Example**

If you call:

```php
$response = $registry->executeFunctionCall('delivery_listProducts', []);
print_r($response->toArray());
```

You might get:

```php
Array
(
    [isError] => false
    [content] => Array
        (
            [0] => Array
                (
                    [type] => text
                    [text] => Available products: Pizza, Burger, Sushi.
                )
        )
)
```

## Example Open Functions

### DeliveryOpenFunction

See [DeliveryOpenFunction.php](src/Examples/DeliveryOpenFunction.php) for a real-world example. It demonstrates how to:
- Implement multiple methods (listProducts, orderProduct, etc.).
- Return different text responses based on the function call.
- Provide function definitions for each method using FunctionDefinition and Parameter.

### WeatherOpenFunction

See [WeatherOpenFunction.php](src/Examples/WeatherOpenFunction.php) for an example that:
- Fetches (or simulates) current weather conditions.
- Simulates multi-day forecasts.

## Contributing

We welcome contributions from the community! Feel free to submit pull requests, open issues, and help us improve the package.

## License

This project is licensed under the MIT License. Please see [License File](LICENSE.md) for more information.

