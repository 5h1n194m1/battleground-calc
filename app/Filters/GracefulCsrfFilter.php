<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;

class GracefulCsrfFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! in_array(strtolower($request->getMethod()), ['post', 'put', 'patch', 'delete'], true)) {
            return null;
        }

        $security = service('security');

        try {
            $security->verify($request);
            return null;
        } catch (SecurityException $e) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Sesi berakhir, silakan muat ulang halaman dan coba lagi.',
                        'csrfTokenName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                    ]);
            }

            $uri = trim($request->getUri()->getPath(), '/');
            $authUris = ['login', 'register', 'magic-link'];

            if (! auth()->loggedIn() || in_array($uri, $authUris, true)) {
                return redirect()->to(site_url('login?expired=1'));
            }

            return redirect()->back()->with('error', 'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
