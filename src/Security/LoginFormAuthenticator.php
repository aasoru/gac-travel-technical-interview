<?php

namespace App\Security;

use App\Repository\UsersRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;
    use SecurityRequestTrait;

    private UrlGeneratorInterface $urlGenerator;
    private UsersRepository $usersRepository;

    public function __construct(UsersRepository $usersRepository, UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
        $this->usersRepository = $usersRepository;
    }

    public function supports(Request $request): bool
    {
        return $this->supportLoginFormRequest($request);
    }

    public function authenticate(Request $request): PassportInterface
    {
        $username = $this->getUsername($request);
        $password = $this->getPassword($request);

        $userBadge = new UserBadge(
            $username,
            function (string $username) {
                $user = $this->usersRepository->findOneByUserName($username);
                if (!$user) {
                    throw  new UsernameNotFoundException();
                }

                return $user;
            }
        );

        $credentials = new PasswordCredentials($password);
        $csrfTokenBadge = new CsrfTokenBadge('authenticate', $this->getCsrfToken($request));

        return new Passport($userBadge, $credentials, [$csrfTokenBadge]);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): RedirectResponse
    {
        return new RedirectResponse($this->getDashboardUrl($this->urlGenerator));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::LAST_USERNAME, $this->getUsername($request));
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        return $this->start($request, $exception);
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse($this->getLoginUrl($this->urlGenerator));
    }
}
