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
     * fill the form with correct data
     * 
     * @param Form $form
     */
    private function fillCorrectForm(Form $form)
    {
        $form->get('chill_reportbundle_report[date]')->setValue(
              (new \DateTime())->format('d-m-Y'));
        //get the first option values
        $form->get('chill_reportbundle_report[user]')->setValue(
              $form->get('chill_reportbundle_report[user]')
              ->availableOptionValues()[0]);
        
        return $form;
    }
    
    /**
     * Test that setting a Null date redirect to an error page
     * 
     * @param Form $form
     * @depends testNewReportPage
     */
    public function testNullDate(Form $form)
    {
        $filledForm = $this->fillCorrectForm($form);
        $filledForm->get('chill_reportbundle_report[date]')->setValue('');
        
        $crawler = static::$client->submit($filledForm);
        
        $this->assertFalse(static::$client->getResponse()->isRedirect());
        $this->assertGreaterThan(0, $crawler->filter('.error')->count());
    }
    
    /**
     * Test that setting a Null date redirect to an error page
     * 
     * @param Form $form
     * @depends testNewReportPage
     */
    public function testInvalidDate(Form $form)
    {
        $filledForm = $this->fillCorrectForm($form);
        $filledForm->get('chill_reportbundle_report[date]')->setValue('invalid date value');
        
        $crawler = static::$client->submit($filledForm);
        
        $this->assertFalse(static::$client->getResponse()->isRedirect());
        $this->assertGreaterThan(0, $crawler->filter('.error')->count());
    }
    
    /**
     * Test that a incorrect value in user will show an error page
     * 
     * @depends testNewReportPage
     * @param Form $form
     */
    public function testInvalidUser(Form $form)
    {
        $filledForm = $this->fillCorrectForm($form);
        $select = $filledForm->get('chill_reportbundle_report[user]')
              ->disableValidation()
              ->setValue(-1);
        
        $crawler = static::$client->submit($filledForm);
        
        $this->assertFalse(static::$client->getResponse()->isRedirect());
        $this->assertGreaterThan(0, $crawler->filter('.error')->count());
    }
    
    /**
     * Test the creation of a report
     * 
     * @depends testNewReportPage
     * @param Form $form
     */
    public function testValidCreate(Form $addForm)
    {
        $filledForm = $this->fillCorrectForm($addForm);
        $c = static::$client->submit($filledForm);

        $this->assertTrue(static::$client->getResponse()->isRedirect(),
              "The next page is a redirection to the new report's view page");
        static::$client->followRedirect();
        
        $this->assertRegExp("|/fr/person/".static::$person->getId()."/report/[0-9]*/view$|",
              static::$client->getHistory()->current()->getUri(),
              "The next page is a redirection to the new report's view page");
        
        $matches = array();
        preg_match('|/report/([0-9]*)/view$|', 
              static::$client->getHistory()->current()->getUri(), $matches);

        return $matches[1];
    }
    

    /**
     * @depends testValidCreate
     * @param int $reportId
     */
    public function testList($reportId)
    {
        $crawler = static::$client->request('GET', sprintf('/fr/person/%s/report/list',
              static::$person->getId()));
        
        $this->assertTrue(static::$client->getResponse()->isSuccessful());
        
        $linkSee = $crawler->selectLink('Voir le rapport')->links();
        $this->assertGreaterThan(0, count($linkSee));
        $this->assertRegExp(sprintf('|/fr/person/%s/report/[0-9]*/view$|', 
              static::$person->getId(), $reportId), $linkSee[0]->getUri());
        
        $linkUpdate = $crawler->selectLink('Mettre à jour le rapport')->links();
        $this->assertGreaterThan(0, count($linkUpdate));
        $this->assertRegExp(sprintf('|/fr/person/%s/report/[0-9]*/edit$|',
              static::$person->getId(), $reportId), $linkUpdate[0]->getUri());
        
    }
    
    /**
     * Test the view of a report
     * 
     * @depends testValidCreate
     * @param int $reportId
     */
    public function testView($reportId)
    {
        static::$client->request('GET', 
              sprintf('/fr/person/%s/report/%s/view', static::$person->getId(), $reportId));
        
        $this->assertTrue(static::$client->getResponse()->isSuccessful(),
              'the page is shown');
    }
    
    /**
     * test the update form
     * 
     * @depends testValidCreate
     * @param int $reportId
     */
    public function testUpdate($reportId)
    {
        $crawler = static::$client->request('GET',
              sprintf('/fr/person/%s/report/%s/edit', static::$person->getId(), $reportId));
        
        $this->assertTrue(static::$client->getResponse()->isSuccessful());
        
        $form = $crawler
                ->selectButton('Enregistrer le rapport')
                ->form();
        
        $form->get('chill_reportbundle_report[date]')->setValue(
              (new \DateTime('yesterday'))->format('d-m-Y'));
        
        static::$client->submit($form);
        
        $this->assertTrue(static::$client->getResponse()->isRedirect(
              sprintf('/fr/person/%s/report/%s/view', 
                    static::$person->getId(), $reportId)));

        $this->assertEquals(new \DateTime('yesterday'), static::$kernel->getContainer()
              ->get('doctrine.orm.entity_manager')
              ->getRepository('ChillReportBundle:Report')
              ->find($reportId)
              ->getDate());
        
    }
}
