<?php

namespace Chill\ReportBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ReportType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user')
            ->add('person')
            ->add('date')
            ->add('scope')
            ->add('cFData')
            ->add('cFGroup')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Chill\ReportBundle\Entity\Report'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'chill_reportbundle_report';
    }
}
