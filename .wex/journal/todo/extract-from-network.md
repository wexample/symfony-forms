# Forms: remaining gaps from network (test tooling, HTML type, entity processor, field errors)

Opened: 2026-09-24
Updated: 2026-09-24
Author: agent:archeology

## Read this first — status of this todo

> **This is a proposal for discussion, not an order to code.** It was written by the 2026-09 network archaeology pass. Read it, then discuss it with the owner: every design choice and recommendation below is to be challenged and validated **before** any code is written. Do not start implementing on your own.
>
> - Context: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/index.md.j2` (entry point, order between packages), then `sources.md.j2` (where the legacy code lives: archive repo, branch checkouts, GitLab issues) and the domain page linked below.
> - Pending owner decisions affecting this work are listed in `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/recap.md.j2`, section "Décisions qui t'attendent". Where this todo assumes an answer, treat it as an open question.
> - Safety: `NETWORK/local/network` runs on **production data** (real bookkeeping, real invoices in `var/`, a prod dump in `.wex/mysql/dumps/`) — read its code only, never run anything against it. Anonymize any fixture taken from network (bank exports, FEC, mails contain real names/accounts). Never copy secrets found in its history (Stripe keys, tokens, passwords, private keys).

## Goal

The core of network's forms is extracted here. The remaining generic pieces are listed below, most valuable first. The whole table with its rationale is in `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/already-extracted-check.md.j2`, section "Forms". The testing-domain todo (symfony-testing) says that `FormTestCaseTrait` belongs to **this** package, in a testing namespace. Note also that `symfony-testing/src/Traits/Form/SearchableFormTestsTrait.php` calls a `formFind()` that nothing defines yet: step 1 fixes that.

Issues: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/gitlab/issues/315.md`, `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/gitlab/issues/293.md`, `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/gitlab/issues/243.md` (test every form, brute-force the required fields, anonymous access), `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/gitlab/issues/170.md` (email field builder), `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/gitlab/issues/320.md` (conventions: "Type" suffix, entity forms mirror entities and embed sub-forms), `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/gitlab/issues/053.md` (every modal form also works as a full page).

## Steps

1. **Form test tooling.** Covered by the sibling todo `extract-from-network-testing.md` (FormTestCaseTrait, TestFormBuilder, brute-force tests). Do that one first: it also fixes `symfony-testing` `SearchableFormTestsTrait::formFind()`.
2. **`HtmlInputType`.** Source: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/src/Wex/BaseBundle/Form/HtmlType.php`. Sanitise with HTMLPurifier (optional dependency). Tests: script tags stripped, allowed tags kept.
3. **maxlength from the `Length` constraint.** Source: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/src/Wex/BaseBundle/Form/Traits/StringTypeTrait.php` (`stringRestrictLength`, which reads annotations). Rewrite it as a form type extension that reads validator metadata. Test.
4. **Field errors in the form domain.** Source: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/src/Wex/BaseBundle/Form/FormError/FormErrorTranslated.php` + `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/src/Wex/BaseBundle/Service/FormProcessor/AbstractFormProcessor.php` lines 309–345 (`addFieldError`, `setFieldError`, `transField`). Key format: `field.<name>.error.<code>`. Test.
5. **`AbstractEntityFormProcessor`.** Source: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/src/Wex/BaseBundle/Service/FormProcessor/AbstractEntityFormProcessor.php` (the F version is richer than prod): `getOrCreateEntity`, `setEntity`, `getSubmittedEntity(id)`, save on valid. Build it on `EntityFormDataResolver`. Kernel test: create and edit.
6. **Builder shortcuts trait.** Source: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/src/Wex/BaseBundle/Form/AbstractForm.php`: `builderAddTitle/Save/Status/Url/Submit`, `buildDateOptions`, the `in_footer` option; `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/src/Wex/BaseBundle/Form/Traits/EmailFormTrait.php` (#170).
7. **Footer split in the theme.** Source: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/src/Wex/BaseBundle/Twig/FormsExtension.php` (`split_form_children`, `set_form_body_children`, `set_form_footer_children`, `revert_form_children`, `form_render_errors`). Buttons marked `in_footer` render in the modal footer.
8. **Thin types.** `TelInputType`, `CheckboxInputType`, and a Doctrine `EntityInputType` wrapper (sources `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/src/Wex/BaseBundle/Form/{TelType,CheckboxType,EntityType}.php`), plus their design-system components.
9. Optional: `createFormView` for several forms on one page and an `onRender` hook (`AbstractFormProcessor` lines 50–285). Drop everything tied to AdaptiveResponse.

## Do not

- Do not port anything Materialize (`MaterializeFieldTypeTrait`, `DefaultTypeTrait`, the materialize theme and macros), or `TranslatedFieldTypeTrait`: the theme already resolves `field.<name>.label`.
- Do not port FOSUser forms (`Login`, `ResetPassword`, `RegistrationFormType`, `FormFactoryUser`).
- Priced form traits go to symfony-money; person-name and password traits go to symfony-user; tunnel forms go to symfony-tunnels.

## Acceptance

- Each step has a unit or kernel test. The design-system demo shows the new types.
