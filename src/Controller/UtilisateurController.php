<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/utilisateur')]
final class UtilisateurController extends AbstractController
{
    #[Route('', name: 'app_utilisateur_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $utilisateur = new Utilisateur();

        $form = $this->createForm(UtilisateurType::class, $utilisateur, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();

            $generatedPassword = null;
            if ($plainPassword === '') {
                $generatedPassword = substr(bin2hex(random_bytes(5)), 0, 10);
                $plainPassword = $generatedPassword;
            }

            $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $plainPassword));

            // ensure ROLE_USER
            $roles = $utilisateur->getRoles();
            if (!in_array('ROLE_USER', $roles, true)) {
                $roles[] = 'ROLE_USER';
                $utilisateur->setRoles($roles);
            }

            // force password change at first login
            $utilisateur->setForcePasswordChange(true);

            $em->persist($utilisateur);
            $em->flush();

            if ($generatedPassword !== null) {
                $this->addFlash('success', 'Utilisateur créé. Mot de passe temporaire : ' . $generatedPassword);
            } else {
                $this->addFlash('success', 'Utilisateur créé.');
            }

            return $this->redirectToRoute('app_utilisateur_index');
        }

        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/param', name: 'app_utilisateur_param', methods: ['GET', 'POST'])]
    public function param(Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/param.html.twig', [
            // 'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_utilisateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();

            if ($plainPassword === '') {
                $generatedPassword = substr(bin2hex(random_bytes(5)), 0, 10);
                $plainPassword = $generatedPassword;
            } else {
                $generatedPassword = null;
            }

            $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $plainPassword));

            // ensure ROLE_USER (important)
            $roles = $utilisateur->getRoles();
            if (!in_array('ROLE_USER', $roles, true)) {
                $roles[] = 'ROLE_USER';
                $utilisateur->setRoles($roles);
            }

            $utilisateur->setForcePasswordChange(true);

            $entityManager->persist($utilisateur);
            $entityManager->flush();

            if ($generatedPassword !== null) {
                $this->addFlash('success', 'Utilisateur créé. Mot de passe temporaire : ' . $generatedPassword);
            } else {
                $this->addFlash('success', 'Utilisateur créé.');
            }

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form->createView(),
        ]);
    }

    /**
     * IMPORTANT: route statique AVANT les routes dynamiques + pas de conflit avec /{id}
     */
    #[Route('/change-password', name: 'app_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(\App\Form\ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = (string) $form->get('plainPassword')->getData(); // OK avec RepeatedType
            $user->setPassword($passwordHasher->hashPassword($user, $plain));
            $user->setForcePasswordChange(false);
            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe modifié.');

            return $this->redirectToRoute('home'); // adapte si besoin
        }

        return $this->render('security/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * ✅ Contraintes: {id<\d+>} pour empêcher "change-password" de matcher ici
     */
    #[Route('/{id<\d+>}', name: 'app_utilisateur_show', methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UtilisateurType::class, $utilisateur, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();

            if ($plainPassword !== '') {
                $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $plainPassword));
                $utilisateur->setForcePasswordChange(false);
            }

            // ensure ROLE_USER
            $roles = $utilisateur->getRoles();
            if (!in_array('ROLE_USER', $roles, true)) {
                $roles[] = 'ROLE_USER';
                $utilisateur->setRoles($roles);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié.');
            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_utilisateur_delete', methods: ['POST'])]
    public function delete(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete' . $utilisateur->getId(), (string) $submittedToken)) {
            $entityManager->remove($utilisateur);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
    }
}