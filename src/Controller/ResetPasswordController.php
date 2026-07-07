<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\ResetPasswordEvent;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route("/reset-password")]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    #[Route("", name: "app_forgot_password_request")]
    public function request(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get("email")->getData(),
            );
        }

        return $this->render("reset_password/request.html.twig", [
            "requestForm" => $form,
        ]);
    }

    #[Route("/check-email", name: "app_check_email")]
    public function checkEmail(): Response
    {
        // On génère toujours un faux token pour ne pas révéler
        // si l'email existe ou non en BD (anti-énumération)
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render("reset_password/check_email.html.twig", [
            "resetToken" => $resetToken,
        ]);
    }

    #[Route("/reset/{token}", name: "app_reset_password")]
    public function reset(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        string $token = null,
    ): Response {
        if ($token) {
            $this->storeTokenInSession($token);
            return $this->redirectToRoute("app_reset_password");
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException(
                "Aucun token de réinitialisation trouvé dans la session.",
            );
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser(
                $token,
            );
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash(
                "reset_password_error",
                \sprintf(
                    "Le lien de réinitialisation a expiré ou est invalide : %s",
                    $e->getReason(),
                ),
            );
            return $this->redirectToRoute("app_forgot_password_request");
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);

            $plainPassword = $form->get("plainPassword")->getData();

            $user->setPassword(
                $passwordHasher->hashPassword($user, $plainPassword),
            );
            $user->setMustChangePassword(false);
            $this->em->flush();

            $this->cleanSessionAfterReset();

            $this->addFlash(
                "success",
                "Votre mot de passe a été réinitialisé.",
            );

            return $this->redirectToRoute("app_login");
        }

        return $this->render("reset_password/reset.html.twig", [
            "resetForm" => $form,
        ]);
    }

    private function processSendingPasswordResetEmail(
        string $emailFormData,
    ): Response {
        $user = $this->em
            ->getRepository(User::class)
            ->findOneBy(["email" => $emailFormData]);

        // Pas d'erreur si l'utilisateur n'existe pas — anti-énumération
        if (!$user) {
            return $this->redirectToRoute("app_check_email");
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->redirectToRoute("app_check_email");
        }

        $this->dispatcher->dispatch(
            new ResetPasswordEvent($user, $resetToken),
            ResetPasswordEvent::NAME,
        );
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute("app_check_email");
    }
}
