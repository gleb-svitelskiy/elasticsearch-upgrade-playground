<?php

declare(strict_types=1);

namespace App\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

final class ClientFactory
{
    public static function create(array $hosts, ConnectionPoolStrategy $connectionPoolStrategy): Client
    {
        return ClientBuilder::create()
            ->setConnectionPool($connectionPoolStrategy->toClass())
            ->setHosts($hosts)
            ->build();
    }
}
