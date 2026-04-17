<?php

declare(strict_types=1);

namespace App\Elasticsearch;

use Elasticsearch\ConnectionPool\SniffingConnectionPool;
use Elasticsearch\ConnectionPool\StaticConnectionPool;
use Elasticsearch\ConnectionPool\StaticNoPingConnectionPool;

enum ConnectionPoolStrategy: string
{
    case SNIFFING = 'sniffing';
    case STATIC_NO_PING = 'static-no-ping';
    case STATIC_WITH_PING = 'static-with-ping';

    public function toClass(): string
    {
        return match ($this) {
            self::SNIFFING => SniffingConnectionPool::class,
            self::STATIC_NO_PING => StaticNoPingConnectionPool::class,
            self::STATIC_WITH_PING => StaticConnectionPool::class,
        };
    }
}
