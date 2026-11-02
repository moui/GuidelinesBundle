<?php

declare(strict_types=1);

/*
 * This file is part of the "GuidelinesBundle" for Kimai.
 * Copyright (c) 2026 Mauricio Barrera (https://github.com/moui)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\GuidelinesBundle\Validator;

use App\Validator\Constraints\TimesheetConstraint;

/**
 * A softer version of Kimai's "no future times" rule. It only has an effect
 * when Kimai's own "Allow time entries in the future" is on; otherwise Kimai's stricter
 * check rejects the entry first.
 */
final class TimesheetFutureLimit extends TimesheetConstraint
{
    public const BEGIN_TOO_FAR_ERROR = 'guidelines-future-limit-01';
    public const END_TOO_FAR_ERROR = 'guidelines-future-limit-02';

    protected const ERROR_NAMES = [
        self::BEGIN_TOO_FAR_ERROR => 'BEGIN_TOO_FAR_ERROR',
        self::END_TOO_FAR_ERROR => 'END_TOO_FAR_ERROR',
    ];

    public string $sameDayMessage = 'guidelines.validation.future_same_day';
    public string $hoursMessage = 'guidelines.validation.future_hours';
    public string $minutesMessage = 'guidelines.validation.future_minutes';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
