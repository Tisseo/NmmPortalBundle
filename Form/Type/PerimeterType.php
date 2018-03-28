<?php

namespace CanalTP\NmmPortalBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Doctrine\ORM\EntityRepository;

/**
 * Description of PerimeterType
 *
 * @author rabikhalil
 */
class PerimeterType extends AbstractType
{
    private $coverages = array();
    private $navitia = null;
    protected $withPerimeters = true;

    /*public function __construct($coverages, $navitia, $withPerimeters = true)
    {
        $this->navitia = $navitia;
        $this->withPerimeters = $withPerimeters;

        $this->fetchCoverages($coverages);
    }*/

    private function fetchCoverages($coverages)
    {
        foreach ($coverages as $coverage) {
            $this->coverages[$coverage->id] = $coverage->id;
        }
        asort($this->coverages);
        $this->coverages = array_merge(['' => 'global.please_choose'], $this->coverages);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->navitia = $options['init']['navitia'];
        $this->withPerimeters = $options['init']['withPerimeters'];
        $this->fetchCoverages($options['init']['coverages']);

        $builder->add(
            'external_coverage_id',
            ChoiceType::class,
            [
                'choices' => $this->coverages,
                'choices_as_values' => true,
                'choice_name' => function ($val, $key) {
                    return $key;
                },
                'choice_value' => function ($val) {
                    return $val;
                },

            ]
        );

        if ($this->withPerimeters) {
            $builder->add(
                'external_network_id',
                ChoiceType::class,
                [
                    'choices' => ['global.please_choose' => ''],
                    'choices_as_values' => true,
                    /*'choice_name' => function ($val, $key) {
                        return $key;
                    },
                    'choice_value' => function ($val) {
                        return $val;
                    },
                    'choice_label' => function ($val) {
                        return $val;
                    },*/
                ]
            );
        }

        $formFactory = $builder->getFormFactory();
        $callback = function (FormEvent $event) use ($formFactory) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!is_null($data) && $this->withPerimeters) {
                $externalCoverageId = (is_array($data) ?
                    $data['external_coverage_id'] : $data->getExternalCoverageId());

                $externalNetworkId = (is_array($data) ?
                    $data['external_network_id'] : $data->getExternalNetworkId());

                // Enable edit customer even with a non existent coverage
                if (in_array($externalCoverageId, $this->coverages)) {
                    $form->remove('external_network_id');
                    $networks = $this->navitia->getNetWorks($externalCoverageId);
                    asort($networks);
                    $networks = array_flip(array_merge(['' => 'global.please_choose'], $networks));

                    $form->add(
                        $formFactory->createNamed(
                            'external_network_id',
                            ChoiceType::class,
                            $externalNetworkId,
                            array(
                                'auto_initialize' => false,
                                'choices' => $networks,
                                'choices_as_values' => true,
                            )
                        )
                    );
                    if (false === array_search($externalNetworkId, $networks)) {
                        // Add message on old network selected
                        $form->get('external_network_id')->addError(
                            new FormError('customer.network.undefined', null, ['%network%' => $externalNetworkId])
                        );
                    }
                } else {
                    // Add message on old coverage selected
                    $form->get('external_coverage_id')->addError(
                        new FormError('customer.coverage.undefined', null, ['%coverage%' => $externalCoverageId])
                    );
                }
            }
        };

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $callback);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $callback);
    }

    public function getName()
    {
       return $this->getBlockPrefix();
    }

    public function getBlockPrefix() {
        return 'perimeter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'CanalTP\NmmPortalBundle\Entity\Perimeter',
                'init' => []
            )
        );
    }
}
