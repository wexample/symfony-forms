<?php

namespace Wexample\SymfonyForms\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Wexample\SymfonyForms\Controller\FormController;

/**
 * Keeps the submit endpoints out of search indexes.
 *
 * Every rendered form carries one of them in its `action`, so crawlers find
 * them and open them in GET. They answer `405`, and only the HTML error page
 * says `noindex`; the JSON a POST receives says nothing. The header says it on
 * every response, whatever its format.
 *
 * Not robots.txt: a `Disallow` would stop the crawler before it reads the
 * `405` and the `noindex`, and an address found through a link can then be
 * listed "indexed, though blocked by robots.txt".
 */
class FormRobotsResponseSubscriber implements EventSubscriberInterface
{
    public const string HEADER = 'X-Robots-Tag';
    public const string HEADER_VALUE = 'noindex, nofollow';

    private const array ROUTES = [
        FormController::ROUTE_NAME_PREFIX . FormController::ROUTE_PROCESSOR_SUBMIT,
        FormController::ROUTE_NAME_PREFIX . FormController::ROUTE_ENTITY_FORM_PROCESSOR_SUBMIT,
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if ($response->headers->has(self::HEADER) || ! $this->isSubmitRequest($event->getRequest())) {
            return;
        }

        $response->headers->set(self::HEADER, self::HEADER_VALUE);
    }

    /**
     * By route name, which holds under any prefix an application imports the
     * routes with; and by path, because a GET is refused before any route
     * matches and its `405` carries no route name.
     */
    private function isSubmitRequest(Request $request): bool
    {
        return in_array($request->attributes->get('_route'), self::ROUTES, true)
            || str_starts_with($request->getPathInfo(), FormController::PATH_PREFIX);
    }
}
