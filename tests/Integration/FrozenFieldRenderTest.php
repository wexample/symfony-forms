<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Wexample\SymfonyForms\Tests\Fixtures\App\Form\RecordForm;
use Wexample\SymfonyForms\Tests\Fixtures\App\Model\Record;
use Wexample\SymfonyLoader\Helper\AdaptiveRequestHelper;
use Wexample\SymfonyLoader\Rendering\RenderNode\InitialLayoutRenderNode;
use Wexample\SymfonyLoader\Rendering\RenderPass;
use Wexample\SymfonyLoader\Service\AdaptiveRendererService;

/**
 * How a frozen field looks, which is the half the server does not settle.
 *
 * `readonly` and not `disabled`: a greyed control leaves the tab order and is
 * skipped by most screen readers, which IEC 62366 is read against, and says
 * "unavailable" where the truth is "settled". The controls HTML has no
 * `readonly` for — a select, a radio group, a switch, a file field — are shown
 * as their own value instead, in a field carrying no `name` at all.
 */
class FrozenFieldRenderTest extends KernelTestCase
{
    private const string VIEW = 'test';

    /**
     * The fields HTML can freeze in place, which keep their own element.
     */
    private const array READONLY_FIELDS = [
        'name',
        'email',
        'site',
        'notes',
        'password',
        'weight',
        'count',
        'dateBorn',
        'dateSeen',
        'timeSeen',
    ];

    /**
     * The controls with no `readonly` in HTML, and what each says once frozen.
     */
    private const array FROZEN_VALUES = [
        'status' => 'Active',
        'kind' => 'First',
        'code' => '123456',
        'mood' => ':-)',
        'scan' => 'scan.pdf',
    ];

    public function testEveryFrozenFieldIsReadonlyAndNoneIsDisabled(): void
    {
        $html = $this->render(true);

        foreach (array_merge(self::READONLY_FIELDS, array_keys(self::FROZEN_VALUES), ['active']) as $field) {
            $tag = $this->tag($html, $field);

            $this->assertStringContainsString('readonly', $tag, $field);
            $this->assertStringContainsString('aria-readonly="true"', $tag, $field);
            $this->assertStringNotContainsString('disabled', $tag, $field);
        }
    }

    /**
     * A frozen field that is also empty must not hold the form shut: a
     * `required` the reader cannot satisfy would make the browser refuse every
     * submission.
     */
    public function testAFrozenFieldIsNeverRequired(): void
    {
        $html = $this->render(true);

        foreach (self::READONLY_FIELDS as $field) {
            $this->assertStringNotContainsString('required', $this->tag($html, $field), $field);
        }
    }

    public function testAFieldThatIsNotFrozenCarriesNoneOfIt(): void
    {
        $html = $this->render(false);

        foreach (self::READONLY_FIELDS as $field) {
            $this->assertStringNotContainsString('readonly', $this->tag($html, $field), $field);
        }
    }

    /**
     * A select, a radio group, a switch, a file field: shown as the value they
     * hold, and submitting nothing — there is no `name` for a post to carry.
     */
    public function testAControlHtmlCannotFreezeIsShownAsItsValueAndSubmitsNothing(): void
    {
        $html = $this->render(true);

        foreach (self::FROZEN_VALUES as $field => $expected) {
            $tag = $this->tag($html, $field);

            $this->assertStringContainsString('value="' . $expected . '"', $tag, $field);
            $this->assertStringNotContainsString('name=', $tag, $field);
        }
    }

    /**
     * A switch has no choices to read a label from, so it reads the two keys a
     * radio group would have had — the application names the states.
     */
    public function testAFrozenSwitchSaysWhichStateItIsIn(): void
    {
        $tag = $this->tag($this->render(true), 'active');

        $this->assertStringContainsString('@form::field.active.choice.1.label', $tag);
        $this->assertStringNotContainsString('name=', $tag);
    }

    /**
     * The second need, and the one a read-only field does not cover: a value
     * shown among the fields which is not one. No element a browser submits,
     * so nothing of it appears in a post.
     */
    public function testADisplayValueRendersNoInputAtAll(): void
    {
        $html = $this->render(false);

        $this->assertStringContainsString('12 March 2026', $html);
        $this->assertStringNotContainsString('record_form[dateCreated]', $html);
        $this->assertStringNotContainsString('record_form[dateActivated]', $html);
    }

    /**
     * An empty display value is said with a dash: a blank space reads as
     * something missing from the page, a dash as something missing from the
     * thing described.
     */
    public function testAnEmptyDisplayValueIsSaidWithADash(): void
    {
        $html = $this->render(false);

        $this->assertMatchesRegularExpression(
            '/form--display-value--empty[^>]*>\s*(&mdash;|—)/',
            $html
        );
    }

    /**
     * The element carrying a field's id, whatever its tag.
     */
    private function tag(
        string $html,
        string $field
    ): string {
        $id = 'record_form_' . $field;

        $this->assertMatchesRegularExpression(
            '/<[a-z]+[^>]*\bid="' . preg_quote($id, '/') . '"[^>]*>/',
            $html,
            'No element carries the id ' . $id
        );

        preg_match('/<[a-z]+[^>]*\bid="' . preg_quote($id, '/') . '"[^>]*>/', $html, $matches);

        return $matches[0];
    }

    private function render(bool $frozen): string
    {
        self::bootKernel();
        $container = self::getContainer();

        $twig = $container->get('twig');
        // What `AdaptiveRendererService` does on a real page: the components
        // read the render pass as a twig global, not as a form variable, and
        // they write into the layout node it opens.
        $twig->addGlobal('render_pass', $this->renderPass());

        $form = $container
            ->get('form.factory')
            ->create(RecordForm::class, $this->record(), ['frozen' => $frozen]);

        return $twig
            ->createTemplate('{{ form_widget(form) }}')
            ->render(['form' => $form->createView()]);
    }

    /**
     * A render pass built the way a page builds one, so the components are
     * given the registry, the usages and the layout node they read.
     */
    private function renderPass(): RenderPass
    {
        $container = self::getContainer();

        // A render pass reads the output type off the current request, where a
        // page gets it from the adaptive response subscriber.
        $request = Request::create('/');
        $request->attributes->set(
            AdaptiveRequestHelper::REQUEST_ATTR_OUTPUT_TYPE,
            RenderPass::OUTPUT_TYPE_RESPONSE_HTML
        );
        $request->attributes->set(
            AdaptiveRequestHelper::REQUEST_ATTR_LAYOUT_BASE,
            RenderPass::BASE_DEFAULT
        );
        $container->get('request_stack')->push($request);

        $renderPass = $container
            ->get(AdaptiveRendererService::class)
            ->createRenderPass(self::VIEW);

        $layout = new InitialLayoutRenderNode('test');
        $renderPass->setLayoutRenderNode($layout);
        $layout->init($renderPass, self::VIEW);

        return $renderPass;
    }

    private function record(): Record
    {
        $record = new Record();
        $record->name = 'Jane';
        $record->email = 'jane@example.com';
        $record->site = 'https://jane.example.com';
        $record->notes = 'Seen in March.';
        $record->weight = 62.4;
        $record->count = 3.0;
        $record->dateBorn = new \DateTime('1981-07-02');
        $record->dateSeen = new \DateTime('2026-03-12 09:30');
        $record->timeSeen = new \DateTime('2026-03-12 09:30');
        $record->status = 'active';
        $record->kind = 'first';
        $record->active = true;
        $record->code = '123456';
        $record->mood = ':-)';

        return $record;
    }
}
