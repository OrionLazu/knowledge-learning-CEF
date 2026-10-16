<?php

namespace App\Entity\Contract;

use App\Entity\User;

/**
 * Contract for every entity that keeps track of who created or modified it, and when.
 *
 * The values are filled automatically by {@see \App\EventListener\AuditListener}:
 * controllers and services never set them by hand.
 */
interface AuditableInterface
{
    public function getCreatedAt(): ?\DateTimeImmutable;

    public function setCreatedAt(\DateTimeImmutable $createdAt): static;

    public function getUpdatedAt(): ?\DateTimeImmutable;

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static;

    public function getCreatedBy(): ?User;

    public function setCreatedBy(?User $createdBy): static;

    public function getUpdatedBy(): ?User;

    public function setUpdatedBy(?User $updatedBy): static;
}
