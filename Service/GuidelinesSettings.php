<?php

declare(strict_types=1);

/*
 * This file is part of the "GuidelinesBundle" for Kimai.
 * Copyright (c) 2026 Mauricio Barrera (https://github.com/moui)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\GuidelinesBundle\Service;

use App\Configuration\ConfigurationService;
use App\Configuration\SystemConfiguration;
use App\Form\Model\Configuration;
use App\Form\Model\SystemConfiguration as SystemConfigurationModel;

/**
 * The plugin's settings, edited on System → Guidelines and stored by Kimai in
 * kimai2_configuration as guidelines.*. A key without a saved row falls back
 * to the defaults registered in GuidelinesExtension.
 */
final class GuidelinesSettings
{
    public const CONTENT = 'guidelines.content';
    public const UPDATED_AT = 'guidelines.updated_at';
    public const UPDATED_BY = 'guidelines.updated_by';
    public const DESCRIPTION_REQUIRED = 'guidelines.description_required';
    public const DESCRIPTION_MIN_LENGTH = 'guidelines.description_min_length';
    public const DESCRIPTION_REQUIRED_SINCE = 'guidelines.description_required_since';
    public const FUTURE_MODE = 'guidelines.future_mode';
    public const FUTURE_MINUTES = 'guidelines.future_minutes';

    /** Leave it to Kimai's own "Allow time entries in the future" setting. */
    public const FUTURE_MODE_KIMAI = 'kimai';
    /** Future times allowed until the end of today. */
    public const FUTURE_MODE_SAME_DAY = 'same_day';
    /** Future times allowed up to FUTURE_MINUTES from now. */
    public const FUTURE_MODE_MINUTES = 'minutes';
    public const FUTURE_MODES = [self::FUTURE_MODE_KIMAI, self::FUTURE_MODE_SAME_DAY, self::FUTURE_MODE_MINUTES];

    /** kimai2_configuration.value is TEXT (64 KB); leave room for multibyte text. */
    public const MAX_CONTENT_LENGTH = 20000;
    public const MAX_MIN_LENGTH = 200;
    public const MAX_FUTURE_MINUTES = 1440;

    public function __construct(
        private readonly SystemConfiguration $configuration,
        private readonly ConfigurationService $store,
    ) {
    }

    public function getContent(): string
    {
        return (string) ($this->configuration->find(self::CONTENT) ?? '');
    }

    /**
     * When and by whom the text was last saved, shown under the widget.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        $value = (string) ($this->configuration->find(self::UPDATED_AT) ?? '');
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    public function getUpdatedBy(): string
    {
        return (string) ($this->configuration->find(self::UPDATED_BY) ?? '');
    }

    public function isDescriptionRequired(): bool
    {
        return (bool) $this->configuration->find(self::DESCRIPTION_REQUIRED);
    }

    public function getDescriptionMinLength(): int
    {
        return max(1, min(self::MAX_MIN_LENGTH, (int) $this->configuration->find(self::DESCRIPTION_MIN_LENGTH)));
    }

    /**
     * First day (Y-m-d, in the entry's own timezone) the rule applies to.
     * Older entries stay editable without a description, so bulk edits of
     * historical data keep working.
     */
    public function getDescriptionRequiredSince(): ?string
    {
        $value = (string) ($this->configuration->find(self::DESCRIPTION_REQUIRED_SINCE) ?? '');

        return self::isDate($value) ? $value : null;
    }

    public function getFutureMode(): string
    {
        $value = (string) ($this->configuration->find(self::FUTURE_MODE) ?? '');

        return \in_array($value, self::FUTURE_MODES, true) ? $value : self::FUTURE_MODE_KIMAI;
    }

    public function getFutureMinutes(): int
    {
        return max(1, min(self::MAX_FUTURE_MINUTES, (int) $this->configuration->find(self::FUTURE_MINUTES)));
    }

    /**
     * Our future rule only loosens anything when Kimai's own check is off.
     */
    public function isKimaiFutureTimesAllowed(): bool
    {
        return $this->configuration->isTimesheetAllowFutureTimes();
    }

    public function save(string $content, bool $required, int $minLength, string $since, string $futureMode, int $futureMinutes, string $updatedBy): void
    {
        $values = [
            self::CONTENT => $content,
            self::DESCRIPTION_REQUIRED => $required ? '1' : '0',
            self::DESCRIPTION_MIN_LENGTH => (string) $minLength,
            self::DESCRIPTION_REQUIRED_SINCE => $since,
            self::FUTURE_MODE => $futureMode,
            self::FUTURE_MINUTES => (string) $futureMinutes,
        ];

        // Only a change to the text counts as an update people should notice.
        if ($content !== $this->getContent()) {
            $values[self::UPDATED_AT] = (new \DateTimeImmutable())->format(\DATE_ATOM);
            $values[self::UPDATED_BY] = $updatedBy;
        }

        $model = new SystemConfigurationModel('guidelines');
        foreach ($values as $key => $value) {
            // An empty string, not null: Kimai deletes the row for null, which
            // would silently bring the default back.
            $model->addConfiguration((new Configuration($key))->setValue($value));
            $this->configuration->set($key, $value);
        }

        // Through the service, not the repository: it also drops Kimai's
        // one-day "configurations" cache, otherwise the new values would only
        // show up after the cache expires.
        $this->store->saveSystemConfiguration($model);
    }

    public static function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
