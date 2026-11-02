# GuidelinesBundle for Kimai

[![CI](https://github.com/moui/GuidelinesBundle/actions/workflows/linting.yaml/badge.svg)](https://github.com/moui/GuidelinesBundle/actions/workflows/linting.yaml)

A Kimai plugin that puts your team's time-tracking guidelines where people actually look: a
**Guidelines and news** card on the dashboard, written in Markdown by an administrator. It can
also enforce two rules on every timesheet save: a **required description** and a softer
**future-time limit** (within today, or up to N minutes ahead).

## Features

- **Guidelines and news** dashboard card: admin-edited Markdown (headings, lists, links,
  tables), full width, at the bottom of the default dashboard, with "Updated on … by …".
- **System → Guidelines** settings page with a live preview.
- **Required description**: off by default, configurable minimum length, applies from a chosen
  date, skips running timers.
- **Future-time limit**: allow future times only within today, or up to N minutes from now.
- Both rules are Kimai `TimesheetConstraint`s, so they apply to the web forms, bulk edit, the
  JSON API and any API client.
- No database tables: settings are stored in Kimai's own configuration table.

## Requirements

- Kimai 2.66 or later
- PHP 8.2 or later

## Installation

Copy the plugin to `var/plugins/` so that `var/plugins/GuidelinesBundle/composer.json` exists,
then clear the cache:

```bash
cd /opt/kimai/var/plugins/
# download and extract the release into GuidelinesBundle/, or:
git clone https://github.com/moui/GuidelinesBundle.git
cd /opt/kimai && bin/console kimai:reload -n
```

The plugin has no database migrations, so no install command is needed.

**Docker**: mount or copy the directory to `/opt/kimai/var/plugins/GuidelinesBundle` and reload
as the web server user:

```bash
docker compose exec -u www-data kimai bin/console kimai:reload -n
```

Running as `www-data` matters: as root, `kimai:reload` leaves a root-owned cache that Kimai
can no longer write to.

## Configuration

Everything is set on **System → Guidelines** (`/{locale}/admin/guidelines`).

- **Dashboard text**: Markdown, rendered by Kimai's own `md2html` filter in safe mode (raw HTML
  is escaped). Up to 20,000 characters. **Preview** renders the posted text without saving it.
  The card shows "Updated on … by …"; only a change to the text updates that line.
- **Required description**: on/off, minimum length (default 5) and **Applies to entries from**,
  which is required when the rule is on.
- **Future times**: Kimai's setting (default), within today, or up to N minutes from now.

Settings are stored in Kimai's configuration table as `guidelines.*`:

| Key | Meaning | Default |
|---|---|---|
| `guidelines.content` | Markdown text of the card | the example guide in `Resources/defaults/guidelines.md` |
| `guidelines.updated_at` / `guidelines.updated_by` | last change to the text | empty |
| `guidelines.description_required` | rule on/off (`1`/`0`) | off |
| `guidelines.description_min_length` | minimum characters after trimming | `5` |
| `guidelines.description_required_since` | first day (`Y-m-d`) the rule applies to | empty |
| `guidelines.future_mode` | `kimai`, `same_day` or `minutes` | `kimai` |
| `guidelines.future_minutes` | minutes ahead allowed in `minutes` mode (1–1440) | `120` |

The example guide is in English and is meant to be replaced: it shows until an administrator
saves the page for the first time.

### Required description

`Validator\TimesheetDescriptionRequired` extends Kimai's `TimesheetConstraint`, so Kimai runs it
on every timesheet save.

- A **running** timer passes, so starting one from the toolbar still works. The description is
  asked for when the timer is stopped or the entry is saved with an end time.
- Entries that **begin before** the "Applies to entries from" date pass, so old entries without
  a description stay editable (for example, for bulk changes to the billable flag).
- There are no exemptions by role.

### Future-time limit

Kimai's own *Allow time entries in the future* setting is all or nothing: when it is off, Kimai
only allows the begin/end rounding plus one minute. `Validator\TimesheetFutureLimit` adds a
middle ground, checked on the begin and the end of every save, in the entry's timezone:

- **`same_day`**: anything up to 00:00 tomorrow.
- **`minutes`**: up to N minutes (plus one minute of slack) from now.
- **`kimai`**: the plugin does nothing.

The plugin can only loosen Kimai's rule if Kimai's rule is out of the way, so **turn on
*Allow time entries in the future*** (System → Settings → Timesheet) when using `same_day` or
`minutes`. The settings page warns while it is still off.

## Permissions

| Permission | Default roles | Grants |
|---|---|---|
| `guidelines_edit` | `ROLE_ADMIN`, `ROLE_SUPER_ADMIN` | the **System → Guidelines** page and the "Edit" link on the card |

Everyone sees the dashboard card. Grant `guidelines_edit` to other roles from Kimai's *Roles*
screen if needed.

## Console commands

The card is added at the end of Kimai's default dashboard. Users who customised their dashboard
keep their layout; append the card for them with:

```bash
bin/console kimai:guidelines:add-widget          # dry-run: reports what would change
bin/console kimai:guidelines:add-widget --apply  # saves the change
```

Re-running it is harmless: users who already have the card are skipped.

## Translations

The plugin ships English (`en`, the fallback) and Spanish (`es`) in
`Resources/translations/`, in the `messages`, `flashmessages` and `validators` domains. To add
a language, copy each `*.en.xlf` file to `*.<locale>.xlf` and translate the `<target>`
elements. The example guide in `Resources/defaults/guidelines.md` is English only; administrators
replace it with their own text.

## Contributing

Bug reports, translations and pull requests are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT. See [LICENSE](LICENSE).
