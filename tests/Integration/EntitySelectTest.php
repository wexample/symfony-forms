<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Wexample\SymfonyForms\Form\Type\EntitySelectInputType;
use Wexample\SymfonyForms\Tests\Fixtures\App\Entity\Team;
use Wexample\SymfonyForms\Tests\Traits\RendersFormsTrait;

/**
 * Every row of a short table, offered at once as a select.
 */
class EntitySelectTest extends KernelTestCase
{
    use RendersFormsTrait;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        (new SchemaTool($this->entityManager))->createSchema([$this->entityManager->getClassMetadata(Team::class)]);

        foreach (['Editorial', 'Studio', 'Sales'] as $name) {
            $this->entityManager->persist(new Team($name));
        }
        $this->entityManager->flush();
    }

    public function testTheRowsAreTheOptionsAndOneIsSubmitted(): void
    {
        $form = $this->form();
        $html = $this->renderForm($form);

        foreach (['Editorial', 'Studio', 'Sales'] as $name) {
            $this->assertStringContainsString($name, $html);
        }
        $this->assertStringContainsString('select', $html);

        $studio = $this->entityManager->getRepository(Team::class)->findOneBy(['name' => 'Studio']);
        $form->submit(['team' => (string) $studio->id]);

        $this->assertSame($studio, $form->get('team')->getData());
    }

    private function form()
    {
        return self::getContainer()->get('form.factory')
            ->createNamedBuilder('assignment', FormType::class)
            ->add('team', EntitySelectInputType::class, ['class' => Team::class, 'label' => false])
            ->getForm();
    }
}
