<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class IdleTimeoutFilter implements FilterInterface
{
    private const IDLE_LIMIT_SECONDS = 300;
    private const SESSION_KEY = 'lastActivityAt';

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! auth()->loggedIn()) {
            return null;
        }

        $session = session();
        $now = time();
        $lastActivityAt = (int) ($session->get(self::SESSION_KEY) ?? 0);

        if ($lastActivityAt > 0 && ($now - $lastActivityAt) >= self::IDLE_LIMIT_SECONDS) {
            auth()->logout();

            return redirect()->to(site_url('login'))
                ->with('error', 'Sesi berakhir karena tidak ada aktivitas selama 5 menit. Silakan login kembali.');
        }

        $session->set(self::SESSION_KEY, $now);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}