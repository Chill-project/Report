<?php

/*
 * Chill is a software for social workers
 * Copyright (C) 2015 Champs Libres <info@champs-libres.coop>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Chill\ReportBundle\Tests\Timeline;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Chill\PersonBundle\Entity\Person;
use Chill\ReportBundle\Entity\Report;
use Chill\MainBundle\Tests\TestHelper as MainTestHelper;

/**
 * Test a report is shown into timeline
 *
 * @author Julien Fastré <julien.fastre@champs-libres.coop>
 * @author Champs Libres <info@champs-libres.coop>
 */
class TimelineProviderTest extends WebTestCase
{
    
    /**
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private static $em;
    
    /**
     *
     * @var Person
     */
    private $person;
    
    /**
     *
     * @var Report
     */
    private $report;
    
    /**
     * Create a person with a report associated with the person
     */
    public function setUp()
    {
        static::bootKernel();
        
        static::$em = static::$kernel->getContainer()
              ->get('doctrine.orm.entity_manager');
        
        $center = static::$em->getRepository('ChillMainBundle:Center')
              ->findOneBy(array('name' => 'Center A'));
        
        $this->person = (new Person(new \DateTime('2015-05-01')))
          ->setGenre(Person::GENRE_WOMAN)
          ->setFirstName('Nelson')
          ->setLastName('Mandela')
          ->setCenter($center);
        static::$em->persist($this->person);
        
        $this->report = (new Report)
              ->setUser(static::$em->getRepository('ChillMainBundle:User')
                    ->findOneByUsername('center b_social'))
              ->setDate(new \DateTime('2015-05-02'))
              ->setPerson($this->person)
              ->setCFGroup($this->getHousingCustomFieldsGroup())
              ->setCFData(['has_logement' => 'own_house', 
           'house-desc' => 'blah blah']);
        
        static::$em->persist($this->report);
        
        
        
        static::$em->flush();
        
    }
    
    /**
     * Test that a report is shown in timeline
     */
    public function testTimelineReport()
    {
        $client = static::createClient(array(),
              MainTestHelper::getAuthenticatedClientOptions()
              );
        
        $crawler = $client->request('GET', '/fr/person/'.$this->person->getId()
              .'/timeline');
        
        $this->assertTrue($client->getResponse()->isSuccessful(),
              'The page timeline is loaded successfully');
        $this->assertContains('a ajouté un rapport', $crawler->text(),
              'the page contains the text "a publié un rapport"');
    }
    
    public function testTimelineReportWithSummaryField()
    {
        //load the page
        $client = static::createClient(array(),
              MainTestHelper::getAuthenticatedClientOptions()
              );
        
        $crawler = $client->request('GET', '/fr/person/'.$this->person->getId()
              .'/timeline');
        
        //performs tests
        $this->assertTrue($client->getResponse()->isSuccessful(),
              'The page timeline is loaded successfully');
        $this->assertGreaterThan(0, $crawler->filter('.report .summary')
              ->count(), 
              'the page contains a .report .summary element');
        $this->assertContains('blah blah', $crawler->filter('.report .summary')
              ->text(),
              'the page contains the text "blah blah"');
        $this->assertContains('Propriétaire', $crawler->filter('.report .summary')
              ->text(),
              'the page contains the mention "Propriétaire"');
    }
    
    /**
     * get a random custom fields group
     * 
     * @return \Chill\CustomFieldsBundle\Entity\CustomFieldsGroup
     */
    private function getHousingCustomFieldsGroup()
    {
        $groups = static::$em
              ->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')
              ->findAll();
        
        foreach ($groups as $group) {
            if ($group->getName()['fr'] === 'Situation de logement') {
                return $group;
            }
        }
        
        return $groups[rand(0, count($groups) -1)];
    }
    
    
    
    public function tearDown()
    {
        //static::$em->remove($this->person);
        //static::$em->remove($this->report);
    }
}
