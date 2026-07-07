<?php

namespace App\Service;

use App\Entity\Candidat;
use App\Entity\Candidature;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

class EmailService
{
    private const FROM = "no-reply@egreffe.bj";

    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {}

    #    public function envoyerRecuPaiement(CertificatIndividualite $certificat): void
    #    {
    #        $this->sendEmailSafe(
    #            (new TemplatedEmail())
    #                ->from(self::FROM)
    #                ->to($certificat->getCitoyenEmail())
    #                ->subject('Paiement confirmé — Certificat d\'individualité')
    #                ->htmlTemplate('emails/recu_paiement.html.twig')
    #                ->context(['certificat' => $certificat]),
    #            'recu_paiement'
    #        );
    #    }
    #
    #    public function envoyerDemandeCorrection(CertificatIndividualite $certificat, string $instructions): void
    #    {
    #        $this->sendEmailSafe(
    #            (new TemplatedEmail())
    #                ->from(self::FROM)
    #                ->to($certificat->getCitoyenEmail())
    #                ->subject('Action requise — Votre dossier nécessite des corrections')
    #                ->htmlTemplate('emails/demande_correction.html.twig')
    #                ->context([
    #                    'certificat'   => $certificat,
    #                    'instructions' => $instructions,
    #                ]),
    #            'demande_correction'
    #        );
    #    }
    #
    public function envoyerConfirmationDepotCandidature(
        Candidature $candidature,
        Candidat $candidat,
    ): void {
        $email = (new TemplatedEmail())
            ->from(self::FROM)
            ->to($candidat->getEmail())
            ->subject("Depot de candidature effectué avec succes")
            ->htmlTemplate("emails/candidature_acceptee.html.twig")
            ->context(["candidat" => $candidat, "candidature" => $candidature]);

        $this->sendEmailSafe($email, "certificat_traite");
    }

    public function envoyerInfoCreationUser(
        User $user,
        string $plainPassword,
    ): void {
        $this->sendEmailSafe(
            (new TemplatedEmail())
                ->from(self::FROM)
                ->to($user->getEmail())
                ->subject("Création de votre compte — Cour Suprême")
                ->htmlTemplate("emails/creation_compte_user.html.twig")
                ->context([
                    "user" => $user,
                    "plainPassword" => $plainPassword,
                ]),
            "creation_utilisateur",
        );
    }

    public function envoyerConfirmationCreationUser(
        User $user,
        User $createdBy,
    ): void {
        $this->sendEmailSafe(
            (new TemplatedEmail())
                ->from(self::FROM)
                ->to($createdBy->getEmail())
                ->subject("Compte créé avec succès")
                ->htmlTemplate("emails/creation_compte_admin.html.twig")
                ->context([
                    "user" => $user,
                    "createdBy" => $createdBy,
                ]),
            "confirmation_creation_utilisateur",
        );
    }

    public function resetPassword(User $user, ResetPasswordToken $resetToken)
    {
        $this->sendEmailSafe(
            (new TemplatedEmail())
                ->from(self::FROM)
                ->to((string) $user->getEmail())
                ->subject("Réinitialisation de votre mot de passe")
                ->htmlTemplate("reset_password/email.html.twig")
                ->context([
                    "resetToken" => $resetToken,
                    "user" => $user,
                ]),
            "reset_password",
        );
    }

    private function sendEmailSafe(TemplatedEmail $email, string $type): void
    {
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                \sprintf(
                    "Échec envoi email [%s] : %s",
                    $type,
                    $e->getMessage(),
                ),
                ["to" => $email->getTo()],
            );
        }
    }
}