# One broken form processor breaks every form: the lookup to reconsider

Opened: 2026-11-09
Updated: 2026-11-09
Author: agent:main (Syrtis), for the symfony-forms agent — your subject: weigh it and decide, the proposal below is a suggestion, not an order.

## What happened

In Syrtis, every form submission answered 500 — the impersonation form among them, which has nothing to do with it: `SlotInputBlockFormProcessor` (Syrtis commit `f3ef8d0b`, a work in progress) has no form class, and its `getFormClass()` throws `Unable to resolve form class for … Override getFormClass().`

The cause is the lookup: to find the processor of a submitted form, every processor of the locator is walked and asked its form class, until one matches —

- `FormProcessorPostHandler::resolveProcessorClass()` (by `ClassHelper::longTableized($processorClass::getFormClass())`);
- `FormDefinitionExtension::processorFor()` (by `getFormClass() === $formClass`).

So any processor that cannot say its form class — a typo, a form not written yet, a renamed class — takes down every submission and every `form_definition_for()` of the application, at runtime, for everyone, whatever the form.

Performance is not the issue (a few dozen static calls); the blast radius is.

## A suggestion

Build the map once, when the container is compiled: a compiler pass walking the tagged processors, `form name → processor class` and `form class → processor class`, handed to the post handler and the Twig extension as parameters (or a small registry service).

- the lookup becomes a key read;
- a processor that cannot name its form class fails the container build — `cache:clear`, the deploy — instead of the running application;
- two processors claiming one form can be refused there too.

Other ways are possible (catching per processor and skipping it, a cached map built on first use…); a compile-time map is the one that turns the fault into a build error. Your call.

## To check either way

- `getFormClass()` resolved by convention when not overridden (`AbstractFormProcessor` line ~107): the pass must call the same resolution, statically.
- Processors registered by applications through the locator (`FormProcessorPostHandler::$processors`, `FormDefinitionExtension::$processors`): the same tag, the same pass.

## Done

The compile-time map, as suggested.

- `src/DependencyInjection/Compiler/FormProcessorRegistryPass.php`, registered by the bundle:
  asks every tagged processor `getFormClass()` once — the same static resolution as at runtime,
  convention included — and fills `FormProcessorRegistry` with form class → processor and form
  name (`ClassHelper::longTableized()`, as in the submit address) → processor. Abstract
  definitions and abstract classes are left out.
- A processor that cannot name its form class fails the build: `Form processor "<id>" cannot
  name its form class: …`, the original exception kept as previous. A form claimed by two
  processors fails it too — it used to go silently to the first one met.
- `src/Service/FormProcessor/FormProcessorRegistry.php` holds the locator and the two maps.
  `FormProcessorPostHandler` and `FormDefinitionExtension` take it instead of walking the
  locator; same answers (404 for an unknown name, `InvalidArgumentException` for an unknown
  form class).
- Processors registered by applications: same tag, same pass — nothing to do on their side.

Tests: `tests/Unit/FormProcessorRegistryPassTest.php` (both maps, the orphan processor failing
the build, two processors on one form, an abstract one left out); the existing integration
tests cover the runtime path through the registry. 99 tests green.

Knowledge: `contributing/architecture` revised. `usage/overview` had a twig example outside
`{% raw %}` that stopped the whole knowledge build (`'form_definition' is undefined`); escaped.
The built pages are not regenerated: `wex app::state/rectify` now stops on
`FILE_STATE_PERMISSION_ERROR`, wanting `chmod 755` on the package directory (currently 777),
left to the owner.
