<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ZAuthModule\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidRegistrationVerification extends Constraint
{
    public $message = 'The fields are invalid.';

    public function validatedBy()
    {
        return 'zikula.zauth.registration_verification.validator';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
