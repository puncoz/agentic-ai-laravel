<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;

class WeatherAi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:weather-ai';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agentic AI to provide weather information of a city';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tools = [
            'getWeatherDetails' => 'Get weather details of a city',
        ];

        $messages = [
            [
                'role'    => 'system',
                'content' => config('prompts.weather-ai.prompt'),
            ],
        ];

        while (true) {
            $input = $this->ask('🗣');
            if ( in_array($input, ['exit', 'quit', 'q']) ) {
                $this->info('Exiting...');
                break;
            }

            $messages[] = ['role' => 'user', 'content' => $input];
            $this->autoPrompting($messages, $tools);
        }
    }

    public function autoPrompting(array $messages, array $tools): void
    {
        while (true) {
            $response = OpenAI::chat()->create([
                'model'           => 'gpt-4o',
                'messages'        => $messages,
                'response_format' => [
                    'type' => 'json_object',
                ],
            ]);

            $result     = $response->choices[0]->message->content;
            $messages[] = ['role' => 'assistant', 'content' => $result];
            $this->info("AI:".trim($result));
            $action = json_decode($result, true);

            if ( $action['type'] == 'output' ) {
                $this->line("🤖: ".trim($action['output']));
                break;
            }

            if ( $action['type'] === 'action' ) {
                $function = $action['function'];
                $args     = $action['input'];

                if ( !array_key_exists($function, $tools) ) {
                    $observation = "Error: Function '$function' not found.";
                } else {
                    $observation = $this->$function($args);
                }


                $messages[] = [
                    'role'    => 'developer',
                    'content' => json_encode([
                        'type'        => 'observation',
                        'observation' => $observation,
                    ], JSON_THROW_ON_ERROR),
                ];
            }
        }
    }

    public function getWeatherDetails(string $city): string
    {
        $weatherData = [
            "Osaka"    => "Cloudy, 22°C",
            "Tokyo"    => "Sunny, 32°C",
            "New York" => "Rainy, 18°C",
        ];

        return $weatherData[$city] ?? "Weather data not available.";
    }
}
