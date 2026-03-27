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

        $roles = $user instanceof Utilisateur ? $user->getRoles() : [];

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('home'));
        }

        if (in_array('ROLE_ACCUEIL', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_patient_index'));
        }

        if (in_array('ROLE_MEDECIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_consultation_index'));
        }

        if (in_array('ROLE_PERCEPTION', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_perception_index'));
        }

        if (in_array('ROLE_LABO', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_laboratoire_index'));
        }

        if (in_array('ROLE_INFIRMIER', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_consultation_index'));
        }

        if (in_array('ROLE_PHARMACIE', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_pharmacie_index'));
        }

        if (in_array('ROLE_HOSPITALISATION', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_hospitalisation_index'));
        }

        if (in_array('ROLE_COMPTA_MATIERE', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_comptabilite_matiere_index'));
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }
}