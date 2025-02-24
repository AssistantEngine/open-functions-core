<?php

namespace AssistantEngine\OpenFunctions\Core\Contracts;

use AssistantEngine\OpenFunctions\Core\Models\Responses\Response;
use AssistantEngine\OpenFunctions\Core\Models\Responses\ResponseItem;
use AssistantEngine\OpenFunctions\Core\Models\Responses\TextResponseItem;

abstract class AbstractOpenFunction
{
    /**
     * Calls a method and always returns a Response.
     *
     * This method ensures that the result of the call is either:
     * - A Response (returned directly),
     * - A ResponseItem (wrapped in an array), or
     * - An array of ResponseItems (each element is verified or wrapped).
     *
     * If the result is none of the above, it is wrapped as a TextResponseItem.
     *
     * @param string $methodName The method to call.
     * @param mixed|array $arguments  The arguments to pass to the method.
     *
     * @return Response
     */
    public function callMethod(string $methodName, array $arguments = []): Response
    {
        try {
            if (!method_exists($this, $methodName)) {
                throw new \BadMethodCallException("Method $methodName does not exist.");
            }

            $result = $this->$methodName(...$arguments);

            // If a Response is already returned, use it as is.
            if ($result instanceof Response) {
                return $result;
            }

            // If a single ResponseItem is returned, wrap it in an array.
            if ($result instanceof ResponseItem) {
                return new Response(Response::STATUS_SUCCESS, [$result]);
            }

            // If an array is returned, ensure each element is a ResponseItem.
            if (is_array($result)) {
                $items = [];
                foreach ($result as $item) {
                    if ($item instanceof ResponseItem) {
                        $items[] = $item;
                    } else {
                        // If an element is not a ResponseItem, wrap it as a text response item.
                        $items[] = new TextResponseItem((string)$item);
                    }
                }
                return new Response(Response::STATUS_SUCCESS, $items);
            }

            // For any other type, wrap it in a text response item.
            return new Response(Response::STATUS_SUCCESS, [new TextResponseItem((string)$result)]);
        } catch (\Throwable $e) {
            // Wrap exceptions as error responses.
            $errorItem = new TextResponseItem("An error occurred: " . $e->getMessage());

            return new Response(Response::STATUS_ERROR, [$errorItem]);
        }
    }

    abstract public function generateFunctionDefinitions(): array;
}
