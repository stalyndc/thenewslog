<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Services\Auth;
use App\Services\Csrf;
use Twig\Environment;

class AuthController extends BaseController
{
    private Auth $auth;

    private Csrf $csrf;

    public function __construct(Environment $view, Auth $auth, Csrf $csrf)
    {
        parent::__construct($view);
        $this->auth = $auth;
        $this->csrf = $csrf;
    }

    public function login(Request $request): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/admin/inbox');
        }

        $error = null;

        if ($request->isPost()) {
            $token = $this->csrf->extractToken($request);

            if (!$this->csrf->validate($token)) {
                $error = 'Session expired. Please try again.';
                return $this->render('admin/login.twig', [
                    'error' => $error,
                    'email' => $request->input('email', ''),
                ], 419);
            }

            $email = (string) $request->input('email', '');
            $password = (string) $request->input('password', '');

            if ($this->auth->attempt($email, $password)) {
                return Response::redirect('/admin/inbox');
            }

            $error = 'Invalid credentials. Please try again.';
        }

        return $this->render('admin/login.twig', [
            'error' => $error,
            'email' => $request->input('email', ''),
        ]);
    }

    public function logout(Request $request): Response
    {
        if (!$request->isPost()) {
            return Response::redirect('/admin');
        }

        $token = $this->csrf->extractToken($request);

        if (!$this->csrf->validate($token)) {
            return Response::redirect('/admin');
        }

        $this->auth->logout();

        return Response::redirect('/admin');
    }
}
