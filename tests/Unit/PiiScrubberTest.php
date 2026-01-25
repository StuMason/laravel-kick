<?php

use StuMason\Kick\Services\PiiScrubber;

beforeEach(function () {
    config(['kick.scrubber.enabled' => true]);
    config(['kick.scrubber.replacement' => '[REDACTED]']);
    config(['kick.scrubber.patterns' => []]);
});

it('scrubs email addresses', function () {
    $scrubber = new PiiScrubber;

    $input = 'User john.doe@example.com logged in from work';
    $result = $scrubber->scrub($input);

    expect($result)->toBe('User [EMAIL] logged in from work');
});

it('scrubs multiple email addresses', function () {
    $scrubber = new PiiScrubber;

    $input = 'From: admin@example.com To: support@test.org';
    $result = $scrubber->scrub($input);

    expect($result)->toBe('From: [EMAIL] To: [EMAIL]');
});

it('scrubs IPv4 addresses', function () {
    $scrubber = new PiiScrubber;

    $input = 'Connection from 192.168.1.100 to 10.0.0.1';
    $result = $scrubber->scrub($input);

    expect($result)->toBe('Connection from [IP] to [IP]');
});

it('scrubs IPv6 addresses', function () {
    $scrubber = new PiiScrubber;

    $input = 'Request from 2001:0db8:85a3:0000:0000:8a2e:0370:7334';
    $result = $scrubber->scrub($input);

    expect($result)->toBe('Request from [IP]');
});

it('scrubs phone numbers', function () {
    $scrubber = new PiiScrubber;

    $testCases = [
        '555-123-4567' => '[PHONE]',
        '(555) 123-4567' => '[PHONE]',
        '+1-555-123-4567' => '[PHONE]',
        '555.123.4567' => '[PHONE]',
    ];

    foreach ($testCases as $phone => $expected) {
        $result = $scrubber->scrub("Call {$phone} now");
        expect($result)->toContain($expected);
    }
});

it('scrubs credit card numbers', function () {
    $scrubber = new PiiScrubber;

    // Test Visa pattern (4 + 15 digits)
    $result = $scrubber->scrub('Card: 4000000000000000');
    expect($result)->toBe('Card: [CARD]');

    // Test Mastercard pattern (51-55 + 14 digits)
    $result = $scrubber->scrub('Card: 5100000000000000');
    expect($result)->toBe('Card: [CARD]');
});

it('scrubs social security numbers', function () {
    $scrubber = new PiiScrubber;

    $input = 'SSN: 123-45-6789';
    $result = $scrubber->scrub($input);

    expect($result)->toBe('SSN: [SSN]');
});

it('scrubs API keys', function () {
    $scrubber = new PiiScrubber;

    $testCases = [
        'api_key: test_abcdefghijklmnopqrstuvwxyz123456',
        'apikey=abcdefghijklmnopqrstuvwxyz123456',
        "api-secret: 'abcdefghijklmnopqrstuvwxyz123456'",
    ];

    foreach ($testCases as $input) {
        $result = $scrubber->scrub($input);
        expect($result)->toContain('[API_KEY]');
    }
});

it('scrubs bearer tokens', function () {
    $scrubber = new PiiScrubber;

    $input = 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9';
    $result = $scrubber->scrub($input);

    expect($result)->toContain('Bearer [TOKEN]');
});

it('scrubs JWT tokens', function () {
    $scrubber = new PiiScrubber;

    $jwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIn0.Gfx6VO9tcxwk6xqx9yYzSfebfeakZp5JYIgP_edcw_A';
    $input = "Received JWT {$jwt} in request";
    $result = $scrubber->scrub($input);

    expect($result)->toBe('Received JWT [JWT] in request');
});

it('scrubs password fields', function () {
    $scrubber = new PiiScrubber;

    $testCases = [
        'password=secret123',
        'password: mysecretpass',
        "passwd='hidden'",
        'secret=api_key_here',
    ];

    foreach ($testCases as $input) {
        $result = $scrubber->scrub($input);
        expect($result)->toContain('[REDACTED]');
    }
});

it('handles mixed PII in single string', function () {
    $scrubber = new PiiScrubber;

    $input = 'User john@example.com from 192.168.1.1 called 555-123-4567';
    $result = $scrubber->scrub($input);

    expect($result)->toBe('User [EMAIL] from [IP] called [PHONE]');
});

it('returns original content when disabled', function () {
    config(['kick.scrubber.enabled' => false]);
    $scrubber = new PiiScrubber;

    $input = 'User john@example.com logged in';
    $result = $scrubber->scrub($input);

    expect($result)->toBe($input);
});

it('can scrub multiple lines at once', function () {
    $scrubber = new PiiScrubber;

    $lines = [
        'User john@example.com logged in',
        'Connection from 192.168.1.100',
        'Normal log line with no PII',
    ];

    $result = $scrubber->scrubLines($lines);

    expect($result[0])->toBe('User [EMAIL] logged in');
    expect($result[1])->toBe('Connection from [IP]');
    expect($result[2])->toBe('Normal log line with no PII');
});

it('skips scrubbing lines when disabled', function () {
    config(['kick.scrubber.enabled' => false]);
    $scrubber = new PiiScrubber;

    $lines = ['User john@example.com logged in'];
    $result = $scrubber->scrubLines($lines);

    expect($result[0])->toBe('User john@example.com logged in');
});

it('reports enabled status', function () {
    config(['kick.scrubber.enabled' => true]);
    $scrubber = new PiiScrubber;

    expect($scrubber->isEnabled())->toBeTrue();

    config(['kick.scrubber.enabled' => false]);
    $scrubber = new PiiScrubber;

    expect($scrubber->isEnabled())->toBeFalse();
});

it('uses custom patterns from config', function () {
    config(['kick.scrubber.patterns' => [
        'custom_id' => '/CUST-[0-9]{8}/',
    ]]);

    $scrubber = new PiiScrubber;

    $input = 'Customer CUST-12345678 placed order';
    $result = $scrubber->scrub($input);

    expect($result)->toBe('Customer [REDACTED] placed order');
});

it('preserves non-PII content', function () {
    $scrubber = new PiiScrubber;

    $input = '[2024-01-01 12:00:00] production.INFO: Application started successfully';
    $result = $scrubber->scrub($input);

    expect($result)->toBe($input);
});

it('handles empty strings', function () {
    $scrubber = new PiiScrubber;

    expect($scrubber->scrub(''))->toBe('');
});

it('handles exception stack traces with emails', function () {
    $scrubber = new PiiScrubber;

    $input = <<<'TRACE'
    Exception: User not found for email john@example.com
    #0 /app/Services/UserService.php(42): findByEmail('john@example.com')
    #1 /app/Http/Controllers/AuthController.php(28): UserService->authenticate()
    TRACE;

    $result = $scrubber->scrub($input);

    expect($result)->not->toContain('john@example.com');
    expect($result)->toContain('[EMAIL]');
});
