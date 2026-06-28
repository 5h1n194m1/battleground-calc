<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use CodeIgniter\Events\Events;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Exceptions\ValidationException;
use CodeIgniter\Shield\Models\UserModel;

class RegisterController extends BaseController
{
    public function registerView()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/dashboard');
        }

        if (! setting('Auth.allowRegistration')) {
            return redirect()->back()->withInput()
                ->with('error', lang('Auth.registerDisabled'));
        }

        return view(setting('Auth.views')['register']);
    }

    public function registerAction(): RedirectResponse
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/dashboard');
        }

        if (! setting('Auth.allowRegistration')) {
            return redirect()->back()->withInput()
                ->with('error', lang('Auth.registerDisabled'));
        }

        $rules = [
            'username' => 'required|min_length[3]|max_length[30]',
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[8]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $users = $this->getUserProvider();

        $user = $users->createNewUser([
            'username' => trim((string) $this->request->getPost('username')),
            'email'    => trim((string) $this->request->getPost('email')),
            'password' => (string) $this->request->getPost('password'),
        ]);

        try {
            $users->save($user);
        } catch (ValidationException) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $users->errors());
        }

        $user = $users->findById($users->getInsertID());

        $users->addToDefaultGroup($user);

        Events::trigger('register', $user);

        $user->activate();
        $users->save($user);

        return redirect()->to('/login')
            ->with('message', 'Registrasi berhasil. Silakan login.');
    }

    protected function getUserProvider(): UserModel
    {
        $provider = auth()->getProvider();

        if (! $provider instanceof UserModel) {
            throw new \RuntimeException('User provider is not valid.');
        }

        return $provider;
    }
}
