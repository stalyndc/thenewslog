<?php

namespace App\\Controllers\\Admin;

use App\\Controllers\\BaseController;

class AuthController extends BaseController
{
    public function login(): void
    {
        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

        $context = [
            'message' => $isPost ? 'Authentication logic not yet implemented.' : null,
        ];

        $this->render('admin/login.twig', $context);
    }
}
