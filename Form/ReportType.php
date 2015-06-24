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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Chill\MainBundle\Form\Type\AppendScopeChoiceTypeTrait;
use Chill\MainBundle\Security\Authorization\AuthorizationHelper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Chill\MainBundle\Templating\TranslatableStringHelper;
use Doctrine\Common\Persistence\ObjectManager;

class ReportType extends AbstractType
{    
    use AppendScopeChoiceTypeTrait;
    
    /**
     *
     * @var AuthorizationHelper
     */
    protected $authorizationHelper;
    
    /**
     *
     * @var TranslatableStringHelper
     */
    protected $translatableStringHelper;
    
    /**
     *
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $om;
    
    /**
     *
     * @var \Chill\MainBundle\Entity\User
     */
    protected $user;
    
    public function __construct(AuthorizationHelper $helper,
        TokenStorageInterface $tokenStorage, 
        TranslatableStringHelper $translatableStringHelper,
        ObjectManager $om)
    {
        $this->authorizationHelper = $helper;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->translatableStringHelper = $translatableStringHelper;
        $this->om = $om;
    }
    
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user')
            ->add('date', 'date', 
                array('required' => true, 'widget' => 'single_text', 'format' => 'dd-MM-yyyy'))
            ->add('cFData', 'custom_field', 
                array('attr' => array('class' => 'cf-fields'), 
                   'group' => $options['cFGroup']))
        ;
        
        $this->appendScopeChoices($builder, $options['role'], $options['center'], 
                $this->user, $this->authorizationHelper, 
                $this->translatableStringHelper,
                $this->om);
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {       
        $resolver->setDefaults(array(
            'data_class' => 'Chill\ReportBundle\Entity\Report'
        ));

        $resolver->setRequired(array(
            'cFGroup',
        ));

        $resolver->setAllowedTypes(array(
            'cFGroup' => 'Chill\CustomFieldsBundle\Entity\CustomFieldsGroup',
        ));
        
        $this->appendScopeChoicesOptions($resolver);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'chill_reportbundle_report';
    }
}
