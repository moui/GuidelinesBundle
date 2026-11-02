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

use App\Entity\Timesheet;
use KimaiPlugin\GuidelinesBundle\Service\GuidelinesSettings;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Caps how far into the future an entry may begin or end: either up to the
 * end of today (in the entry's timezone, which is the user's) or up to a
 * number of minutes from now. Like Kimai's rule, one extra minute of slack
 * covers forms that submit at minute precision.
 */
final class TimesheetFutureLimitValidator extends ConstraintValidator
{
    public function __construct(private readonly GuidelinesSettings $settings)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetFutureLimit)) {
            throw new UnexpectedTypeException($constraint, TimesheetFutureLimit::class);
        }

        if (!\is_object($value) || !($value instanceof Timesheet)) {
            throw new UnexpectedTypeException($value, Timesheet::class);
        }

        $mode = $this->settings->getFutureMode();
        if ($mode === GuidelinesSettings::FUTURE_MODE_KIMAI || $value->getBegin() === null) {
            return;
        }

        $now = new \DateTimeImmutable('now', $value->getBegin()->getTimezone());

        $minutes = $this->settings->getFutureMinutes();
        // Whole hours read better as hours ("2 hours" rather than "120 minutes").
        $count = $minutes % 60 === 0 ? intdiv($minutes, 60) : $minutes;

        if ($mode === GuidelinesSettings::FUTURE_MODE_SAME_DAY) {
            // An entry may end exactly at midnight.
            $limit = $now->modify('tomorrow')->getTimestamp();
            $message = $constraint->sameDayMessage;
        } else {
            $limit = $now->getTimestamp() + ($minutes * 60) + 60;
            $message = $minutes % 60 === 0 ? $constraint->hoursMessage : $constraint->minutesMessage;
        }

        $checks = [
            ['begin_date', $value->getBegin(), TimesheetFutureLimit::BEGIN_TOO_FAR_ERROR],
            ['end_time', $value->getEnd(), TimesheetFutureLimit::END_TOO_FAR_ERROR],
        ];

        foreach ($checks as [$path, $date, $code]) {
            if ($date === null || $date->getTimestamp() <= $limit) {
                continue;
            }

            $this->context->buildViolation($message)
                ->setParameter('{{ limit }}', (string) $count)
                ->setPlural($count)
                ->atPath($path)
                ->setCode($code)
                ->addViolation();
        }
    }
}
