<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\RateLimiter;
use Twig\Environment;

class AuthController extends BaseController
{
    private Auth $auth;

    private Csrf $csrf;

    private RateLimiter $rateLimiter;

    public function __construct(Environment $view, Auth $auth, Csrf $csrf, RateLimiter $rateLimiter)
    {
        parent::__construct($view);
        $this->auth = $auth;
        $this->csrf = $csrf;
        $this->rateLimiter = $rateLimiter;
    }

    public function login(Request $request): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/admin/inbox');
        }

        $error = null;
        $clientIp = $this->getClientIp($request);

        if ($request->isPost()) {
            if ($this->rateLimiter->isBlocked($clientIp)) {
                $timeRemaining = $this->rateLimiter->getTimeRemaining($clientIp);
                $error = sprintf('Too many failed attempts. Please try again in %d seconds.', $timeRemaining);
                return $this->render('admin/login.twig', [
                    'error' => $error,
                    'email' => $request->input('email', ''),
                ], 429);
            }

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
                $this->rateLimiter->recordSuccess($clientIp);
                return Response::redirect('/admin/inbox');
            }

            $this->rateLimiter->recordFailure($clientIp);
            $error = 'Invalid credentials. Please try again.';
        }

        return $this->render('admin/login.twig', [
            'error' => $error,
            'email' => $request->input('email', ''),
        ]);
    }

    private function getClientIp(Request $request): string
    {
        $ip = $request->server('REMOTE_ADDR');

        if (is_string($ip) && $ip !== '') {
            return $ip;
        }

        return '127.0.0.1';
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
