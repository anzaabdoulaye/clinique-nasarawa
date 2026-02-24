<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

        if ($user instanceof Utilisateur && $user->isForcePasswordChange()) {
            return new RedirectResponse($this->urlGenerator->generate('app_change_password'));
        }

        // Mets ici ton dashboard (ou page d'accueil)
        return new RedirectResponse($this->urlGenerator->generate('home'));
    }
}