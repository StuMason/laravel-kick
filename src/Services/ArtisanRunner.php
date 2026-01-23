<?php

namespace StuMason\Kick\Services;

use Exception;
use Illuminate\Support\Facades\Artisan;
use StuMason\Kick\Exceptions\CommandNotAllowedException;

class ArtisanRunner
{
    /**
     * @var array<string>
     */
    protected array $allowedCommands;

    public function __construct()
    {
        $this->allowedCommands = config('kick.allowed_commands', []);
    }

    /**
     * Get list of allowed commands with their descriptions.
     *
     * @return array<int, array{name: string, description: string}>
     */
    public function listCommands(): array
    {
        $allCommands = Artisan::all();

        return collect($this->allowedCommands)
            ->filter(fn ($command) => isset($allCommands[$command]))
            ->map(fn ($command) => [
                'name' => $command,
                'description' => $allCommands[$command]->getDescription(),
            ])
            ->values()
            ->all();
    }

    /**
     * Check if a command is allowed.
     */
    public function isAllowed(string $command): bool
    {
        // Extract base command (without arguments)
        $baseCommand = $this->extractBaseCommand($command);

        return in_array($baseCommand, $this->allowedCommands, true);
    }

    /**
     * Run an artisan command.
     *
     * @param  array<string, mixed>  $parameters
     * @return array{success: bool, output: string, exit_code: int}
     *
     * @throws CommandNotAllowedException
     */
    public function run(string $command, array $parameters = []): array
    {
        $baseCommand = $this->extractBaseCommand($command);

        if (! $this->isAllowed($baseCommand)) {
            throw new CommandNotAllowedException($baseCommand);
        }

        try {
            $exitCode = Artisan::call($baseCommand, $parameters);
            $output = Artisan::output();

            return [
                'success' => $exitCode === 0,
                'output' => trim($output),
                'exit_code' => $exitCode,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'output' => 'Command execution failed: '.$e->getMessage(),
                'exit_code' => 1,
            ];
        }
    }

    /**
     * Parse a command string into command and parameters.
     *
     * @return array{command: string, parameters: array<string, mixed>}
     */
    public function parseCommand(string $input): array
    {
        $input = trim($input);

        // Simple tokenization - split on whitespace but respect quotes
        $tokens = $this->tokenize($input);

        if (empty($tokens)) {
            return ['command' => '', 'parameters' => []];
        }

        $command = array_shift($tokens);
        $parameters = $this->parseParameters($tokens);

        return [
            'command' => $command,
            'parameters' => $parameters,
        ];
    }

    /**
     * Extract base command name from a command string.
     */
    protected function extractBaseCommand(string $command): string
    {
        // Remove any leading/trailing whitespace
        $command = trim($command);

        // Split on first space to get just the command name
        $parts = explode(' ', $command, 2);

        return $parts[0];
    }

    /**
     * Tokenize a command string, respecting quotes.
     *
     * @return array<string>
     */
    protected function tokenize(string $input): array
    {
        $tokens = [];
        $current = '';
        $inQuote = false;
        $quoteChar = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];

            if (! $inQuote && ($char === '"' || $char === "'")) {
                $inQuote = true;
                $quoteChar = $char;

                continue;
            }

            if ($inQuote && $char === $quoteChar) {
                $inQuote = false;
                $quoteChar = '';

                continue;
            }

            if (! $inQuote && $char === ' ') {
                if ($current !== '') {
                    $tokens[] = $current;
                    $current = '';
                }

                continue;
            }

            $current .= $char;
        }

        if ($current !== '') {
            $tokens[] = $current;
        }

        return $tokens;
    }

    /**
     * Parse tokens into artisan parameters array.
     *
     * @param  array<string>  $tokens
     * @return array<string, mixed>
     */
    protected function parseParameters(array $tokens): array
    {
        $parameters = [];
        $arguments = [];

        foreach ($tokens as $token) {
            // Long option with value: --option=value
            if (str_starts_with($token, '--') && str_contains($token, '=')) {
                [$key, $value] = explode('=', substr($token, 2), 2);
                $parameters['--'.$key] = $value;

                continue;
            }

            // Long option flag: --option
            if (str_starts_with($token, '--')) {
                $parameters[$token] = true;

                continue;
            }

            // Short option: -v, -vvv
            if (str_starts_with($token, '-') && strlen($token) > 1) {
                $parameters[$token] = true;

                continue;
            }

            // Positional argument
            $arguments[] = $token;
        }

        // Add positional arguments if any
        if (! empty($arguments)) {
            // Artisan typically uses numeric keys or specific argument names
            // We'll pass them as an indexed array that can be spread
            foreach ($arguments as $index => $arg) {
                $parameters[$index] = $arg;
            }
        }

        return $parameters;
    }
}
