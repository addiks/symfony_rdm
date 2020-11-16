<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Tests\Symfony\FormType;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\CallbackTransformer;
use Addiks\RDMBundle\Tests\Hydration\ServiceExample;
use Addiks\RDMBundle\Symfony\FormType\ServiceFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

final class ServiceFormTypeTest extends TestCase
{

    /**
     * @var ServiceFormType
     */
    private $formType;

    /**
     * @var ContainerInterface
     */
    private $dependency;

    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->formType = new ServiceFormType($this->container);
    }

    /**
     * @test
     */
    public function shouldBuildForm()
    {
        /** @var FormBuilderInterface $builder */
        $builder = $this->createMock(FormBuilderInterface::class);

        /** @var ?CallbackTransformer $modelTransformer */
        $modelTransformer = null;

        $builder->expects($this->once())->method('addModelTransformer')->will($this->returnCallback(
            function ($calledModelTransformer) use (&$modelTransformer) {
                $modelTransformer = $calledModelTransformer;
            }
        ));

        $this->formType->buildForm($builder, [
            'choices' => [
                'a' => 'some_service_a',
                'b' => 'some_service_b',
                'c' => 'some_service_c',
            ]
        ]);

        $this->assertInstanceOf(CallbackTransformer::class, $modelTransformer);

        $someServiceA = new ServiceExample("lorem", 123);
        $someServiceB = new ServiceExample("ipsum", 456);

        $this->container->method('get')->will($this->returnValueMap([
            ['some_service_a', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $someServiceA],
            ['some_service_b', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $someServiceB],
            ['some_service_c', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $someServiceB],
        ]));

        /** @var string $expectedServiceId */
        $expectedServiceId = 'some_service_b';

        /** @var string $actualServiceId */
        $actualServiceId = $modelTransformer->transform($someServiceB);

        /** @var ServiceExample $expectedService */
        $expectedService = $someServiceA;

        /** @var ServiceExample $actualService */
        $actualService = $modelTransformer->reverseTransform('some_service_a');

        $this->assertEquals($expectedServiceId, $actualServiceId);
        $this->assertSame($expectedService, $actualService);
    }

    /**
     * @test
     */
    public function shouldBeChildOfChoiceType()
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }

    /**
     * @test
     */
    public function shouldHaveServiceBlockPrefix()
    {
        $this->assertEquals('service', $this->formType->getBlockPrefix());
    }

    /**
     * @test
     */
    public function shouldHaveAName()
    {
        $this->assertEquals('service', $this->formType->getName());
    }

    /**
     * @test
     */
    public function shouldSetDefaultOptions()
    {
        if (!interface_exists(OptionsResolverInterface::class)) {
            # This interface was removed in symfony 3, but this bundle aims to support 2.8 as well as 3.x,
            # so this interface may or may not exist depending on the version of symfony
            eval('
                namespace Symfony\Component\OptionsResolver;
                interface OptionsResolverInterface{
                    public function setDefaults(array $defaultValues);
                }
            ');
        }

        /** @var OptionsResolverInterface $resolver */
        $resolver = $this->createMock(OptionsResolverInterface::class);

        $resolver->expects($this->once())->method('setDefaults')->with($this->equalTo([
            'choices' => null,
        ]));

        $this->formType->setDefaultOptions($resolver);
    }

    /**
     * @test
     */
    public function shouldConfigureOptions()
    {
        /** @var OptionsResolver $resolver */
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())->method('setDefaults')->with($this->equalTo([
            'choices' => null,
        ]));

        $this->formType->configureOptions($resolver);
    }

    /**
     * @test
     */
    public function shouldBuildAndFinishView()
    {
        /** @var FormView $view */
        $view = $this->createMock(FormView::class);

        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);

        $this->formType->buildView($view, $form, []);
        $this->formType->finishView($view, $form, []);

        $this->assertTrue(true);
    }

}
