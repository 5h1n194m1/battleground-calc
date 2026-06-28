<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * @var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        'csrf'          => \App\Filters\GracefulCsrfFilter::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,

        'session'       => \CodeIgniter\Shield\Filters\SessionAuth::class,
        'group'         => \CodeIgniter\Shield\Filters\GroupFilter::class,
        'permission'    => \CodeIgniter\Shield\Filters\PermissionFilter::class,
        'chain'         => \CodeIgniter\Shield\Filters\ChainAuth::class,
        'tokens'        => \CodeIgniter\Shield\Filters\TokenAuth::class,

        'idle'          => \App\Filters\IdleTimeoutFilter::class,
        'localonly'     => \App\Filters\LocalOnlyFilter::class,
        'auththrottle'  => \App\Filters\AuthThrottleFilter::class,
        'appheaders'    => \App\Filters\AppSecurityHeadersFilter::class,
    ];

    /**
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'localonly',
        ],
        'after' => [
            'appheaders',
        ],
    ];

    /**
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            'csrf',
        ],
        'after' => [],
    ];

    /**
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        'auththrottle' => [
            'before' => [
                'login',
                'login/*',
                'register',
                'register/*',
                'magic-link',
                'magic-link/*',
            ],
        ],
    ];
}
