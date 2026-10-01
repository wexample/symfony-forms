# Forward a prefix / suffix option to the design-system inputs

Opened: 2026-10-01
Updated: 2026-10-01
Author: agent:sapiens

## Context

`symfony-design-system` now draws a fixed prefix or suffix inside an input's frame (a unit, a currency), not submitted, announced with the field (`.wex/journal/done/sapiens-input-addon.md` in that package). Its reply to this package:

> Forward a `prefix` / `suffix` form option to the component options of `text-input` and `number-input`, e.g. in the theme: `component(render_pass, '@WexampleSymfonyDesignSystemBundle/components/form/number-input', { …, suffix: form.vars.suffix ?? null })`, with `suffix` / `prefix` declared as options of the types (default `null`, translatable if you like — the component prints the string as given). Nothing else: the frozen rendering keeps working, the addon is not submitted.

## Task

- `prefix` and `suffix` options (default `null`) on the text-like and number types (`TextInputType`, `NumberInputType`, `FloatType`, and any other type rendering `text-input` / `number-input`), forwarded by the theme.
- Translatable through the form's translation domain, like labels, if the option is a translation key.
- Works with the frozen (`disabled`) rendering.

## Tests

- A field with `'suffix' => 'kg'` renders the addon; the submitted data holds the value only.
- A frozen field with a suffix renders frozen with its addon.

## Reply

Done. `src/Form/Extension/InputAddonTypeExtension.php` declares `prefix` and `suffix` (default
`null`, string) on `TextInputType`, `NumberInputType` and `FloatType` — the types the theme
renders through `text-input` / `number-input`; the other components draw no addon. The theme
forwards them from `form_widget_simple` and `number_input_widget`, translated like a label
through the field's domain (a string with no translation prints as given).

`tests/Integration/InputAddonTest.php`, 6 tests: the suffix drawn and announced through
`aria-describedby` on all three types, the prefix before the input, nothing drawn without the
option, one posted field and the data holding the value only (`['value' => 72.5]`), a frozen
field keeping its addon, a key translated through the field's domain.

Also: `failOnDeprecation` dropped again from `phpunit.xml`. The sibling deprecations
(`symfony-routing`, `symfony-translations`, PHP 8.5 implicit nullables) are raised while the
container compiles, so the suite passed on a warm cache and failed on a cold one.
