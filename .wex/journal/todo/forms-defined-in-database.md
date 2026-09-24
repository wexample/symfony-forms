# Forms defined in the database

Opened: 2026-09-24
Updated: 2026-09-24
Author: agent:main (from symfony-tunnels)

## Why

`symfony-tunnels` wants tunnels an administrator composes from rows, each step showing a form also built in the database (its journal todo "Tunnels defined in the database"). The forms part belongs here: it is useful without tunnels — a contact form, a survey — and tunnels only consume it. Build this first; the tunnels todo depends on it.

This is a proposal for discussion with the owner, not an order to code.

## Scope

- Entities: `Form` (name) with many `FormField` (name, `type` among `html|text|textarea|checkbox` to start, `required`, `position`); `FormSubmission` (form, date, nullable user identifier) with many `FormSubmissionField` (field, JSON value). No relation to an application `User` entity — store the security identifier, as `symfony-tunnels` does.
- A generic form type building unmapped fields from a `Form` row, prefilled from a previous submission; an `html` field renders translated text, not an input.
- A processor persisting the submission (upsert when a submission id is given), so a caller — a tunnel step — only keeps that id.
- Labels come from translations attached to the rows (`@form_field.<id>::label`); that mechanism belongs in `symfony-translations` and has to exist first or alongside.
- Export the entities to TypeScript, as for every entity of the suite.
- Demo page in the forms demo: build a form from fixtures, submit it, show the stored submission.

## Legacy to read

`NETWORK/archeo/trees/develop-131-fos-user/` (read-only): `src/Entity/{Form,FormField,FormSubmission,FormSubmissionField,Translation}.php`, `src/Form/NeutralTunnelEntityForm.php`, `src/Service/FormProcessor/NeutralTunnelEntityFormProcessor.php`, `src/Repository/TranslationRepository.php` (`translateField()`, Twig `trans_entity`). The copies under `src/Wex/BaseBundle/{Form,Service/FormProcessor}/NeutralTunnelEntityForm*.php` are empty stubs; ignore them.
