<?php

namespace App\Entity\Contract;

use App\Entity\Lesson;

/**
 * Something a client can buy: a whole course or a single lesson.
 *
 * The purchase process only depends on this contract, so it handles both cases with the same code.
 */
interface PurchasableInterface
{
    public function getId(): ?int;

    /**
     * Price in euro cents.
     */
    public function getPrice(): int;

    /**
     * Label written on the order line and on the Stripe payment page.
     */
    public function getPurchaseLabel(): string;

    /**
     * Lessons the buyer gets access to once the payment is confirmed.
     *
     * @return list<Lesson>
     */
    public function getIncludedLessons(): array;
}
