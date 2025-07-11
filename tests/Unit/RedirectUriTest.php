<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use PHPUnit\Framework\TestCase;

final class RedirectUriTest extends TestCase
{
    public function exceptionRedirectUriProvider(): array
    {
        return [
            ['invalid'],
            ['http://invalid url'],
            ['http:/invalid'],
            ['http:/invalid.com'],
            ['http:/invalid.com/test'],
        ];
    }

    /**
     * @dataProvider exceptionRedirectUriProvider
     */
    public function testInvalidRedirectUris($data): void
    {
        $this->expectException(\RuntimeException::class);

        new RedirectUri($data[0]);
    }

    public function testValidRedirectUris(): void
    {
        // Test standard URIs
        $this->assertIsObject(new RedirectUri('http://github.com'));
        $this->assertIsObject(new RedirectUri('http://github.com/test'));
        $this->assertIsObject(new RedirectUri('http://github.com/test?query=test'));

        // Test mobile URIs
        $this->assertIsObject(new RedirectUri('com.my.app:/'));
        $this->assertIsObject(new RedirectUri('com.my.app:/callback'));
        $this->assertIsObject(new RedirectUri('myapp://callback#token=123'));
    }
}
