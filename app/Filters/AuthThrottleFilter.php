<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request->is('post')) {
            return null;
        }

        $throttler = service('throttler');
        $ip = trim((string) $request->getIPAddress());
        $uri = trim($request->getUri()->getPath(), '/');
        $email = strtolower(trim((string) ($request->getPost('email') ?? '')));
        $bucket = sha1('auth:' . $uri . ':' . $ip . ':' . $email);

        if ($throttler->check($bucket, 10, MINUTE) === false) {
            return redirect()->to(site_url('login?throttled=1'))
                ->with('error', 'Terlalu banyak percobaan login. Tunggu sebentar lalu coba lagi.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
