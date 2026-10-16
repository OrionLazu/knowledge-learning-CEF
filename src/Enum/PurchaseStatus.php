<?php

namespace App\Enum;

/**
 * Life cycle of a purchase.
 *
 * pending  -> the client was sent to the Stripe payment page
 * paid     -> Stripe confirmed the payment, the lessons are unlocked
 * canceled -> the payment was abandoned or could not be started
 */
enum PurchaseStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Canceled = 'canceled';

    /**
     * Label displayed in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de paiement',
            self::Paid => 'Payé',
            self::Canceled => 'Annulé',
        };
    }
}
