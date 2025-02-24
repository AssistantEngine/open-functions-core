<?php

namespace AssistantEngine\OpenFunctions\Core\Examples;

use AssistantEngine\OpenFunctions\Core\Contracts\AbstractOpenFunction;
use AssistantEngine\OpenFunctions\Core\Helpers\FunctionDefinition;
use AssistantEngine\OpenFunctions\Core\Helpers\Parameter;
use AssistantEngine\OpenFunctions\Core\Models\Responses\TextResponseItem;

class DeliveryOpenFunction extends AbstractOpenFunction
{
    /**
     * @var array List of available delivery products.
     */
    private array $products;

    /**
     * Constructor initializes available products.
     */
    public function __construct(array $products)
    {
        // Initialize available products.
        $this->products = $products;
    }

    /**
     * Generate function definitions for the Delivery service.
     *
     * This method defines:
     * - listProducts: lists all available products.
     * - orderProduct: places an order for a product.
     * - getOrderTime: provides the estimated delivery time for an order.
     * - getOpenOrders: returns a list of open (pending) orders.
     * - cancelOrder: cancels an open order.
     * - updateOrder: updates the quantity for an existing order.
     *
     * @return array
     */
    public function generateFunctionDefinitions(): array
    {
        $definitions = [];

        // Definition for listProducts
        $defListProducts = new FunctionDefinition(
            'listProducts',
            'Lists all available delivery products.'
        );
        $definitions[] = $defListProducts->createFunctionDescription();

        // Definition for orderProduct
        $defOrderProduct = new FunctionDefinition(
            'orderProduct',
            'Places an order for a delivery product.'
        );
        $defOrderProduct->addParameter(
            Parameter::string("productName")
                ->description("The name of the product to order. Must be one of the available products.")
                ->required()
        );
        $defOrderProduct->addParameter(
            Parameter::number("quantity")
                ->description("The quantity of the product to order.")
                ->required()
        );
        $definitions[] = $defOrderProduct->createFunctionDescription();

        // Definition for getOrderTime
        $defGetOrderTime = new FunctionDefinition(
            'getOrderTime',
            'Provides the estimated delivery time for the given order.'
        );
        $defGetOrderTime->addParameter(
            Parameter::string("orderId")
                ->description("The unique identifier for the order.")
                ->required()
        );
        $definitions[] = $defGetOrderTime->createFunctionDescription();

        // Definition for getOpenOrders
        $defGetOpenOrders = new FunctionDefinition(
            'getOpenOrders',
            'Lists all open (pending) orders.'
        );
        $definitions[] = $defGetOpenOrders->createFunctionDescription();

        // Definition for cancelOrder
        $defCancelOrder = new FunctionDefinition(
            'cancelOrder',
            'Cancels an open order.'
        );
        $defCancelOrder->addParameter(
            Parameter::string("orderId")
                ->description("The unique identifier for the order to cancel.")
                ->required()
        );
        $definitions[] = $defCancelOrder->createFunctionDescription();

        // Definition for updateOrder
        $defUpdateOrder = new FunctionDefinition(
            'updateOrder',
            'Updates the quantity for an existing open order.'
        );
        $defUpdateOrder->addParameter(
            Parameter::string("orderId")
                ->description("The unique identifier for the order to update.")
                ->required()
        );
        $defUpdateOrder->addParameter(
            Parameter::number("quantity")
                ->description("The new quantity for the order.")
                ->required()
        );
        $definitions[] = $defUpdateOrder->createFunctionDescription();

        return $definitions;
    }

    /**
     * Lists all available delivery products.
     *
     * @return TextResponseItem
     */
    public function listProducts()
    {
        $productList = implode(", ", $this->products);
        return new TextResponseItem("Available products: {$productList}.");
    }

    /**
     * Places an order for a delivery product.
     *
     * @param string $productName The product name.
     * @param int    $quantity    The quantity to order.
     * @return TextResponseItem
     */
    public function orderProduct(string $productName, int $quantity)
    {
        // Verify that the product exists.
        if (!in_array($productName, $this->products)) {
            return new TextResponseItem(
                "Product '{$productName}' is not available. Please choose from: " . implode(", ", $this->products) . "."
            );
        }

        // Generate a unique order ID and simulate an estimated delivery time.
        $orderId = uniqid("order_");
        $estimatedTime = rand(20, 40);

        return new TextResponseItem(
            "Order placed for {$quantity} x {$productName}. Your order ID is {$orderId} and estimated delivery time is {$estimatedTime} minutes."
        );
    }

    /**
     * Provides the estimated delivery time for the given order.
     *
     * @param string $orderId The order ID.
     * @return TextResponseItem
     */
    public function getOrderTime(string $orderId)
    {
        // Simulate retrieving an estimated delivery time.
        $estimatedTime = rand(20, 40);
        return new TextResponseItem(
            "Order {$orderId} will be delivered in approximately {$estimatedTime} minutes."
        );
    }

    /**
     * Lists all open (pending) orders.
     *
     * @return TextResponseItem
     */
    public function getOpenOrders()
    {
        // Simulate a random number of open orders.
        $numOrders = rand(1, 3);
        $fakeOrders = [];

        for ($i = 0; $i < $numOrders; $i++) {
            $orderId = uniqid("order_");
            // Pick a random product from the available list.
            $productName = $this->products[array_rand($this->products)];
            $quantity = rand(1, 3);
            $fakeOrders[] = "OrderID: {$orderId} ({$quantity} x {$productName})";
        }

        $ordersStr = implode("; ", $fakeOrders);
        return new TextResponseItem("Open orders: {$ordersStr}.");
    }

    /**
     * Cancels an open order.
     *
     * @param string $orderId The order ID.
     * @return TextResponseItem
     */
    public function cancelOrder(string $orderId)
    {
        return new TextResponseItem("Order {$orderId} has been cancelled (simulation).");
    }

    /**
     * Updates the quantity for an existing open order.
     *
     * @param string $orderId  The order ID.
     * @param int    $quantity The new quantity.
     * @return TextResponseItem
     */
    public function updateOrder(string $orderId, int $quantity)
    {
        return new TextResponseItem("Order {$orderId} has been updated to quantity {$quantity} (simulation).");
    }
}