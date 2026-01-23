<?php

describe('authentication', function () {
    it('returns 401 without token for list', function () {
        $this->getJson('/kick/artisan')
            ->assertStatus(401);
    });

    it('returns 403 with insufficient scope for list', function () {
        $this->getJson('/kick/artisan', [
            'Authorization' => 'Bearer test-token-limited',
        ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Token does not have required scope: artisan:list']);
    });

    it('returns 403 with insufficient scope for execute', function () {
        config(['kick.tokens' => [
            'test-artisan-list' => ['artisan:list'],
        ]]);

        $this->postJson('/kick/artisan', ['command' => 'about'], [
            'Authorization' => 'Bearer test-artisan-list',
        ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Token does not have required scope: artisan:execute']);
    });
});

describe('list commands endpoint', function () {
    it('returns list of allowed commands', function () {
        $this->getJson('/kick/artisan', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'commands' => [
                    '*' => ['name', 'description'],
                ],
                'count',
            ]);
    });

    it('includes about command', function () {
        $response = $this->getJson('/kick/artisan', [
            'Authorization' => 'Bearer test-token-full',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $commandNames = collect($data['commands'])->pluck('name')->all();
        expect($commandNames)->toContain('about');
    });
});

describe('execute command endpoint', function () {
    it('executes allowed commands', function () {
        $this->postJson('/kick/artisan', [
            'command' => 'about',
        ], [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'command',
                'output',
                'exit_code',
            ])
            ->assertJson([
                'success' => true,
                'command' => 'about',
                'exit_code' => 0,
            ]);
    });

    it('rejects disallowed commands with 403', function () {
        $this->postJson('/kick/artisan', [
            'command' => 'migrate:fresh',
        ], [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'error',
                'allowed_commands',
            ]);
    });

    it('returns 400 when no command provided', function () {
        $this->postJson('/kick/artisan', [], [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'No command provided.',
            ]);
    });

    it('returns 400 for empty command string', function () {
        $this->postJson('/kick/artisan', [
            'command' => '',
        ], [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'No command provided.',
            ]);
    });

    it('executes commands with options', function () {
        $this->postJson('/kick/artisan', [
            'command' => 'about --json',
        ], [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'command' => 'about',
            ]);
    });

    it('returns allowed commands list on 403', function () {
        $response = $this->postJson('/kick/artisan', [
            'command' => 'db:wipe',
        ], [
            'Authorization' => 'Bearer test-token-full',
        ]);

        $response->assertStatus(403);
        $data = $response->json();

        expect($data['allowed_commands'])->toBeArray();
        expect($data['allowed_commands'])->toContain('about');
    });
});
