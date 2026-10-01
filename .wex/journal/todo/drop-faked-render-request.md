# Drop the faked request in FrozenFieldRenderTest

Opened: 2026-10-01
Updated: 2026-10-01
Author: agent:sapiens

## Context

`symfony-loader` commit `92ff57c` lets `AdaptiveRendererService::createRenderPass()` run with an empty request stack. `tests/Integration/FrozenFieldRenderTest.php` (around lines 199–210) pushes a request and sets `AdaptiveRequestHelper::REQUEST_ATTR_OUTPUT_TYPE` by hand only to work around the old failure.

## Task

Once this package requires a `symfony-loader` release carrying `92ff57c`, remove the faked request and attribute from the test. Low priority, cleanup only.
