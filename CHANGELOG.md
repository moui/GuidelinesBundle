# Changelog

## 0.3.0

- First public release: translations (English, Spanish) for the dashboard card, the settings
  page, flash messages and validation messages. English is the fallback.
- The default dashboard text is now a short, generic English example guide
  (`Resources/defaults/guidelines.md`) meant to be replaced by the administrator.
- Dates on the card and the settings page use Kimai's locale-aware date formats.
- Validation error codes renamed to `guidelines-description-required-01`,
  `guidelines-future-limit-01` and `guidelines-future-limit-02`.
- MIT license, CI linting (PHP-CS-Fixer, PHPStan level 9) and contribution guidelines.

## 0.2.0

- **Future times** limit on System → Guidelines: within today, or up to N minutes from now
  (default 120), as a `TimesheetConstraint` on begin and end. Off (`kimai`) by default;
  needs Kimai's "Allow time entries in the future" switched on to have an effect.

## 0.1.0

- First release, targeting Kimai 2.66 (`extra.kimai.require` 26600) and PHP 8.2+.
- **Guidelines and news** dashboard widget: admin-edited Markdown, full width, at the bottom
  of the default dashboard, with "Updated on … by …". `kimai:guidelines:add-widget`
  appends it to customised dashboards.
- **System → Guidelines** settings page with preview (`guidelines_edit`, admins by default).
- Required-description rule as a `TimesheetConstraint`: off by default, minimum length,
  applies from a chosen date, skips running timers.
