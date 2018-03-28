<?php

namespace CanalTP\NmmPortalBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Email;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use CanalTP\NmmPortalBundle\Form\DataTransformer\ApplicationToCustomerApplicationTransformer;

/**
 * Description of CustomerType
 *
 * @author kevin
 */
class CustomerApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'isActive',
            'checkbox',
            array(
                'required'  => false
            )
        );

        $builder->add(
            'token',
            TextType::class,
            array(
                'required'  => false
            )
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix() {
        return 'customer_application';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'CanalTP\NmmPortalBundle\Entity\CustomerApplication'
            )
        );
    }
}
