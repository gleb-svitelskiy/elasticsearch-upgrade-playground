<?php

namespace App;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class ElasticsearchClientFactory
{
    public static function create(array $hosts): Client
    {
        return ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }
}
