<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;

class ApiTokenRemoverSubscriber implements EventSubscriberInterface
{
    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof User) {
            return;
        }

        $em = $args->getObjectManager();
        $currentDateTime = new \DateTime();

        $apiTokens = $entity->getApiTokens()->getValues();
        foreach ($apiTokens as $token) {
            if ($token->getExpiresAt() < $currentDateTime) {
                $em->remove($token);
                $em->flush();
            }
        }
    }

    public function getSubscribedEvents(): array 
    {
        return [
            Events::postLoad ,
        ];
    }
}
