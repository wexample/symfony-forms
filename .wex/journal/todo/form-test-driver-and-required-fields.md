# Form test driver and required-field brute force

Opened: 2026-09-24
Updated: 2026-10-12
Author: agent:main

Not to start yet: it pays off once apps have real business forms to cover (accounting, cart). Discuss the shape with the owner before coding.

## Why

The scenarios of symfony-testing already fill and send a form (`do: { submit: { button, fill } }`, `ScenarioContext::submit()`), but when the answer is a failure they cannot say which field failed, and forms sent through the processor endpoints (AJAX, JSON answer) are not read. Nothing checks that every required field is really required server-side.

## What

1. **Driver** in `src/Testing/` (test env only, a class, not a trait): from a `KernelBrowser`, find a form by FormType class or button, set any field (text, select with injected options, checkbox, radio, file), submit it classically or through the processor endpoint, and return a result: `isValid()`, form errors, errors per field path, status. Read the JSON errors of processor answers (`fields` / `constraints`) and the classic rendered errors alike. Regression test: an invalid AJAX submission must be reported invalid (network counted those errors in the wrong key and saw them as successes).
2. **Required-field brute force (#315)**: for a FormType, collect its required children and a valid value per field type (an interface the package input types implement), submit every incomplete combination (2^n − 1), assert each is rejected with exactly the missing fields in error.
3. **Consumers**: `ScenarioContext::submit()` reports the driver's per-field errors when an expected success fails; an opt-in `explore` setting (`forms: brute_force`) runs step 2 on every form the role exploration meets.
4. Tests on a fixture kernel: one form with 3 required fields (text, select, email), rendered classic and processor → 7 rejected combinations.

## References (read only)

The 2022 network version, as a source of ideas, not to port as is: `NETWORK/archeo/trees/develop-131-fos-user/src/Wex/BaseBundle/Tests/Traits/FormTestCaseTrait.php` (`checkBrutalizeForm`, `formSubmit`), `src/Class/Test/TestFormBuilder.php`, `testingBuildValidBruteForceFieldValue` in `src/Wex/BaseBundle/Form/Traits/DefaultTypeTrait.php`. Issues #315, #243, #295 in `NETWORK/archeo/gitlab/issues/`. Never run anything against `NETWORK/local/network`.
