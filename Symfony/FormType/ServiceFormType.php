<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\RDMBundle\Symfony\FormType;

use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormConfigBuilderInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ServiceFormType implements FormTypeInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($builder instanceof FormConfigBuilderInterface) {
            /** @var ContainerInterface $container */
            $container = $this->container;

            $builder->addModelTransformer(new CallbackTransformer(
                /**
                 * @param object $service
                 */
                function ($service) use ($container, $options): ?string {
                    /** @var ?string $serviceId */
                    $serviceId = null;

                    if (isset($options['choices'])) {
                        foreach (array_keys($options['choices']) as $serviceIdCandidate) {
                            /** @var object $serviceCandidate */
                            $serviceCandidate = $container->get($serviceIdCandidate);

                            if ($serviceCandidate === $service) {
                                $serviceId = $serviceIdCandidate;
                                break;
                            }
                        }
                    }

                    return $serviceId;
                },
                /**
                 * @return ?object
                 */
                function (string $serviceId) use ($container) {
                    /** @var ?object $service */
                    $service = null;

                    if (!empty($serviceId)) {
                        $service = $container->get($serviceId);
                    }

                    return $service;
                }
            ));
        }

    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'choices' => null,
        ));
    }

    /**
     * @psalm-suppress UndefinedClass
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults(array(
            'choices' => null,
        ));
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getName(): string
    {
        return 'service';
    }

}
