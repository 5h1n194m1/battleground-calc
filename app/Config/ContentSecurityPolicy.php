<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ContentSecurityPolicy extends BaseConfig
{
    public bool $reportOnly = false;
    public ?string $reportURI = null;
    public ?string $reportTo = null;
    public bool $upgradeInsecureRequests = false;

    /** @var list<string>|string|null */
    public $defaultSrc = 'self';

    /** @var list<string>|string */
    public $scriptSrc = ['self', 'https://cdn.jsdelivr.net'];

    /** @var list<string>|string */
    public array|string $scriptSrcElem = ['self', 'https://cdn.jsdelivr.net'];

    /** @var list<string>|string */
    public array|string $scriptSrcAttr = 'none';

    /** @var list<string>|string */
    public $styleSrc = ['self', 'https://cdn.jsdelivr.net'];

    /** @var list<string>|string */
    public array|string $styleSrcElem = ['self', 'https://cdn.jsdelivr.net'];

    /** @var list<string>|string */
    public array|string $styleSrcAttr = 'unsafe-inline';

    /** @var list<string>|string */
    public $imageSrc = ['self', 'data:'];

    /** @var list<string>|string|null */
    public $baseURI = 'self';

    /** @var list<string>|string */
    public $childSrc = 'none';

    /** @var list<string>|string */
    public $connectSrc = ['self'];

    /** @var list<string>|string */
    public $fontSrc = ['self', 'https://cdn.jsdelivr.net', 'data:'];

    /** @var list<string>|string */
    public $formAction = 'self';

    /** @var list<string>|string|null */
    public $frameAncestors = 'none';

    /** @var list<string>|string|null */
    public $frameSrc = 'none';

    /** @var list<string>|string|null */
    public $mediaSrc = 'self';

    /** @var list<string>|string */
    public $objectSrc = 'none';

    /** @var list<string>|string|null */
    public $manifestSrc = 'self';

    /** @var list<string>|string */
    public array|string $workerSrc = 'self';

    /** @var list<string>|string|null */
    public $pluginTypes = null;

    /** @var list<string>|string|null */
    public $sandbox = null;

    public string $styleNonceTag = '{csp-style-nonce}';
    public string $scriptNonceTag = '{csp-script-nonce}';
    public bool $autoNonce = true;
}
