<?php

declare(strict_types=1);

/*
 * This file is part of the "GuidelinesBundle" for Kimai.
 * Copyright (c) 2026 Mauricio Barrera (https://github.com/moui)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\GuidelinesBundle\EventSubscriber;

use App\Event\DashboardEvent;
use KimaiPlugin\GuidelinesBundle\Widget\GuidelinesWidget;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Puts the widget at the bottom of the default dashboard. Users who already
 * customised their dashboard keep it as is; kimai:guidelines:add-widget
 * appends it for them.
 */
final class DashboardSubscriber implements EventSubscriberInterface
{
    /** Kimai's own widgets sit at 0, 10, 20…; this keeps the card last. */
    private const POSITION = 1000;

    public static function getSubscribedEvents(): array
    {
        return [
            DashboardEvent::class => ['onDashboard', 100],
        ];
    }

    public function onDashboard(DashboardEvent $event): void
    {
        $event->addWidget(GuidelinesWidget::ID, self::POSITION);
    }
}
