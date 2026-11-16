<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\Bundle\OAuth2ServerBundle\Manager\InMemory\AccessTokenManager as InMemoryAccessTokenManager;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class InMemoryAccessTokenManagerTest extends TestCase
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
        $inMemoryAccessTokenManager = new InMemoryAccessTokenManager(true);

        $testData = $this->buildClearExpiredTestData();

        foreach ($testData['input'] as $token) {
            $inMemoryAccessTokenManager->save($token);
        }

        $this->assertSame(3, $inMemoryAccessTokenManager->clearExpiredTokens());

        $reflectionProperty = new \ReflectionProperty(InMemoryAccessTokenManager::class, 'accessTokens');

        $this->assertSame($testData['output'], $reflectionProperty->getValue($inMemoryAccessTokenManager));
    }

    public function testClearExpiredTokensWithoutSavingAccessToken(): void
    {
        $inMemoryAccessTokenManager = new InMemoryAccessTokenManager(false);

        $testData = $this->buildClearExpiredTestData();

        foreach ($testData['input'] as $token) {
            $inMemoryAccessTokenManager->save($token);
        }

        $this->assertSame(0, $inMemoryAccessTokenManager->clearExpiredTokens());

        $reflectionProperty = new \ReflectionProperty(InMemoryAccessTokenManager::class, 'accessTokens');

        $this->assertSame([], $reflectionProperty->getValue($inMemoryAccessTokenManager));
    }

    public function testClearExpiredTokensWithCleanupDelay(): void
    {
        $inMemoryAccessTokenManager = new InMemoryAccessTokenManager(true);

        $testData = $this->buildClearExpiredTestData();

        foreach ($testData['input'] as $token) {
            $inMemoryAccessTokenManager->save($token);
        }

        $this->assertSame(2, $inMemoryAccessTokenManager->clearExpiredTokens(new \DateInterval('PT30M')));
    }

    #[IgnoreDeprecations]
    public function testLegacyClearExpired(): void
    {
        $inMemoryAccessTokenManager = new InMemoryAccessTokenManager(true);

        $testData = $this->buildClearExpiredTestData();

        foreach ($testData['input'] as $token) {
            $inMemoryAccessTokenManager->save($token);
        }

        $this->expectUserDeprecationMessage('Since league/oauth2-server-bundle 2.1: Method "League\Bundle\OAuth2ServerBundle\Manager\InMemory\AccessTokenManager::clearExpired()" is deprecated, use "clearExpiredTokens()" instead.');
        $this->assertSame(3, $inMemoryAccessTokenManager->clearExpired());

        $reflectionProperty = new \ReflectionProperty(InMemoryAccessTokenManager::class, 'accessTokens');

        $this->assertSame($testData['output'], $reflectionProperty->getValue($inMemoryAccessTokenManager));
    }

    #[IgnoreDeprecations]
    public function testLegacyClearExpiredWithoutSavingAccessToken(): void
    {
        $inMemoryAccessTokenManager = new InMemoryAccessTokenManager(false);

        $testData = $this->buildClearExpiredTestData();

        foreach ($testData['input'] as $token) {
            $inMemoryAccessTokenManager->save($token);
        }

        $this->expectUserDeprecationMessage('Since league/oauth2-server-bundle 2.1: Method "League\Bundle\OAuth2ServerBundle\Manager\InMemory\AccessTokenManager::clearExpired()" is deprecated, use "clearExpiredTokens()" instead.');
        $this->assertSame(0, $inMemoryAccessTokenManager->clearExpired());

        $reflectionProperty = new \ReflectionProperty(InMemoryAccessTokenManager::class, 'accessTokens');

        $this->assertSame([], $reflectionProperty->getValue($inMemoryAccessTokenManager));
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
            $this->clock->now()->modify($modify),
            new Client('name', 'identifier', 'secret'),
            null,
            []
        );
    }
}
