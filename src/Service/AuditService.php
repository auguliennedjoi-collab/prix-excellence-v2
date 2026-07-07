<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AuditService
{
   public function __construct(
      private EntityManagerInterface $em,
   ) {}

   public function log(string $action, mixed $user, array $contexte = []): void
   {
      // On accepte uniquement les User Doctrine, pas UserInterface générique
      $userEntity = $user instanceof User ? $user : null;

      $log = new AuditLog(
         action: $action,
         user: $userEntity,
         contexte: $contexte,
      );

      $this->em->persist($log);
      $this->em->flush();
   }
}
