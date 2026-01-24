<?php

namespace StuMason\Kick\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use StuMason\Kick\Exceptions\CommandNotAllowedException;
use StuMason\Kick\Services\ArtisanRunner;

#[IsDestructive]
class ArtisanRunTool extends Tool
{
    protected string $name = 'kick_artisan_run';

    protected string $description = 'Execute a whitelisted artisan command. Use kick_artisan_list to see available commands. The whitelist is configured in config/kick.php.';

    public function __construct(
        protected ArtisanRunner $artisanRunner,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'command' => 'required|string|max:255',
            'arguments' => 'nullable|array',
        ], [
            'command.required' => 'You must specify a command to run. Use kick_artisan_list to see available commands.',
        ]);

        $command = $validated['command'];
        $arguments = $validated['arguments'] ?? [];

        try {
            $result = $this->artisanRunner->run($command, $arguments);

            $summary = sprintf(
                "Command: php artisan %s\nExit Code: %d\n\nOutput:\n%s",
                $command,
                $result['exit_code'],
                $result['output'] ?: '(no output)'
            );

            return Response::make(
                Response::text($summary)
            )->withStructuredContent($result);

        } catch (CommandNotAllowedException $e) {
            $allowedCommands = $this->artisanRunner->listCommands();
            $commandNames = array_column($allowedCommands, 'name');

            return Response::error(sprintf(
                "Command '%s' is not allowed. Available commands: %s",
                $command,
                implode(', ', $commandNames)
            ));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'command' => $schema->string()
                ->description('The artisan command to run (e.g., "cache:clear", "config:cache")')
                ->required(),

            'arguments' => $schema->object()
                ->description('Optional arguments/options for the command as key-value pairs'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'success' => $schema->boolean()->description('Whether the command executed successfully')->required(),
            'command' => $schema->string()->description('The command that was run')->required(),
            'output' => $schema->string()->description('Command output'),
            'exit_code' => $schema->integer()->description('Command exit code (0 = success)')->required(),
        ];
    }
}
