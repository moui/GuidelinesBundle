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
 * Rejects a finished entry without a meaningful description. Running timers
 * pass, so starting one from the toolbar still works; the description is
 * asked for when the entry is stopped or saved with an end time.
 */
final class TimesheetDescriptionRequiredValidator extends ConstraintValidator
{
    public function __construct(private readonly GuidelinesSettings $settings)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!($constraint instanceof TimesheetDescriptionRequired)) {
            throw new UnexpectedTypeException($constraint, TimesheetDescriptionRequired::class);
        }

        if (!\is_object($value) || !($value instanceof Timesheet)) {
            throw new UnexpectedTypeException($value, Timesheet::class);
        }

        if (!$this->settings->isDescriptionRequired() || $value->isRunning() || $value->getBegin() === null) {
            return;
        }

        $since = $this->settings->getDescriptionRequiredSince();
        if ($since !== null && $value->getBegin()->format('Y-m-d') < $since) {
            return;
        }

        $min = $this->settings->getDescriptionMinLength();
        if (mb_strlen(trim((string) $value->getDescription())) >= $min) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ min }}', (string) $min)
            ->atPath('description')
            ->setCode(TimesheetDescriptionRequired::DESCRIPTION_REQUIRED_ERROR)
            ->addViolation();
    }
}
