<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Manager\InMemory\RefreshTokenManager as InMemoryRefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class InMemoryRefreshTokenManagerTest extends TestCase
{
    use ClockSensitiveTrait;

    private ClockInterface $clock;

    #[Before]
    protected function setMockClock(): void
    {
        $this->clock = static::mockTime(true);
    }

    public function testClearExpiredTokens(): void
    {
        $inMemoryRefreshTokenManager = new InMemoryRefreshTokenManager();

        $testData = $this->buildClearExpiredTestData();

        foreach ($testData['input'] as $token) {
            $inMemoryRefreshTokenManager->save($token);
        }

        $this->assertSame(3, $inMemoryRefreshTokenManager->clearExpiredTokens());

        $reflectionProperty = new \ReflectionProperty(InMemoryRefreshTokenManager::class, 'refreshTokens');

        $this->assertSame($testData['output'], $reflectionProperty->getValue($inMemoryRefreshTokenManager));
    }

    public function testClearExpiredTokensWithCleanupDelay(): void
    {
        $inMemoryRefreshTokenManager = new InMemoryRefreshTokenManager();

        $testData = $this->buildClearExpiredTestData();

        foreach ($testData['input'] as $token) {
            $inMemoryRefreshTokenManager->save($token);
        }

        $this->assertSame(2, $inMemoryRefreshTokenManager->clearExpiredTokens(new \DateInterval('PT30M')));
    }

    #[IgnoreDeprecations]
    public function testLegacyClearExpired(): void
    {
        $inMemoryRefreshTokenManager = new InMemoryRefreshTokenManager();

        $testData = $this->buildClearExpiredTestData();

        foreach ($testData['input'] as $token) {
            $inMemoryRefreshTokenManager->save($token);
        }

        $this->expectUserDeprecationMessage('Since league/oauth2-server-bundle 2.1: Method "League\Bundle\OAuth2ServerBundle\Manager\InMemory\RefreshTokenManager::clearExpired()" is deprecated, use "clearExpiredTokens()" instead.');
        $this->assertSame(3, $inMemoryRefreshTokenManager->clearExpired());

        $reflectionProperty = new \ReflectionProperty(InMemoryRefreshTokenManager::class, 'refreshTokens');

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
            $this->clock->now()->modify($modify),
            new AccessToken(
                $identifier,
                $this->clock->now()->modify('+1 day'),
                new Client('name', 'identifier', 'secret'),
                null,
                []
            )
        );
    }
}
