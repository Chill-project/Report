<?php

namespace Chill\ReportBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Chill\CustomFieldsBundle\Form\DataTransformer\CustomFieldsGroupToIdTransformer;

class ReportType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $options['em'];
        $transformer = new CustomFieldsGroupToIdTransformer($entityManager);

        $builder
            ->add('date')
            ->add('scope')
            ->add('cFData', 'custom_field', array('group' => $options['cFGroup']))
            ->add(
                $builder->create('cFGroup', 'text')
                ->addModelTransformer($transformer)
            )
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

        $resolver->setRequired(array(
            'em',
            'cFGroup',
        ));

        $resolver->setAllowedTypes(array(
            'em' => 'Doctrine\Common\Persistence\ObjectManager',
            'cFGroup' => 'Chill\CustomFieldsBundle\Entity\CustomFieldsGroup'
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
