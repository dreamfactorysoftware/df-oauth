<?php

namespace DreamFactory\Core\OAuth\Tests\Security;

use DreamFactory\Core\OAuth\Services\BaseOAuthService;
use PHPUnit\Framework\TestCase;

/**
 * Security: hostMatchesPattern must be strict; replaces fnmatch which
 * accepts unsafe wildcards.
 */
class HostMatchTest extends TestCase
{
    /** @dataProvider matchProvider */
    public function testMatch(string $host, string $pattern, bool $expected): void
    {
        $this->assertSame($expected, BaseOAuthService::hostMatchesPattern($host, $pattern));
    }

    public static function matchProvider(): array
    {
        return [
            // Exact host
            ['example.com', 'example.com', true],
            ['EXAMPLE.com', 'example.com', true],
            ['evil.com', 'example.com', false],

            // Leading-wildcard subdomain
            ['app.example.com', '*.example.com', true],
            ['admin.example.com', '*.example.com', true],
            ['example.com', '*.example.com', false],          // bare host doesn't match *.host
            ['evil.com', '*.example.com', false],
            // The fnmatch bypass: pattern as a glob would let
            // `evil.example.com.attacker.com` match `*.example.com`.
            // Our strict matcher must reject it.
            ['evil.example.com.attacker.com', '*.example.com', false],

            // Leading-wildcard does not let a host suffix match different-domain
            ['attacker-example.com', '*.example.com', false],

            // Empty pattern is rejected
            ['anything.com', '', false],
        ];
    }
}
