<?php

namespace App\Console\Commands;

use App\Tools\TodoCrudTool;
use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;

class TodoCrudAi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:todo-ai';

    /**
     * The console command description.
     *
     * @var string
     */
    protected              $description = 'Generate a CRUD for the Todo model using AI';
    protected TodoCrudTool $todoCrudTool;

    /**
     * Execute the console command.
     */
    public function handle(TodoCrudTool $todoCrudTool)
    {
        $this->todoCrudTool = $todoCrudTool;

        $systemPrompt = str_replace(
            ["__TOOLS_INFO__", "__TOOLS_SCHEMA__"],
            [$this->todoCrudTool->info, $this->todoCrudTool->schema],
            config('prompts.todo-ai.prompt')
        );

        while (true) {
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];
            $input = $this->ask('🗣');
            if ( in_array($input, ['exit', 'quit', 'q']) ) {
                $this->info('Exiting...');
                break;
            }

            $messages[] = ['role' => 'user', 'content' => $input];
            $this->autoPrompting($messages);
        }
    }

    public function autoPrompting(array $messages): void
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
                $args     = $action['input'] ?? null;

                if ( !method_exists($this->todoCrudTool, $function) ) {
                    $observation = "Error: Function '$function' not found.";
                } else {
                    $observation = $this->todoCrudTool->$function($args);
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
}
