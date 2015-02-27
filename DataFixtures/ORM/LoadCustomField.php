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

namespace Chill\ReportBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Chill\CustomFieldsBundle\Entity\CustomField;

/**
 * Load CustomField for Report into database
 */
class LoadCustomField extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 15001;
    }
    
    public function load(ObjectManager $manager)
    {
        echo "loading CustomField...\n";

        $cFTypes = [
            array('type' => 'text', 'options' => array('maxLength' => '255')),
            array('type' => 'text', 'options' => array('maxLength' => '1000')),
            array('type' => 'text', 'options' => array('maxLength' => '2000')),
            array('type' => 'title', 'options' => array('type' => 'title')),
            array('type' => 'title', 'options' => array('type' => 'subtitle')),
            array('type' => 'choice', 'options' => array(
                'multiple' => false,
                'expanded'=> false,
                'other' => false,
                'choices'=> [
                    array(
                        'name' => array(
                            'fr' => 'Options 1 FR',
                            'nl' => 'Options 1 NL',
                            'en' => 'Options 1 EN'),
                        'active' => true,
                        'slug' => 'el-1-fr'),
                    array(
                        'name' => array(
                            'fr' => 'Options 2 FR',
                            'nl' => 'Options 2 NL',
                            'en' => 'Options 2 EN'),
                        'active' => true,
                        'slug' => 'el-2-fr'),
                    array(
                        'name' => array(
                            'fr' => 'Options 2 FR',
                            'nl' => 'Options 2 NL',
                            'en' => 'Options 2 EN'),
                        'active' => true,
                        'slug' => 'el-3-fr')
                    ]
                )
            )
        ];

        for($i=0; $i <= 25; $i++) {
            echo "CustomField {$i}\n";
            $cFType = $cFTypes[rand(0,sizeof($cFTypes) - 1)];

            $customField = (new CustomField())
                ->setSlug("cf_report_{$i}")
                ->setType($cFType['type'])
                ->setOptions($cFType['options'])
                ->setName(array("fr" => "CustomField {$i}"))
                ->setOrdering(rand(0,1000) / 1000)
                ->setCustomFieldsGroup($this->getReference('cf_group_report_'.(rand(0,3))))
            ;

            $manager->persist($customField);
        }
        
        $this->createExpectedFields($manager);
        
        $manager->flush();
    }
    
    private function createExpectedFields(ObjectManager $manager)
    {
        //report logement
        $reportLogement = $this->getReference('cf_group_report_logement');
        
        $houseTitle = (new CustomField())
                ->setSlug('house_title')
                ->setType('title')
                ->setOptions(array('type' => 'title'))
                ->setName(array('fr' => 'Situation de logement'))
                ->setOrdering(10)
                ->setCustomFieldsGroup($reportLogement)
                ;
        $manager->persist($houseTitle);
        
        $hasLogement = (new CustomField())
                ->setSlug('has_logement')
                ->setName(array('fr' => 'Logement actuel'))
                ->setType('choice')
                ->setOptions(array(
                    'multiple' => FALSE,
                    'expanded' => TRUE,
                    'other' => TRUE,
                    'choices' => [
                        array(
                            'name' => ['fr' => 'Locataire d\' un logement'],
                            'slug' => 'rent_house',
                            'active' => true
                        ),
                        array(
                            'name' => ['fr' => 'Propriétaire d\' un logement'],
                            'slug' => 'own_house',
                            'active' => true
                        ),
                        array(
                            'name' => ['fr' => 'Par-ci, par là (amis, famille, ...)'],
                            'slug' => 'here-and-there',
                            'active' => true
                        ),
                        array(
                            'name' => ['fr' => 'A la rue'],
                            'slug' => 'street',
                            'active' => true
                        )
                    ]
                    
                ))
                ->setOrdering(20)
                ->setCustomFieldsGroup($reportLogement)
                ;
        $manager->persist($hasLogement);
        
        $descriptionLogement = (new CustomField())
                ->setSlug('house-desc')
                ->setName(array('fr' => 'Plaintes éventuelles sur le logement'))
                ->setType('text')
                ->setOptions(['maxLength' => 1500])
                ->setOrdering(30)
                ->setCustomFieldsGroup($reportLogement)
                ;
        $manager->persist($descriptionLogement);
        
        
        //report problems
        $reportEducation = $this->getReference('cf_group_report_education');
        
        $title = (new CustomField())
                ->setSlug('title')
                ->setType('title')
                ->setOptions(array('type' => 'title'))
                ->setName(array('fr' => 'Éducation'))
                ->setOrdering(10)
                ->setCustomFieldsGroup($reportEducation)
                ;
        $manager->persist($title);
        
        $educationLevel = (new CustomField())
                ->setSlug('level')
                ->setName(array('fr' => 'Niveau du plus haut diplôme'))
                ->setType('choice')
                ->setOptions(array(
                    'multiple' => FALSE,
                    'expanded' => FALSE,
                    'other' => FALSE,
                    'choices' => [
                        array(
                            'name' => ['fr' => 'Supérieur'],
                            'slug' => 'superieur',
                            'active' => true
                        ),
                        array(
                            'name' => ['fr' => 'Secondaire supérieur (CESS)'],
                            'slug' => 'cess',
                            'active' => true
                        ),
                        array(
                            'name' => ['fr' => 'Secondaire deuxième degré ou inférieur (C2D)'],
                            'slug' => 'c2d',
                            'active' => true
                        ),
                        array(
                            'name' => ['fr' => 'Primaire'],
                            'slug' => 'low',
                            'active' => true
                        ),
                        array(
                            'name' => ['fr' => 'Aucun diplome'],
                            'slug' => 'no',
                            'active' => true
                        )
                    ]
                    
                ))
                ->setOrdering(20)
                ->setCustomFieldsGroup($reportEducation)
                ;
        $manager->persist($educationLevel);
        
        
        
    }
}