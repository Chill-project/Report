<?php

/*
 * Copyright (C) 2015 Julien Fastré <julien.fastre@champs-libres.coop>
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

namespace Chill\ReportBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Chill\PersonBundle\Entity\Person;
use Chill\CustomFieldsBundle\Entity\CustomFieldsGroup;
use Symfony\Component\BrowserKit\Client;

/**
 * This class is much well writtend than ReportControllerTest class, and will
 * replace ReportControllerTest in the future.
 *
 * @author Julien Fastré <julien.fastre@champs-libres.coop>
 */
class ReportControllerNextTest extends WebTestCase
{
    /**
     *
     * @var Person
     */
    protected $person;
    
    /**
     *
     * @var CustomFieldsGroup
     */
    protected $group;
    
    
    public function setUp()
    {
        static::bootKernel();
        // get person from fixture
        $em = static::$kernel->getContainer()
              ->get('doctrine.orm.entity_manager');
        
        $this->person = $em
              ->getRepository('ChillPersonBundle:Person')
              ->findOneBy(array(
                  'lastName' => 'Charline',
                  'firstName'  => 'Depardieu'
                  )
                );
        
        if ($this->person === NULL) {
            throw new \RuntimeException("The expected person is not present in the database. "
                    . "Did you run `php app/console doctrine:fixture:load` before launching tests ? "
                    . "(expecting person is 'Charline Depardieu'");
        }
        
        // get custom fields group from fixture
        $customFieldsGroups = static::$kernel->getContainer()
              ->get('doctrine.orm.entity_manager')
              ->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')
              ->findBy(array('entity' => 'Chill\ReportBundle\Entity\Report'))
                ;
        //filter customFieldsGroup to get only "situation de logement"
        $filteredCustomFieldsGroupHouse = array_filter($customFieldsGroups,
                function(CustomFieldsGroup $group) {
                    return in_array("Situation de logement", $group->getName());
                });
        $this->group = $filteredCustomFieldsGroupHouse[0];
    }
    
    public function testValidCreate()
    {
        $client = $this->getAuthenticatedClient();
        $form = $this->getReportForm($this->person, $this->group, $client);
        
        $form->get('chill_reportbundle_report[date]')->setValue(
              (new \DateTime())->format('d-m-Y'));
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect(),
              "The next page is a redirection to the new report's view page");
        
    }
    
    public function testUngrantedUserIsDeniedAccessOnListReports()
    {
        $client = $this->getAuthenticatedClient('center b_social');
        $client->request('GET', sprintf('/fr/person/%d/report/list', 
                $this->person->getId()));
        
        $this->assertEquals(403, $client->getResponse()->getStatusCode(),
                'assert that user for center b has a 403 status code when listing'
                . 'reports on person from center a');
    }
    
    public function testUngrantedUserIsDeniedAccessOnReport()
    {
        $client = $this->getAuthenticatedClient('center b_social');
        $reports = static::$kernel->getContainer()->get('doctrine.orm.entity_manager')
                ->getRepository('ChillReportBundle:Report')
                ->findBy(array('person' => $this->person));
        $report = $reports[0];
        
        $client->request('GET', sprintf('/fr/person/%d/report/%d/view', 
                $this->person->getId(), $report->getId()));
        
        $this->assertEquals(403, $client->getResponse()->getStatusCode(),
                'assert that user for center b has a 403 status code when '
                . 'trying to watch a report from person from center a');
    }
    
    public function testUngrantedUserIsDeniedReportNew()
    {
        $client = $this->getAuthenticatedClient('center b_social');
        
        $client->request('GET', sprintf('fr/person/%d/report/cfgroup/%d/new',
                $this->person->getId(), $this->group->getId()));
        
        $this->assertEquals(403, $client->getResponse()->getStatusCode(),
                'assert that user is denied on trying to show a form "new" for'
                . ' a person on another center');
    }
    
    public function testUngrantedUserIsDeniedReportCreate()
    {
        $clientCenterA = $this->getAuthenticatedClient('center a_social');
        
        $form = $this->getReportForm($this->person, $this->group, $clientCenterA);
        
        $clientCenterB = $this->getAuthenticatedClient('center b_social');
        $clientCenterB->submit($form);
        
        $this->assertEquals(403, $clientCenterB->getResponse()->getStatusCode(),
                'assert that user is denied on trying to show a form "new" for'
                . ' a person on another center');
    }
    
    protected function getAuthenticatedClient($username = 'center a_social')
    {
        return static::createClient(array(), array(
           'PHP_AUTH_USER' => $username,
           'PHP_AUTH_PW'   => 'password',
        ));
    }
    
    /**
     * 
     * @param Person $person
     * @param CustomFieldsGroup $group
     * @param Client $client
     * @return \Symfony\Component\DomCrawler\Form
     */
    protected function getReportForm(Person $person, CustomFieldsGroup $group, Client $client)
    {
        $url = sprintf('fr/person/%d/report/cfgroup/%d/new', $person->getId(),
                $group->getId());
        $crawler = $client->request('GET', $url);
        
        return $crawler->selectButton('Ajouter le rapport')
              ->form();
    }
            
    
    
}
