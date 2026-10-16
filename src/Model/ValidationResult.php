<?php

namespace App\Model;

use App\Entity\Theme;

/**
 * What happened when a client validated a lesson, used to display the right messages.
 */
final class ValidationResult
{
    /** The lesson was not validated before. */
    public bool $lessonValidated = false;

    /** The validation completed the course, which is now validated too. */
    public bool $courseValidated = false;

    /** Theme whose certification was just earned, if any. */
    public ?Theme $certifiedTheme = null;
}
