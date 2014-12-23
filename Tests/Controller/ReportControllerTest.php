<?php

/*
 * Chill is a software for social workers
 * Copyright (C) 2014 Julien Fastré <julien.fastre@champs-libres.coop>
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
use Symfony\Component\DomCrawler\Form;

/**
 * Test the life cycles of controllers, according to 
 * https://redmine.champs-libres.coop/projects/report/wiki/Test_plan_for_report_lifecycle
 *
 * @author Julien Fastré <julien.fastre@champs-libres.coop>
 */
class ReportControllerTest extends WebTestCase
{
    
    const REPORT_NAME_FIELD = 'cFGroup';
    
    /**
     *
     * @var \Chill\PersonBundle\Entity\Person 
     */
    private static $person;
    
    /**
     *
     * @var \Symfony\Component\BrowserKit\Client
     */
    private static $client;
    
    private static $user;
    
    public static function setUpBeforeClass()
    {
        static::bootKernel();
        
        //get a random person
        $persons = static::$kernel->getContainer()
              ->get('doctrine.orm.entity_manager')
              ->getRepository('ChillPersonBundle:Person')
              ->findAll();
        static::$person = $persons[array_rand($persons)];
        
        static::$client = static::createClient(array(), array(
           'PHP_AUTH_USER' => 'center a_social',
           'PHP_AUTH_PW'   => 'password',
        ));
        
        static::$user =  static::$kernel->getContainer()
                  ->get('doctrine.orm.entity_manager')
                  ->getRepository('ChillMainBundle:User')
                  ->findOneBy(array('username' => "center a_social"));
        
    }
    
    /**
     * 
     * 
     * We assume that : 
     * - we are on a "person" page
     * - there are more than one report model
     * 
     */
    public function testMenu()
    {
        
        $crawlerPersonPage = static::$client->request('GET', sprintf('/fr/person/%d/general', 
              static::$person->getId()));
        
        if (! static::$client->getResponse()->isSuccessful()) {
            var_dump($crawlerPersonPage->html());
            throw new \RuntimeException('the request at person page failed');
        }
        
        $link = $crawlerPersonPage->selectLink("Ajout d'un rapport")->link();
        $this->assertInstanceOf('Symfony\Component\DomCrawler\Link', $link, 
              "There is a \"add a report\" link in menu");
        $this->assertContains(sprintf("/fr/person/%d/report/select/type/for/creation",
              static::$person->getId()), $link->getUri(), 
              "There is a \"add a report\" link in menu");
        
        return $link;
    }
    
    /**
     * 
     * @param \Symfony\Component\DomCrawler\Link $link
     * @return type
     * @depends testMenu
     */
    public function testChooseReportModelPage(\Symfony\Component\DomCrawler\Link $link) 
    {
        
        // When I click on "adda report" link in menu
        $crawlerAddAReportPage = static::$client->click($link);
        
        $form = $crawlerAddAReportPage->selectButton("Créer un nouveau rapport")->form();
        
        $this->assertInstanceOf('Symfony\Component\DomCrawler\Form', $form,
              'I can see a form with a button "add a new report" ');
        
        $this->assertGreaterThan(1, count($form->get(self::REPORT_NAME_FIELD)
                  ->availableOptionValues()),
                "I can choose between report models");

        $possibleOptionsValue = $form->get(self::REPORT_NAME_FIELD)
              ->availableOptionValues();
        $form->get(self::REPORT_NAME_FIELD)->setValue(
              $possibleOptionsValue[array_rand($possibleOptionsValue)]);
        
        static::$client->submit($form);
        
        $this->assertTrue(static::$client->getResponse()->isRedirect());
        return static::$client->followRedirect();
        
        
    }
    
    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $crawlerNewReportPage
     * @return type
     * @depends testChooseReportModelPage
     */
    public function testNewReportPage(\Symfony\Component\DomCrawler\Crawler $crawlerNewReportPage)
    {   
        
        $addForm = $crawlerNewReportPage
              ->selectButton('Ajouter le rapport')
              ->form();
        
        $this->assertInstanceOf('Symfony\Component\DomCrawler\Form', $addForm,
              'I have a report form');
        
        $this->isFormAsExpected($addForm);
        
        $c = static::$client->submit($addForm);

        $this->assertTrue(static::$client->getResponse()->isRedirect(),
              "The next page is a redirection to the new report's view page");
        static::$client->followRedirect();
        
        $this->assertRegExp("|/fr/person/".static::$person->getId()."/report/[0-9]*/view$|",
              static::$client->getHistory()->current()->getUri(),
              "The next page is a redirection to the new report's view page");
        
        return $addForm;
        
        
    }
    
    
    /**
     * Test the expected field are present
     * 
     * @param Form $form
     * @param boolean $isDefault if the form should be at default values
     * 
     */
    private function isFormAsExpected(Form $form, $isDefault = true)
    {
        $this->assertTrue($form->has('chill_reportbundle_report[date]'), 
              'the report form have a field "date"' );
        $this->assertTrue($form->has('chill_reportbundle_report[user]'),
              'the report form have field "user" ');
        
        $this->assertEquals('select', $form->get('chill_reportbundle_report[user]')
              ->getType(), "the user field is a select input");
        
        if ($isDefault) {
            $date = new \DateTime('now');
            $this->assertEquals(
                $date->format('d-m-Y'), 
                $form->get('chill_reportbundle_report[date]')->getValue(),
                "the date field contains the current date by default"
                  );
            
            //resolve the user
            $userId = $form->get('chill_reportbundle_report[user]')->getValue();
            
            $this->assertEquals('center a_social', static::$user->getUsername(),
                  "the user field is the current user by default");
        }
    }
    
    /**
     * 
     * @param Form $form
     * @depends testNewReportPage
     */
    public function testNullDate(Form $form)
    {
        $this->assertTrue(true);
    }
}
