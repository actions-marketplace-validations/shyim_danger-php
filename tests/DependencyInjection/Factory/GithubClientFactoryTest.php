<?php
declare(strict_types=1);

namespace Danger\Tests\DependencyInjection\Factory;

use Danger\DependencyInjection\Factory\GithubClientFactory;
use Github\Exception\RuntimeException;
use Github\HttpClient\Builder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @internal
 */
#[CoversClass(GithubClientFactory::class)]
class GithubClientFactoryTest extends TestCase
{
    public function testRetriesTransientServerErrors(): void
    {
        $mock = new MockHttpClient([
            new MockResponse('<html><title>Unicorn!</title></html>', ['http_code' => 502]),
            new MockResponse('{"login": "shyim"}', ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]),
        ]);

        $client = GithubClientFactory::build(new Builder(new Psr18Client($mock)));

        static::assertSame(['login' => 'shyim'], $client->currentUser()->show());
        static::assertSame(2, $mock->getRequestsCount());
    }

    public function testDoesNotRetryClientErrors(): void
    {
        $mock = new MockHttpClient([
            new MockResponse('{"message": "Not Found"}', ['http_code' => 404, 'response_headers' => ['content-type' => 'application/json']]),
        ]);

        $client = GithubClientFactory::build(new Builder(new Psr18Client($mock)));

        $this->expectException(RuntimeException::class);

        try {
            $client->currentUser()->show();
        } finally {
            static::assertSame(1, $mock->getRequestsCount());
        }
    }
}
