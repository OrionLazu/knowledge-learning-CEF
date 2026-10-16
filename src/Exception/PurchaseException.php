<?php

namespace App\Exception;

/**
 * Business error raised while buying a course or a lesson.
 *
 * Its message is written for the client and can be displayed as is.
 */
class PurchaseException extends \DomainException
{
}
