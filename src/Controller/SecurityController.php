<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route("/login", name: "app_login")]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            if ($user->isMustChangePassword()) {
                return $this->redirectToRoute(
                    "app_change_password_first_login",
                );
            }

            return $this->redirectToRoute("app_dashboard");
        }

        return $this->render("security/login.html.twig", [
            "last_username" => $authenticationUtils->getLastUsername(),
            "error" => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route("/dashboard", name: "app_dashboard")]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute("app_login");
        }

       return match (true) {
    // $this->isGranted('ROLE_SUPER_ADMIN')          => $this->redirectToRoute('admin_dashboard'),
    $this->isGranted("ROLE_JURY") => $this->redirectToRoute(
        "jury_index",
    ),
    $this->isGranted("ROLE_RESPONSABLE") => $this->redirectToRoute(
        "responsable_index",
    ),
    $this->isGranted("ROLE_ADMIN") => $this->redirectToRoute(
        "admin_index",
    ),
    default => $this->redirectToRoute("app_login"),
};
    }

    #[Route(path: "/logout", name: "app_logout")]
    public function logout(): void
    {
        throw new \LogicException(
            "This method can be blank - it will be intercepted by the logout key on your firewall.",
        );
    }

    #[IsGranted("ROLE_USER")]
    #[Route("/premiere-connexion", name: "app_change_password_first_login")]
    public function changePasswordFirstLogin(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute("app_login");
        }

        if (!$user->isMustChangePassword()) {
            return $this->redirectToRoute("app_dashboard");
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get("plainPassword")->getData();

            $user->setPassword(
                $passwordHasher->hashPassword($user, $plainPassword),
            );
            $user->setMustChangePassword(false);

            $em->flush();

            $this->addFlash("success", "Votre mot de passe a été mis à jour.");

            return $this->redirectToRoute("app_dashboard");
        }

        return $this->render("security/change_password.html.twig", [
            "form" => $form->createView(),
        ]);
    }
}
