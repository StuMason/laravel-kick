<?php

namespace StuMason\Kick\Services;

class PiiScrubber
{
    /**
     * @var array<string, string>
     */
    protected array $patterns;

    protected bool $enabled;

    protected string $replacement;

    public function __construct()
    {
        $this->enabled = config('kick.scrubber.enabled', true);
        $this->replacement = config('kick.scrubber.replacement', '[REDACTED]');
        $this->patterns = $this->getDefaultPatterns();

        // Merge custom patterns from config
        $customPatterns = config('kick.scrubber.patterns', []);
        $this->patterns = array_merge($this->patterns, $customPatterns);
    }

    /**
     * Scrub PII from a string.
     */
    public function scrub(string $content): string
    {
        if (! $this->enabled) {
            return $content;
        }

        foreach ($this->patterns as $name => $pattern) {
            $content = preg_replace($pattern, $this->replacementFor($name), $content) ?? $content;
        }

        return $content;
    }

    /**
     * Scrub PII from an array of strings.
     *
     * @param  array<string>  $lines
     * @return array<string>
     */
    public function scrubLines(array $lines): array
    {
        if (! $this->enabled) {
            return $lines;
        }

        return array_map(fn (string $line) => $this->scrub($line), $lines);
    }

    /**
     * Check if scrubbing is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get the replacement string for a pattern type.
     */
    protected function replacementFor(string $name): string
    {
        return match ($name) {
            'email' => '[EMAIL]',
            'ipv4' => '[IP]',
            'ipv6' => '[IP]',
            'phone' => '[PHONE]',
            'credit_card' => '[CARD]',
            'ssn' => '[SSN]',
            'api_key' => '[API_KEY]',
            'bearer_token' => 'Bearer [TOKEN]',
            'password_field' => '$1[REDACTED]',
            'jwt' => '[JWT]',
            'uuid' => '[UUID]',
            default => $this->replacement,
        };
    }

    /**
     * Get the default PII patterns.
     *
     * @return array<string, string>
     */
    protected function getDefaultPatterns(): array
    {
        return [
            // Email addresses
            'email' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',

            // IPv4 addresses
            'ipv4' => '/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/',

            // IPv6 addresses (simplified)
            'ipv6' => '/\b(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}\b/',

            // Phone numbers (various formats)
            'phone' => '/\b(?:\+?1[-.\s]?)?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}\b/',

            // Credit card numbers (basic pattern)
            'credit_card' => '/\b(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})\b/',

            // SSN (US Social Security Numbers)
            'ssn' => '/\b[0-9]{3}-[0-9]{2}-[0-9]{4}\b/',

            // API keys (common patterns)
            'api_key' => '/\b(?:api[_-]?key|apikey|api[_-]?secret)["\']?\s*[:=]\s*["\']?([a-zA-Z0-9_-]{20,})["\']?/i',

            // Bearer tokens
            'bearer_token' => '/Bearer\s+[a-zA-Z0-9_-]+\.?[a-zA-Z0-9_-]*\.?[a-zA-Z0-9_-]*/i',

            // Password fields in logs (key=value or "key": "value" patterns)
            'password_field' => '/(password|passwd|pwd|secret|token)["\']?\s*[:=]\s*["\']?[^"\'&\s]+/i',

            // JWT tokens
            'jwt' => '/eyJ[a-zA-Z0-9_-]*\.eyJ[a-zA-Z0-9_-]*\.[a-zA-Z0-9_-]*/i',
        ];
    }
}
