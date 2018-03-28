<?php

namespace CanalTP\NmmPortalBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Email;
use Doctrine\ORM\EntityRepository;
use CanalTP\SamCoreBundle\Form\DataTransformer\ApplicationToCustomerApplicationTransformer;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


/**
 * Description of CustomerType
 *
 * @author kevin
 */
class NavitiaEntityType extends AbstractType
{
    protected $coverages = null;
    protected $navitia = null;
    protected $withPerimeters = true;

    /*public function __construct($coverages, $navitia, $withPerimeters = true)
    {
        $this->coverages = $coverages;
        $this->navitia = $navitia;
        $this->withPerimeters = $withPerimeters;
    }*/

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->coverages = $options['init']['coverages'];
        $this->navitia = $options['init']['navitia'];
        $this->withPerimeters = $options['init']['withPerimeters'];

        $builder->add(
            'perimeters',
            CollectionType::class,
            array(
                'label' => 'customer.perimeters',
                //'entry_type' => new PerimeterType($this->coverages, $this->navitia, $this->withPerimeters),
                'entry_type' => PerimeterType::class,
                'entry_options' => ['init' => [
                        'coverages' => $this->coverages,
                        'navitia' => $this->navitia,
                        'withPerimeters' => $this->withPerimeters,
                    ]
                ],
                'prototype_name' => '__perimeter_id__',
                'allow_add' => true,
                'allow_delete' => true
            )
        );

        $builder->add(
            'email',
            EmailType::class,
            array(
                'label' => 'customer.email',
                'constraints' => array(
                    new Length(
                        array('max' => 255)
                    ),
                    new Email(array('checkMX' => true))
                )
            )
        );

        $builder->add(
            'name',
            TextType::class,
            array(
                'label' => 'customer.name',
                'constraints' => array(
                    new NotBlank(),
                    new Length(
                        array('max' => 255)
                    )
                )
            )
        );
        $purgeDuplicates = function (FormEvent $event) {
            $data = $event->getData();

            if (isset($data['perimeters'])) {
                $selectedPerims = [];

                foreach ($data['perimeters'] as $perimeter) {
                    $key = $perimeter['external_coverage_id'] . '_' . $perimeter['external_network_id'];

                    if (!array_key_exists($key, $selectedPerims)) {
                        $selectedPerims[$key] = $perimeter;
                    }
               }
               $data['perimeters'] = array_values($selectedPerims);
               $event->setData($data);
           }
        };

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $purgeDuplicates);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix() {
        return 'customer';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'CanalTP\NmmPortalBundle\Entity\NavitiaEntity',
                'init' => [],
            )
        );
    }
}
