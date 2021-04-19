<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Model\Client;
use PHPUnit\Framework\TestCase;

final class ClientEntityTest extends TestCase
{
    /**
     * @dataProvider confidentialDataProvider
     */
    public function testClientConfidentiality(?string $secret, bool $isConfidential): void
    {
        $client = new Client('name', 'identifier', $secret);

        $this->assertSame($isConfidential, $client->isConfidential());
    }

    public function confidentialDataProvider(): iterable
    {
        return [
            'Client with null secret is not confidential' => [null, false],
            'Client with empty secret is not confidential' => ['', false],
            'Client with non empty secret is confidential' => ['f', true],
        ];
    }
}
