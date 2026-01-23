<?php

namespace StuMason\Kick\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use StuMason\Kick\Services\ArtisanRunner;

#[IsReadOnly]
#[IsIdempotent]
class ArtisanListTool extends Tool
{
    protected string $name = 'kick_artisan_list';

    protected string $description = 'List all whitelisted artisan commands that can be executed through Kick. Only these commands are allowed for security.';

    public function __construct(
        protected ArtisanRunner $artisanRunner,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $commands = $this->artisanRunner->listCommands();

        $summary = sprintf("Available Artisan Commands (%d):\n\n", count($commands));

        foreach ($commands as $command) {
            $summary .= sprintf("- %s\n  %s\n\n", $command['name'], $command['description']);
        }

        return Response::make(
            Response::text($summary)
        )->withStructuredContent([
            'commands' => $commands,
            'count' => count($commands),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'commands' => $schema->array()->description('List of allowed commands with name and description')->required(),
            'count' => $schema->integer()->description('Total number of allowed commands')->required(),
        ];
    }
}
