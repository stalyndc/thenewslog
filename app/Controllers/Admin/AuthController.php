<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Http\Response;

class AuthController extends BaseController
{
    public function login(Request $request): Response
    {
        $context = [
            'message' => $request->isPost()
                ? 'Authentication logic not yet implemented.'
                : null,
        ];

        return $this->render('admin/login.twig', $context);
    }
}
