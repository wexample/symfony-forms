<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wexample\SymfonyForms\EventSubscriber\FormRobotsResponseSubscriber;

/**
 * The submit endpoints, found by crawlers in every form's `action`, say
 * `noindex` on whatever they answer — the `405` of a GET first of all.
 */
class FormRobotsResponseTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->catchExceptions(true);
    }

    /**
     * What a crawler gets: refused before any route matches.
     */
    public function testAGetOnASubmitEndpointIsRefusedAndNotIndexed(): void
    {
        foreach ([
            '/_forms/submit/form-add_to_cart_form',
            '/_forms/submit/form-add_to_cart_form/entity/01a12142-fe03-75c2-9320-ff35473bc223',
        ] as $path) {
            $this->client->request('GET', $path);

            $this->assertResponseStatusCodeSame(405, $path);
            $this->assertResponseHeaderSame(
                FormRobotsResponseSubscriber::HEADER,
                FormRobotsResponseSubscriber::HEADER_VALUE,
                $path
            );
        }
    }

    /**
     * A POST reaches the route, whatever it answers, and says it too.
     */
    public function testAPostOnASubmitEndpointIsNotIndexed(): void
    {
        $this->client->request('POST', '/_forms/submit/form-unknown_form');

        $this->assertResponseHeaderSame(
            FormRobotsResponseSubscriber::HEADER,
            FormRobotsResponseSubscriber::HEADER_VALUE
        );
    }

    public function testAnyOtherAddressIsLeftAlone(): void
    {
        $this->client->request('GET', '/anything-else');

        $this->assertResponseNotHasHeader(FormRobotsResponseSubscriber::HEADER);
    }
}
