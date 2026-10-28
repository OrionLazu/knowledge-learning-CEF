<?php

namespace App\Exception;

/**
 * Business error raised while validating a lesson.
 *
 * Its message is written for the client and can be displayed as is.
 */
class ProgressException extends \DomainException
{
}
