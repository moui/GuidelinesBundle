<?php

declare(strict_types=1);

/*
 * This file is part of the "GuidelinesBundle" for Kimai.
 * Copyright (c) 2026 Mauricio Barrera (https://github.com/moui)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\GuidelinesBundle\Widget;

use App\Widget\Type\AbstractWidget;
use App\Widget\WidgetInterface;
use KimaiPlugin\GuidelinesBundle\Service\GuidelinesSettings;

/**
 * "Guidelines and news": the Markdown text admins keep on System → Guidelines,
 * such as how to log time, how to ask for leave, what changed in the system.
 */
final class GuidelinesWidget extends AbstractWidget
{
    public const ID = 'GuidelinesWidget';

    public function __construct(private readonly GuidelinesSettings $settings)
    {
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function getTitle(): string
    {
        return 'guidelines.widget.title';
    }

    public function getWidth(): int
    {
        return WidgetInterface::WIDTH_FULL;
    }

    public function getHeight(): int
    {
        return WidgetInterface::HEIGHT_LARGE;
    }

    public function getTemplateName(): string
    {
        return '@Guidelines/widget.html.twig';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     * @return array{content: string, updated_at: \DateTimeImmutable|null, updated_by: string}
     */
    public function getData(array $options = []): mixed
    {
        $updatedAt = $this->settings->getUpdatedAt();

        return [
            'content' => $this->settings->getContent(),
            'updated_at' => $updatedAt?->setTimezone($this->getTimezone()),
            'updated_by' => $this->settings->getUpdatedBy(),
        ];
    }
}
