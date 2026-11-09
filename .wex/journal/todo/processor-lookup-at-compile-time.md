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
