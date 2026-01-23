<?php

use StuMason\Kick\Exceptions\CommandNotAllowedException;
use StuMason\Kick\Services\ArtisanRunner;

beforeEach(function () {
    config(['kick.allowed_commands' => [
        'about',
        'route:list',
        'cache:clear',
    ]]);
    $this->artisanRunner = new ArtisanRunner;
});

describe('isAllowed', function () {
    it('returns true for allowed commands', function () {
        expect($this->artisanRunner->isAllowed('about'))->toBeTrue();
        expect($this->artisanRunner->isAllowed('route:list'))->toBeTrue();
        expect($this->artisanRunner->isAllowed('cache:clear'))->toBeTrue();
    });

    it('returns false for disallowed commands', function () {
        expect($this->artisanRunner->isAllowed('migrate:fresh'))->toBeFalse();
        expect($this->artisanRunner->isAllowed('db:wipe'))->toBeFalse();
        expect($this->artisanRunner->isAllowed('tinker'))->toBeFalse();
    });

    it('extracts base command from full command string', function () {
        expect($this->artisanRunner->isAllowed('route:list --path=/api'))->toBeTrue();
        expect($this->artisanRunner->isAllowed('cache:clear --store=redis'))->toBeTrue();
    });
});

describe('listCommands', function () {
    it('returns list of allowed commands with descriptions', function () {
        $result = $this->artisanRunner->listCommands();

        expect($result)->toBeArray();
        // 'about' should always exist
        $aboutCommand = collect($result)->firstWhere('name', 'about');
        expect($aboutCommand)->not->toBeNull();
        expect($aboutCommand)->toHaveKeys(['name', 'description']);
    });

    it('only includes commands that exist', function () {
        config(['kick.allowed_commands' => ['about', 'nonexistent:command']]);
        $runner = new ArtisanRunner;

        $result = $runner->listCommands();

        $names = collect($result)->pluck('name')->all();
        expect($names)->toContain('about');
        expect($names)->not->toContain('nonexistent:command');
    });
});

describe('parseCommand', function () {
    it('parses simple command', function () {
        $result = $this->artisanRunner->parseCommand('about');

        expect($result['command'])->toBe('about');
        expect($result['parameters'])->toBeEmpty();
    });

    it('parses command with long options', function () {
        $result = $this->artisanRunner->parseCommand('route:list --path=/api');

        expect($result['command'])->toBe('route:list');
        expect($result['parameters'])->toHaveKey('--path');
        expect($result['parameters']['--path'])->toBe('/api');
    });

    it('parses command with flag options', function () {
        $result = $this->artisanRunner->parseCommand('route:list --json');

        expect($result['command'])->toBe('route:list');
        expect($result['parameters'])->toHaveKey('--json');
        expect($result['parameters']['--json'])->toBeTrue();
    });

    it('parses command with quoted values', function () {
        $result = $this->artisanRunner->parseCommand('cache:clear --store="my store"');

        expect($result['command'])->toBe('cache:clear');
        expect($result['parameters']['--store'])->toBe('my store');
    });

    it('handles empty input', function () {
        $result = $this->artisanRunner->parseCommand('');

        expect($result['command'])->toBe('');
        expect($result['parameters'])->toBeEmpty();
    });
});

describe('run', function () {
    it('executes allowed commands successfully', function () {
        $result = $this->artisanRunner->run('about');

        expect($result)->toHaveKeys(['success', 'output', 'exit_code']);
        expect($result['success'])->toBeTrue();
        expect($result['exit_code'])->toBe(0);
    });

    it('throws exception for disallowed commands', function () {
        expect(fn () => $this->artisanRunner->run('migrate:fresh'))
            ->toThrow(CommandNotAllowedException::class);
    });

    it('accepts parameters array', function () {
        $result = $this->artisanRunner->run('about', ['--json' => true]);

        expect($result['success'])->toBeTrue();
    });
});
