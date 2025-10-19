<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Services\Auth;
use Twig\Environment;

class AuthController extends BaseController
{
    private Auth $auth;

    public function __construct(Environment $view, Auth $auth)
    {
        parent::__construct($view);
        $this->auth = $auth;
    }

    public function login(Request $request): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/admin/inbox');
        }

        $error = null;

        if ($request->isPost()) {
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

    public function logout(): Response
    {
        $this->auth->logout();

        return Response::redirect('/admin');
    }
}
