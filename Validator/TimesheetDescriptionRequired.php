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
 * Registered as a TimesheetConstraint, so Kimai's TimesheetValidator runs it
 * on every save — UI, bulk edit, API and the MCP server alike.
 */
final class TimesheetDescriptionRequired extends TimesheetConstraint
{
    public const DESCRIPTION_REQUIRED_ERROR = 'guidelines-description-required-01';

    protected const ERROR_NAMES = [
        self::DESCRIPTION_REQUIRED_ERROR => 'DESCRIPTION_REQUIRED_ERROR',
    ];

    public string $message = 'guidelines.validation.description_required';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
