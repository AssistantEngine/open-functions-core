<?php

namespace AssistantEngine\OpenFunctions\Core\Examples;

use AssistantEngine\OpenFunctions\Core\Contracts\AbstractOpenFunction;
use AssistantEngine\OpenFunctions\Core\Helpers\FunctionDefinition;
use AssistantEngine\OpenFunctions\Core\Helpers\Parameter;
use AssistantEngine\OpenFunctions\Core\Models\Responses\TextResponseItem;

class WeatherOpenFunction extends AbstractOpenFunction
{
    /**
     * Generate function definitions for the Weather open function.
     *
     * This method defines:
     * - getWeather: returns current weather for a given city.
     * - getForecast: returns a weather forecast for a given city for a number of days.
     *
     * @return array
     */
    public function generateFunctionDefinitions(): array
    {
        $definitions = [];

        // Definition for getWeather
        $defGetWeather = new FunctionDefinition(
            'getWeather',
            'Retrieves the current weather for a given city.'
        );
        $defGetWeather->addParameter(
            Parameter::string("cityName")
                ->description("The name of the city to get the weather for.")
                ->required()
        );
        $definitions[] = $defGetWeather->createFunctionDescription();

        // Definition for getForecast
        $defGetForecast = new FunctionDefinition(
            'getForecast',
            'Retrieves the weather forecast for a given city for the upcoming days.'
        );
        $defGetForecast->addParameter(
            Parameter::string("cityName")
                ->description("The name of the city to get the forecast for.")
                ->required()
        );
        $defGetForecast->addParameter(
            Parameter::number("days")
                ->description("The number of days to forecast.")
                ->required()
        );
        $definitions[] = $defGetForecast->createFunctionDescription();

        return $definitions;
    }

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

    /**
     * Returns a forecast for the given city for the upcoming days.
     *
     * @param string $cityName
     * @param float  $days
     * @return TextResponseItem
     */
    public function getForecast(string $cityName, float $days)
    {
        $weathers = ['sunny', 'rainy', 'cloudy', 'stormy', 'snowy', 'windy'];
        $numDays = (int)$days;
        $forecast = [];

        for ($i = 1; $i <= $numDays; $i++) {
            $weather = $weathers[array_rand($weathers)];
            $forecast[] = "Day {$i}: {$weather}";
        }

        $forecastStr = implode(", ", $forecast);
        return new TextResponseItem("The forecast for {$cityName} for the next {$numDays} days: {$forecastStr}.");
    }
}