# Sapiens — read-only fields and display values inside edit forms

Opened: 2026-10-01
Updated: 2026-10-01
Closed: 2026-10-01 — commit `3c06379`
Author: agent:sapiens

## Context

Asked by the Sapiens app (`HOME_HABILIS/local/sapiens`). Closes the line **Read-only fields inside an edit form (creation date and the like)** of Sapiens' `.wex/knowledge/contributing/stack-requirements.md.j2`. Neither this package nor the design-system `form` component handles `readonly` or `disabled` today; the design-system `properties` component (label + value) is the only candidate found.

## What the line asks

In an edit form, some values are shown and cannot be changed. A server-side guarantee, not a visual effect. Sapiens has three families:

- **automatic values**: creation date, activation date, last login, establishment "automatic value";
- **values coming from the medical device**: measured dry weight, total weight, OH — shown among the editable fields of the patient record, never editable by hand;
- **fields editable depending on context**: one form serves several roles or states, and a field is editable for one and frozen for another.

## Two distinct needs

1. **A display value inside the form layout.** Not a field: nothing submitted, nothing bound to the model. A label and a formatted value, aligned with the real fields so the modal reads as one block. `properties` may be the base. Dates show in French and in the reader's time zone while stored in UTC; an empty value has an explicit rendering ("—" for "never signed in"), not a blank.
2. **A real field rendered read-only.** Bound to the model, shown with its type's look, not modifiable. The trap:
   - HTML `readonly` alone is **cosmetic**: a forged POST changes the value. The server must ignore what is submitted for the field — which Symfony's `disabled: true` does, keeping model data.
   - But `disabled` renders HTML `disabled`: greyed (reads as "unavailable", not "frozen"), out of keyboard navigation, often skipped by screen readers. Sapiens is read against IEC 62366 (usability). The right combination: **`disabled` server semantics, HTML `readonly` rendering** — focusable, readable, selectable.
   - HTML `readonly` **does not exist** for `select`, `radio`, `checkbox`, hence `SwitchInputType`. Those need their own frozen rendering (the chosen value shown, or the control visibly locked) without falling back on the greyed look. Covers every type: Text, Email, Number, Float, Date, Datetime, Time, Select, Radio, Switch, Textarea, File.

## Conditional read-only is decided in the form type, not in Twig

An option declared on the type (a list of frozen fields, or a callable receiving the object and the user) lets one form serve the admin and the healthcare professional. Decided in the template, nothing protects the server.

## Both renderings

The design system has Twig and Vue components (`.vue.twig`). Read-only must work in both, or a form rendered in Vue loses it silently.

## Tests

- A forged POST on a read-only field leaves the model value unchanged.
- Every input type has a read-only rendering, select, radio and switch included.
- A read-only field stays reachable by keyboard.
- A display value never appears in submitted data.
