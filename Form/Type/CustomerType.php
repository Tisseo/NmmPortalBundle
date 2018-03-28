<?php

namespace CanalTP\NmmPortalBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Email;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use CanalTP\NmmPortalBundle\Form\DataTransformer\ApplicationToCustomerApplicationTransformer;
use CanalTP\NmmPortalBundle\Form\DataTransformer\ApplicationToCustomerApplicationTransformerWithToken;
use CanalTP\NmmPortalBundle\Entity\CustomerApplication;

/**
 * Description of CustomerType
 *
 * @author kevin
 */
class CustomerType extends \CanalTP\SamCoreBundle\Form\Type\CustomerType
{
    private $em = null;
    private $coverages = null;
    private $navitia = null;
    private $applicationsTransformer = null;
    private $applicationsTransformerWithToken = null;
    private $withTyr = false;

    /*
    public function __construct(
        EntityManager $em,
        $coverages,
        $navitia,
        ApplicationToCustomerApplicationTransformer $applicationsTransformer,
        ApplicationToCustomerApplicationTransformerWithToken $applicationsTransformerWithToken,
        $withTyr
    )
    {
        //$this->em = $em;
        $this->coverages = $coverages;
        $this->navitia = $navitia;
        $this->applicationsTransformer = $applicationsTransformer;
        $this->applicationsTransformerWithToken = $applicationsTransformerWithToken;
        $this->withTyr = $withTyr;
    }*/

    private function addApplicationsField(FormBuilderInterface $builder)
    {
        if ($this->withTyr)
        {
            $builder->add(
                'applications',
                EntityType::class,
                array(
                    'label' => 'customer.applications',
                    'multiple' => true,
                    'class' => 'CanalTPSamCoreBundle:Application',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('cli')
                            ->orderBy('cli.name', 'ASC');
                    },
                    'expanded' => true
                )
            )->addModelTransformer($this->applicationsTransformer);
        } else {
            $builder->add(
                'applications',
                CollectionType::class,
                array(
                    'label' => 'customer.applications',
                    'entry_type' => CustomerApplicationType::class
                )
            )->addModelTransformer($this->applicationsTransformerWithToken);
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $this->em = $options['init']['em'];
        $this->coverages = $options['init']['coverage'];
        $this->navitia = $options['init']['navitia'];
        $this->applicationsTransformer = $options['init']['applicationsTransformer'];
        $this->applicationsTransformerWithToken = $options['init']['applicationsTransformerWithToken'];
        $this->withTyr = $options['init']['withTyr'];

        //it's now in navitiaEntity
        $builder->remove('email');
        $builder->remove('name');

        $builder->add(
            'navitiaEntity',
            NavitiaEntityType::class,
            [
                'label' => 'customer.navitia',
                'init' => [
                    'coverages' => $this->coverages,
                    'navitia' => $this->navitia,
                    'withPerimeters' => true
                ],
            ]
        );

        $copyName = function (FormEvent $event) {
            $customer = $event->getData();
            $customer->setName($customer->getNavitiaEntity()->getName());
        };

        $builder->addEventListener(FormEvents::POST_SUBMIT, $copyName);

        $this->addApplicationsField($builder);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'CanalTP\NmmPortalBundle\Entity\Customer',
                'invalid_message' => 'Tyr error: Email is duplicated',
                'init' => []
            )
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix() {
        return 'customer';
    }
}
