<?php

/*
 * Chill is a suite of a modules, Chill is a software for social workers
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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Chill\ReportBundle\Entity\Report;
use Chill\MainBundle\DataFixtures\ORM\LoadUsers;
use Faker\Factory as FakerFactory;
use Chill\CustomFieldsBundle\Entity\CustomField;

/**
 * Load reports into DB
 *
 * @author Julien Fastré <julien.fastre@champs-libres.coop>
 */
class LoadReports extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;
    
    /**
     *
     * @var \Faker\Generator 
     */
    private $faker;
    
    public function __construct()
    {
        $this->faker = FakerFactory::create('fr_FR');
    }
    
    public function getOrder()
    {
        return 15002;
    }
    
    public function load(ObjectManager $manager)
    {
        
        
        $this->createExpected($manager);
        
        //create random 2 times, to allow multiple report on some people
        $this->createRandom($manager, 90);
        $this->createRandom($manager, 30);
        
        $manager->flush();
    }
    
    private function createRandom(ObjectManager $manager, $percentage)
    {
        $people = $this->getPeopleRandom($percentage);
        
        foreach ($people as $person) {
            //create a report, set logement or education report
            $report = (new Report())
                    ->setPerson($person)
                    ->setCFGroup(rand(0,10) > 5 ? 
                            $this->getReference('cf_group_report_logement') :
                            $this->getReference('cf_group_report_education')
                        );
            $this->fillReport($report);
            $manager->persist($report);
        }
    }
    
    private function createExpected(ObjectManager $manager)
    {
        $charline = $this->container->get('doctrine.orm.entity_manager')
                ->getRepository('ChillPersonBundle:Person')
                ->findOneBy(array('lastName' => 'Charline'))
                ;
        
        $report = (new Report())
                ->setPerson($charline)
                ->setCFGroup($this->getReference('cf_group_report_logement'))
                ->setDate(new \DateTime('2015-01-05'))
                ;
        $this->fillReport($report);
        
        $manager->persist($report);
    }
    
    private function getPeopleRandom($percentage)
    {
        $people = $this->container->get('doctrine.orm.entity_manager')
                ->getRepository('ChillPersonBundle:Person')
                ->findAll()
                ;
        
        //keep only a part ($percentage) of the people
        $selectedPeople = array();
        foreach($people as $person) {
            if (rand(0,100) < $percentage) {
                $selectedPeople[] = $person;
            }
        }
        
        return $selectedPeople;
    }
    
    private function fillReport(Report $report)
    {
        //setUser
        $report->setUser(
                $this->getReference(LoadUsers::$refs[array_rand(LoadUsers::$refs)])
                );
        
        //set date if null
        if ($report->getDate() === NULL) {
            //set date. 30% of the dates are 2015-05-01
            $expectedDate = new \DateTime('2015-01-05');
            if (rand(0,100) < 30) {
                $report->setDate($expectedDate);
            } else {
                $report->setDate($this->faker->dateTimeBetween('-1 year', 'now')
                        ->setTime(0, 0, 0));
            } 
        }
        
        //fill data
        $datas = array();
        foreach ($report->getCFGroup()->getCustomFields() as $field) {
            switch ($field->getType()) {
                case 'title' :
                    $datas[$field->getSlug()] = array();
                    break;
                case 'choice' :
                    $datas[$field->getSlug()] = $this->getRandomChoice($field);
                    break;
                case 'text' :
                    $datas[$field->getSlug()] = $this->faker->realText($field->getOptions()['maxLength']);
                    break;
            }
        }
        $report->setCFData($datas);
        
        return $report;
    }
    
    /**
     * pick a random choice
     * 
     * @param CustomField $field
     * @return string[]|string the array of slug if multiple, a single slug otherwise
     */
    private function getRandomChoice(CustomField $field) 
    {
        $choices = $field->getOptions()['choices'];
        $multiple = $field->getOptions()['multiple'];
        $other = $field->getOptions()['other'];
        
        //add other if allowed
        if($other) {
            $choices[] = array('slug' => '_other');
        }
        
        //initialize results
        $picked = array();
        
        if ($multiple) {
            $numberSelected = rand(1, count($choices) -1);
            for ($i = 0; $i < $numberSelected; $i++) {
                $picked[] = $this->pickChoice($choices);
            }
            
            if ($other) {
                $result = array("_other" => NULL, "_choices" => $picked);
                
                if (in_array('_other', $picked)) {
                    $result['_other'] = $this->faker->realText(70);
                }
                
                return $result;
            }

        } else {
            $picked = $this->pickChoice($choices);
            
            if ($other) {
                $result = array('_other' => NULL, '_choices' => $picked);
                
                if ($picked === '_other') {
                    $result['_other'] = $this->faker->realText(70);
                }
                
                return $result;
            }
        }
        
        
    }
    
    /**
     * pick a choice within a 'choices' options (for choice type)
     * 
     * @param array $choices
     * @return the slug of the selected choice
     */
    private function pickChoice(array $choices)
    {
        return $choices[array_rand($choices)]['slug'];
    }
    


}
