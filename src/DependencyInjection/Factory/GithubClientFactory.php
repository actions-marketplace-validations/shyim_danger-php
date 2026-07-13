<?php
declare(strict_types=1);

namespace Danger\DependencyInjection\Factory;

use Github\AuthMethod;
use Github\Client;
use Github\HttpClient\Builder;
use Http\Client\Common\Plugin\RetryPlugin;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

class GithubClientFactory
{
    public static function build(?Builder $httpClientBuilder = null): Client
    {
        $builder = $httpClientBuilder ?? new Builder();
        $builder->addPlugin(new RetryPlugin([
            'retries' => 3,
            'exception_decider' => static function (RequestInterface $request, ClientExceptionInterface $exception): bool {
                // GithubExceptionThrower maps error responses to exceptions carrying the HTTP
                // status code, so retry only transient failures: network errors and 5xx
                // responses. Client errors (4xx, e.g. rate limits) bubble up immediately.
                return $exception instanceof NetworkExceptionInterface
                    || ($exception->getCode() >= 500 && $exception->getCode() < 600);
            },
        ]));

        $client = new Client($builder);

        if (isset($_SERVER['GITHUB_TOKEN']) && \is_string($_SERVER['GITHUB_TOKEN'])) {
            $client->authenticate($_SERVER['GITHUB_TOKEN'], null, AuthMethod::ACCESS_TOKEN);
        }

        return $client;
    }
}
