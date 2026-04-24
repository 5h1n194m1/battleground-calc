<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public string $baseURL = 'http://localhost:9000/';

    /**
     * @var list<string>
     */
    public array $allowedHostnames = ['localhost', '127.0.0.1'];

    public string $indexPage = 'index.php';
    public string $uriProtocol = 'REQUEST_URI';
    public string $permittedURIChars = 'a-z 0-9~%.:_\-';
    public string $defaultLocale = 'id';
    public bool $negotiateLocale = false;

    /**
     * @var list<string>
     */
    public array $supportedLocales = ['id', 'en'];

    public string $appTimezone = 'Asia/Jakarta';
    public string $charset = 'UTF-8';
    public bool $forceGlobalSecureRequests = false;

    /**
     * @var array<string, string>
     */
    public array $proxyIPs = [];

    public bool $CSPEnabled = true;
}
