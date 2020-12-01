<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Manager\InMemory\AccessTokenManager as InMemoryAccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use PHPUnit\Framework\TestCase;

/**
 * @group time-sensitive
 */
final class InMemoryAccessTokenManagerTest extends TestCase
{
    public function testClearExpired(): void
    {
        $inMemoryAccessTokenManager = new InMemoryAccessTokenManager();

        $testData = $this->buildClearExpiredTestData();

        foreach ($testData['input'] as $token) {
            $inMemoryAccessTokenManager->save($token);
        }

        $this->assertSame(3, $inMemoryAccessTokenManager->clearExpired());

        $reflectionProperty = new \ReflectionProperty(InMemoryAccessTokenManager::class, 'accessTokens');
        $reflectionProperty->setAccessible(true);

        $this->assertSame($testData['output'], $reflectionProperty->getValue($inMemoryAccessTokenManager));
    }

    private function buildClearExpiredTestData(): array
    {
        $validAccessTokens = [
            '1111' => $this->buildAccessToken('1111', '+1 day'),
            '2222' => $this->buildAccessToken('2222', '+1 hour'),
            '3333' => $this->buildAccessToken('3333', '+5 seconds'),
        ];

        $expiredAccessTokens = [
            '5555' => $this->buildAccessToken('5555', '-1 day'),
            '6666' => $this->buildAccessToken('6666', '-1 hour'),
            '7777' => $this->buildAccessToken('7777', '-1 second'),
        ];

        return [
            'input' => $validAccessTokens + $expiredAccessTokens,
            'output' => $validAccessTokens,
        ];
    }

    private function buildAccessToken(string $identifier, string $modify): AccessToken
    {
        return new AccessToken(
            $identifier,
            new \DateTimeImmutable($modify),
            new Client('client', 'secret'),
            null,
            []
        );
    }
}
