<?php

namespace App\EventListener;

use App\Entity\Contract\AuditableInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;

/**
 * Fills the audit columns (created_at, updated_at, created_by, updated_by) of every
 * {@see AuditableInterface} entity right before Doctrine writes it to the database.
 *
 * Centralising this logic in a single Doctrine listener guarantees that no table can be
 * written without its audit data, whichever controller, service or command performs the change.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::onFlush)]
final class AuditListener
{
    public function __construct(
        private readonly Security $security,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * New row: both dates are "now" and both authors are the current user (if any).
     */
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof AuditableInterface) {
            return;
        }

        $now = $this->clock->now();
        $author = $this->getCurrentUser();

        // A value set explicitly beforehand (e.g. by data fixtures) is kept.
        if (null === $entity->getCreatedAt()) {
            $entity->setCreatedAt($now);
        }
        if (null === $entity->getCreatedBy()) {
            $entity->setCreatedBy($author);
        }
        $entity->setUpdatedAt($now);
        $entity->setUpdatedBy($entity->getUpdatedBy() ?? $entity->getCreatedBy());
    }

    /**
     * Existing rows: only the "updated" columns change.
     *
     * onFlush is used rather than preUpdate because Doctrine ignores fields modified in preUpdate
     * unless they were already part of the change set; here the change set is recomputed instead.
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $now = $this->clock->now();
        $author = $this->getCurrentUser();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof AuditableInterface) {
                continue;
            }

            $entity->setUpdatedAt($now);
            $entity->setUpdatedBy($author);
            $unitOfWork->recomputeSingleEntityChangeSet($entityManager->getClassMetadata($entity::class), $entity);
        }
    }

    /**
     * Returns the logged-in user, or null for anonymous requests and console commands.
     *
     * The user is only returned if Doctrine already manages it: a user object coming from
     * the session but detached from the entity manager cannot be used as a foreign key.
     */
    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User && null !== $user->getId() ? $user : null;
    }
}
