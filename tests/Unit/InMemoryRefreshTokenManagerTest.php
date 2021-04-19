<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Manager\InMemory\RefreshTokenManager as InMemoryRefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use PHPUnit\Framework\TestCase;

final class InMemoryRefreshTokenManagerTest extends TestCase
{
    public function testClearExpired(): void
    {
        $inMemoryRefreshTokenManager = new InMemoryRefreshTokenManager();

        $testData = $this->buildClearExpiredTestData();

        foreach ($testData['input'] as $token) {
            $inMemoryRefreshTokenManager->save($token);
        }

        $this->assertSame(3, $inMemoryRefreshTokenManager->clearExpired());

        $reflectionProperty = new \ReflectionProperty(InMemoryRefreshTokenManager::class, 'refreshTokens');
        $reflectionProperty->setAccessible(true);

        $this->assertSame($testData['output'], $reflectionProperty->getValue($inMemoryRefreshTokenManager));
    }

    private function buildClearExpiredTestData(): array
    {
        $validRefreshTokens = [
            '1111' => $this->buildRefreshToken('1111', '+1 day'),
            '2222' => $this->buildRefreshToken('2222', '+1 hour'),
            '3333' => $this->buildRefreshToken('3333', '+5 seconds'),
        ];

        $expiredRefreshTokens = [
            '5555' => $this->buildRefreshToken('5555', '-1 day'),
            '6666' => $this->buildRefreshToken('6666', '-1 hour'),
            '7777' => $this->buildRefreshToken('7777', '-1 second'),
        ];

        return [
            'input' => $validRefreshTokens + $expiredRefreshTokens,
            'output' => $validRefreshTokens,
        ];
    }

    private function buildRefreshToken(string $identifier, string $modify): RefreshToken
    {
        return new RefreshToken(
            $identifier,
            new \DateTimeImmutable($modify),
            new AccessToken(
                $identifier,
                new \DateTimeImmutable('+1 day'),
                new Client('name', 'identifier', 'secret'),
                null,
                []
            )
        );
    }
}
