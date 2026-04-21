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

        $users = $this->getUserProvider();
        $rules = $this->getValidationRules();

        if (! $this->validateData($this->request->getPost(), $rules, [], config('Auth')->DBGroup)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $allowedPostFields = array_keys($rules);
        $user              = $users->createNewUser($this->request->getPost($allowedPostFields));

        if ($user->username === null) {
            $user->username = null;
        }

        try {
            $users->save($user);
        } catch (ValidationException) {
            return redirect()->back()->withInput()->with('errors', $users->errors());
        }

        $user = $users->findById($users->getInsertID());

        $users->addToDefaultGroup($user);

        Events::trigger('register', $user);

        // aktifkan user langsung
        $user->activate();
        $users->save($user);

        // sengaja TIDAK auto-login
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

    protected function getValidationRules(): array
    {
        return setting('Validation.registration') ?? [];
    }
}