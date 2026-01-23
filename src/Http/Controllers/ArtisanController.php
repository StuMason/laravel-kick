<?php

namespace StuMason\Kick\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StuMason\Kick\Exceptions\CommandNotAllowedException;
use StuMason\Kick\Services\ArtisanRunner;

class ArtisanController
{
    public function __construct(
        protected ArtisanRunner $artisanRunner
    ) {}

    /**
     * List available (whitelisted) artisan commands.
     */
    public function index(): JsonResponse
    {
        $commands = $this->artisanRunner->listCommands();

        return response()->json([
            'commands' => $commands,
            'count' => count($commands),
        ]);
    }

    /**
     * Execute an artisan command.
     */
    public function execute(Request $request): JsonResponse
    {
        $command = $request->input('command', '');

        if (empty($command)) {
            return response()->json([
                'success' => false,
                'error' => 'No command provided.',
            ], 400);
        }

        // Parse the command string
        $parsed = $this->artisanRunner->parseCommand($command);

        if (empty($parsed['command'])) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid command format.',
            ], 400);
        }

        // Check if command is allowed before attempting to run
        if (! $this->artisanRunner->isAllowed($parsed['command'])) {
            return response()->json([
                'success' => false,
                'error' => "Command not allowed: {$parsed['command']}",
                'allowed_commands' => collect($this->artisanRunner->listCommands())->pluck('name')->all(),
            ], 403);
        }

        try {
            $result = $this->artisanRunner->run($parsed['command'], $parsed['parameters']);

            return response()->json([
                'success' => $result['success'],
                'command' => $parsed['command'],
                'output' => $result['output'],
                'exit_code' => $result['exit_code'],
            ], $result['success'] ? 200 : 400);
        } catch (CommandNotAllowedException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 403);
        }
    }
}
