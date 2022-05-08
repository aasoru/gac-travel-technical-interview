<?php

declare(strict_types = 1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait SecurityRequestTrait
{
    public function getUsername(Request $request): string
    {
        return $request->request->get('_username', '');
    }

    public function getPassword(Request $request): string
    {
        return $request->request->get('_password', '');
    }

    public function getCsrfToken(Request $request): string
    {
        return $request->request->get('_csrf_token', '');
    }

    public function supportLoginFormRequest(Request $request): bool
    {
        return $request->isMethod(Request::METHOD_POST) && $request->attributes->get('_route') === 'login';
    }

    public function getLoginUrl(UrlGeneratorInterface $generator): string
    {
        return $generator->generate('login');
    }

    public function getDashboardUrl(UrlGeneratorInterface $generator): string
    {
        return $generator->generate('users_index');
    }

}
