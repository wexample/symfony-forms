# Form test driver and brute-force form tests from network

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

Give `symfony-forms` a testing namespace (`src/Testing/…`, test env only) that can drive its forms from a KernelBrowser: find a form by FormType class, set any field type, submit (classic or through the `_forms/submit/{name}` processor endpoints / ajax), and return **structured errors**; plus the network "brutalize" test (#315) that submits every incomplete combination of required fields and checks each one is rejected with the right number of errors. This driver is consumed by the `form` action of the proposed `symfony-scenario` package (via its `FormDriverInterface`).

## Read first

- Knowledge page, rows "Forms" and pitfall "ajax branch": `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/testing.md.j2`
- Scenario package todo (consumer): `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/proposed-packages/symfony-scenario/todo/extract-from-network.md`
- Issues: #315 (brute-force combinations), #243 (every form tested), #295 (brute-force URL params).

## Sources

- Best version: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/src/Wex/BaseBundle/Tests/Traits/FormTestCaseTrait.php` (794 lines: `goToFormRoute`, `formFind`, `buildFormName`, select helpers, `formAddFile`, `getField`, `checkBrutalizeForm`, `formFieldsValues`, `fieldSetValue`, `formSubmit`, `formFieldGetNodeValue`).
- `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/src/Class/Test/TestFormBuilder.php` (records children/options while building a FormType) and `…/src/Wex/BaseBundle/Form/Traits/DefaultTypeTrait.php` (`testingBuildValidBruteForceFieldValue` per type: `FloatType`, `EmailType`, `CheckboxType`, `CountryType`).
- Search fields: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/trees/develop-131-fos-user/tests/Traits/Form/SearchableFormTestsTrait.php`, `SearchTestTrait.php` (value format `"<id>-<type>"` via search API).
- Old extracted copy (2024): `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/vendor/wexample/symfony-design-system/src/Tests/Traits/FormTestCaseTrait.php` (≈ same, diff before porting).
- Users of brute force: `…/develop-131-fos-user/tests/Integration/Role/Anonymous/Controller/Tunnels/CampaignControllerTest.php`, `SingleProductControllerTest.php`.

## Steps

1. `src/Testing/FormDriver.php` (class, not trait; constructed with a `KernelBrowser` + FormFactory): `find(string $formTypeClass): Form`, `set(Form, string $path, mixed $value)` (text, textarea, select incl. injecting options, checkbox, radio, file), `submit(Form, ?string $button): FormSubmitResult` with `isValid()`, `formErrors`, `fieldErrors[path][]`, response status. Detect ajax/processor forms the way symfony-forms renders them (network used the `form-ajax` CSS class and JSON `forms.<name>.errors.fields|constraints`).
2. **Fix the network bug** while porting: the ajax branch wrote errors into `$errors[$fieldName]` instead of `$errors['fields'][…]`, so ajax validation errors were never counted (failing ajax submits looked successful). Add a regression test.
3. `src/Testing/BruteForceFormTester.php`: port `checkBrutalizeForm()` — build the type with a recording builder, collect required children with a valid value per type (move `testingBuildValidBruteForceFieldValue` to an interface implemented by the package's input types), generate all incomplete combinations (`ArrayHelper::generateAllIncompleteCombinations` in network — check symfony-helpers/php-helpers), convert selects to free inputs, submit each, assert error count = missing required fields.
4. Optional `SearchFieldFiller` only if search fields live in this package; otherwise leave to the search package.
5. Tests with a fixture kernel: a form with 3 required fields (text, select, email) rendered both classic and ajax/processor; driver fills + submits OK; brute force yields 7 rejected combinations with correct error counts.

## Do not

- Do not port `debugWrite()` calls, the history/path helpers (they belong to symfony-testing) or the `App\…` imports.
- Do not make it a trait requiring the host test class to supply `find()`, `reload()`, `log()`: the driver must be usable from `symfony-scenario` handlers.
- Never run anything against `NETWORK/local/network`.

## Acceptance criteria

- Package tests above green; `FormDriver` used by at least one symfony-scenario handler test once that package exists.
