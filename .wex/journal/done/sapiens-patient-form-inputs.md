# Check: decimal input in the user's locale, and date input without a time-zone shift

Opened: 2026-10-01
Updated: 2026-10-01
Author: agent:sapiens

## Context

Requested by an application in French (Sapiens) whose forms take measured values and dates of
birth. Mostly a verification: confirm the existing types already do it, fix only what does not.

## Asked

1. Decimal input follows the locale: `72,5` accepted as 72.5, never refused nor read as `725`;
   the point accepted too; shown back with the locale's separator; a Doctrine `DECIMAL`
   round-trips without float rounding. Say which input is rendered and why.
2. A date-only field stores the day typed, whatever the server's and the browser's zones.
3. Optional: a unit suffix next to a number input.

## Reply

Proved by `tests/Integration/LocalizedInputTest.php`, French locale, 8 tests. Written before
the fixes: 5 passed as they were, 3 failed.

### Already right (server side, Symfony's `NumberType`)

- `72,5` and `72.5` both read as 72.5 by `FloatType` and `NumberInputType` — grouping is off
  by default, so the point is never a thousands separator.
- Shown back as `72,5`.
- `input: 'string', scale: 2` keeps a `DECIMAL` a string: `72.30` shown `72,30`, `72,3` typed
  stored `72.30`.
- `DateInputType` stores the day typed with the server in `Pacific/Kiritimati` (UTC+14), `UTC`
  and `Pacific/Pago_Pago` (UTC−11), and shows back a Doctrine-hydrated date unchanged. The
  browser sends a plain `YYYY-MM-DD`, so its zone plays no part.

### Fixed

- **`NumberInputType` rendered `<input type="number">` with no `step`**, whatever the `html5`
  option. In French the localized `72,5` written into it showed an **empty field** on an edit
  form, and the browser refused any decimal (`step` defaulting to 1) and the comma. Now:
  `html5: false` (the default) renders `<input type="text" inputmode="decimal">` with the
  localized value; `html5: true` renders `<input type="number" step="any">` with a point.
- `FloatType` rendered `type="text"` without `inputmode`, and ignored `html5: true`. Same two
  renderings now: `form_widget_simple` forwards the `type` Symfony chose.
- The theme forwards the `inputmode` and `step` Symfony computes, on every simple input.
- **`DateInputType` moved the day when `view_timezone` differed from `model_timezone`**: the 3rd
  typed with a UTC+14 view zone was stored as the 2nd. An application setting `view_timezone`
  to show times in the reader's zone would do it to every date field. `view_timezone` is now
  normalized to `model_timezone` on `DateInputType`. Changes other applications' behaviour
  only where it was a shifted day.

### Not built: the unit suffix

The design system has no input addon — the only wrapper is `form--input-control`, the password
toggle's. Building one is `symfony-design-system`'s (markup + scss); the theme can forward a
`unit` option once the component exists.

### Also

- `failOnDeprecation` restored in `phpunit.xml`: the sibling deprecations are gone.
- `tests/Traits/RendersFormsTrait.php` holds the render-pass setup both render tests share —
  the single place to edit for `drop-faked-render-request` once the `symfony-loader` fix is
  published (it is on `main`, still `15.0.0`).
- `.wex/knowledge/cookbook/numbers-and-dates.md.j2`; the built `freeze-a-field.md`, committed
  empty last time, is now rendered.
