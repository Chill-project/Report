<?php

/*
 * Chill is a software for social workers 
 *
 * Copyright (C) 2014, Champs Libres Cooperative SCRLFS, <http://www.champs-libres.coop>
 * 
 * This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
        $entityManager = $options['em'];

        $builder
            ->add('user')
            ->add('date', 'date', 
                array('required' => false, 'widget' => 'single_text', 'format' => 'dd-MM-yyyy'))
            //->add('scope')
            ->add('cFData', 'custom_field', 
                array('attr' => array('class' => 'only-label'), 'group' => $options['cFGroup']))
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
