<?php

/*
 * Chill is a software for social workers
 *
 * Copyright (C) 2014-2015, Champs Libres Cooperative SCRLFS, 
 * <http://www.champs-libres.coop>, <info@champs-libres.coop>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
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
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\DomCrawler\Crawler;
use Chill\CustomFieldsBundle\Entity\CustomFieldsGroup;
use Chill\PersonBundle\Entity\Person;

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
     * @var \SClientymfony\Component\BrowserKit\
     */
    private static $client;
    
    private static $user;
    
    /**
     *
     * @var CustomFieldsGroup
     */
    private static $group;
    
    /**
     *
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private static $em;

    public static function setUpBeforeClass()
    {
        static::bootKernel();
        
        static::$em = static::$kernel->getContainer()
            ->get('doctrine.orm.entity_manager');

        //get a random person
        static::$person = static::$kernel->getContainer()
              ->get('doctrine.orm.entity_manager')
              ->getRepository('ChillPersonBundle:Person')
              ->findOneBy(array(
                  'lastName' => 'Charline',
                  'firstName'  => 'Depardieu'
                  )
                );
        
        if (static::$person === NULL) {
            throw new \RuntimeException("The expected person is not present in the database. "
                    . "Did you run `php app/console doctrine:fixture:load` before launching tests ? "
                    . "(expecting person is 'Charline Depardieu'");
        }
        
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
        static::$group = $filteredCustomFieldsGroupHouse[0];
        
        static::$user =  static::$kernel->getContainer()
                  ->get('doctrine.orm.entity_manager')
                  ->getRepository('ChillMainBundle:User')
                  ->findOneBy(array('username' => "center a_social"));
    }
    
    public function setUp() 
    {
        static::$client = static::createClient(array(), array(
           'PHP_AUTH_USER' => 'center a_social',
           'PHP_AUTH_PW'   => 'password',
        ));
    }
    
    /**
     * 
     * @param type $username
     * @return Client
     */
    public function getAuthenticatedClient($username = 'center a_social')
    {
        return static::createClient(array(), array(
           'PHP_AUTH_USER' => $username,
           'PHP_AUTH_PW'   => 'password',
        ));
    }
    
    /**
     * Set up the browser to be at a random person general page (/fr/person/%d/general),
     * check if there is a menu link for adding a new report and return this link (as producer)
     * 
     * We assume that : 
     * - we are on a "person" page
     * - there are more than one report model
     * 
     */
    public function testMenu()
    {
        $client = $this->getAuthenticatedClient();
        $crawlerPersonPage = $client->request('GET', sprintf('/fr/person/%d/general', 
              static::$person->getId()));
        
        if (! $client->getResponse()->isSuccessful()) {
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
    public function testChooseReportModelPage(Link $link) 
    {   
        // When I click on "add a report" link in menu
        $client = $this->getAuthenticatedClient();
        $crawlerAddAReportPage = $client->click($link);
        
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
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect());
        return $client->followRedirect();
    }
    
    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $crawlerNewReportPage
     * @return type
     * @depends testChooseReportModelPage
     */
    public function testNewReportPage(Crawler $crawlerNewReportPage)
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
     * get a form for report new
     * 
     * @param \Chill\ReportBundle\Tests\Controller\Person $person
     * @param CustomFieldsGroup $group
     * @param \Symfony\Component\BrowserKit\Client $client
     * @return Form
     */
    protected function getReportForm(Person $person, CustomFieldsGroup $group, 
        \Symfony\Component\BrowserKit\Client $client)
    {
        $url = sprintf('fr/person/%d/report/cfgroup/%d/new', $person->getId(),
                $group->getId());
        $crawler = $client->request('GET', $url);
        
        return $crawler->selectButton('Ajouter le rapport')
              ->form();
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
        
        return $form;
    }
    
    /**
     * Test that setting a Null date redirect to an error page
     * 
     */
    public function testNullDate()
    {
        $client = $this->getAuthenticatedClient();
        $form = $this->getReportForm(static::$person, static::$group, 
                $client);
        //var_dump($form);
        $filledForm = $this->fillCorrectForm($form);
        $filledForm->get('chill_reportbundle_report[date]')->setValue('');
        //$this->markTestSkipped();
        $crawler = $this->getAuthenticatedClient('center a_administrative')->submit($filledForm);

        $this->assertFalse($client->getResponse()->isRedirect());
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
        $client = $this->getAuthenticatedClient();
        $this->markTestSkipped("This test raise an error since symfony 2.7. "
                . "The user is not correctly reloaded from database.");
        $filledForm = $this->fillCorrectForm($form);
        $filledForm->get('chill_reportbundle_report[date]')->setValue('invalid date value');
        
        $crawler = $client->submit($filledForm);
        
        $this->assertFalse($client->getResponse()->isRedirect());
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
        $client = $this->getAuthenticatedClient();
        $filledForm = $this->fillCorrectForm($form);
        $select = $filledForm->get('chill_reportbundle_report[user]')
              ->disableValidation()
              ->setValue(-1);
        
        $crawler = $client->submit($filledForm);
        
        $this->assertFalse($client->getResponse()->isRedirect());
        $this->assertGreaterThan(0, $crawler->filter('.error')->count());
    }
    
    /**
     * Test the creation of a report
     * 
     * depends testNewReportPage
     * param Form $form
     */
    public function testValidCreate()
    {
        $client = $this->getAuthenticatedClient();
        //$this->markTestSkipped("This test raise an error since symfony 2.7. "
        //        . "The user is not correctly reloaded from database.");
        $addForm = $this->getReportForm(self::$person, self::$group, $client);
        $filledForm = $this->fillCorrectForm($addForm);
        $c = $client->submit($filledForm);
        
        $this->assertTrue($client->getResponse()->isRedirect(),
              "The next page is a redirection to the new report's view page");
        $client->followRedirect();
        
        $this->assertRegExp("|/fr/person/".static::$person->getId()."/report/[0-9]*/view$|",
              $client->getHistory()->current()->getUri(),
              "The next page is a redirection to the new report's view page");
        
        $matches = array();
        preg_match('|/report/([0-9]*)/view$|', 
              $client->getHistory()->current()->getUri(), $matches);

        return $matches[1];
    }
    

    /**
     * @depends testValidCreate
     * @param int $reportId
     */
    public function testList($reportId)
    {
        $client = $this->getAuthenticatedClient();
        $crawler = $client->request('GET', sprintf('/fr/person/%s/report/list',
              static::$person->getId()));
        
        $this->assertTrue($client->getResponse()->isSuccessful());
        
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
        $client = $this->getAuthenticatedClient();
        $client->request('GET', 
              sprintf('/fr/person/%s/report/%s/view', static::$person->getId(), $reportId));
        
        $this->assertTrue($client->getResponse()->isSuccessful(),
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
        $client = $this->getAuthenticatedClient();
        $crawler = $client->request('GET',
              sprintf('/fr/person/%s/report/%s/edit', static::$person->getId(), $reportId));
        
        $this->assertTrue($client->getResponse()->isSuccessful());
        
        $form = $crawler
                ->selectButton('Enregistrer le rapport')
                ->form();
        
        $form->get('chill_reportbundle_report[date]')->setValue(
              (new \DateTime('yesterday'))->format('d-m-Y'));
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect(
              sprintf('/fr/person/%s/report/%s/view', 
                    static::$person->getId(), $reportId)));

        $this->assertEquals(new \DateTime('yesterday'), static::$kernel->getContainer()
              ->get('doctrine.orm.entity_manager')
              ->getRepository('ChillReportBundle:Report')
              ->find($reportId)
              ->getDate());
    }

    /**
     * Test that in the general export page there is an Export reports link
     * that leads to export/report/select/type
     * 
     * @return \Symfony\Component\DomCrawler\Link The link to the the
     * form use for selecting which type of report to export
     */
    public function testLinkToTheExportReport()
    {
        $client = $this->getAuthenticatedClient();
        $crawlerReportExportPage = $client->request('GET', '/fr/export');
        
        if (! $client->getResponse()->isSuccessful()) {
            var_dump($crawlerReportExportPage->html());
            throw new \RuntimeException('The get request at export page failed');
        }
        
        $link = $crawlerReportExportPage->selectLink("Export reports")->link();
        $this->assertInstanceOf('Symfony\Component\DomCrawler\Link', $link, 
              "There is a \"export reports\" link in the export menu");

        $this->assertContains("export/report/select/type", $link->getUri(), 
              "The \"export reports\" link in the export menu points to export/report/select/type");
        
        return $link;
    }

    /**
     * Test the export form for selecting the type of report to export :
     * - follow the given link ( export/report/select/type )
     * - choose randomly a type of report (CustomFieldsGroup)
     * - submit the form
     *
     * @return Integer The id of the type of report selected (CFGroup)
     * @depends testLinkToTheExportReport
     */
    public function testFormForExportAction(Link $link)
    {
        $client = $this->getAuthenticatedClient();
        $crawlerExportReportPage = $client->click($link);

        $form = $crawlerExportReportPage->selectButton("Export this kind of reports")->form();
        
        $this->assertInstanceOf('Symfony\Component\DomCrawler\Form', $form,
              'I can see a form with a button "Export this kind of reports" ');
        
        $this->assertGreaterThan(1, count($form->get(self::REPORT_NAME_FIELD)
                  ->availableOptionValues()),
                "I can choose between report types");

        $possibleOptionsValue = $form->get(self::REPORT_NAME_FIELD)
              ->availableOptionValues();


        $cfGroupId = $possibleOptionsValue[array_rand($possibleOptionsValue)];

        $form->get(self::REPORT_NAME_FIELD)->setValue($cfGroupId);
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect());
        
        $client->followRedirect();
        
        return $cfGroupId;
    }
    
    /**
     * Test the output of the export action :
     * - check if a csv file is well received
     * - check if the csv is well formated (if each row has the same number of
     *      cells)
     * - check if the number of data rows (not the header) of the csv file is
     *      as expected (number of report of this type)
     *
     * @param Int The id of the type of report selected (CFGroup)
     * @depends testFormForExportAction
     */
    public function testCSVExportAction($cfGroupId)
    {
        $client = $this->getAuthenticatedClient();
        
        $client->request('GET', 'fr/export/report/cfgroup/'. 
                static::$group->getId());
        $response = $client->getResponse();

        $this->assertTrue(
            strpos($response->headers->get('Content-Type'),'text/csv') !== false,
            'The csv file is well received');
        
        $content = $response->getContent();
        $rows = str_getcsv($content, "\n");
        
        $headerRow = array_pop($rows);
        $header = str_getcsv($headerRow);
        $headerSize = sizeof($header);
        $numberOfRows = 0;

        foreach ($rows as $row) {
            $rowContent = str_getcsv($row);

            $this->assertTrue(
                sizeof($rowContent) == $headerSize,
                'Each row of the csv contains the good number of elements ('
                . 'regarding to the first row');

            $numberOfRows ++;
        }

        $cfGroup = static::$em->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')->find($cfGroupId);
        $reports = static::$em->getRepository('ChillReportBundle:Report')
                ->findByCFGroup($cfGroup);

        $this->markTestSkipped();
        $this->assertEquals(
            $numberOfRows, sizeof($reports),
            'The csv file has a number of row equivalent than the number of reports in the db'
        );
    }
}
