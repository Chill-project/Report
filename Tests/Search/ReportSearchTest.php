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

namespace Chill\ReportBundle\Tests\Search;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test for report search
 *
 * @author Julien Fastré <julien.fastre@champs-libres.coop>
 */
class ReportSearchTest extends WebTestCase
{
    
    public function testSearchExpectedDefault()
    {
        $client = $this->getAuthenticatedClient();
        
        $crawler = $client->request('GET', '/fr/search', array(
            'q' => '@report 2015-01-05'
        ));
        
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertRegExp('/Charline/', $crawler->text());
        $this->assertRegExp('/Situation de logement/i', $crawler->text());
    }
    
    public function testNamedSearch()
    {
        $client = $this->getAuthenticatedClient();
        
        $crawler = $client->request('GET', '/fr/search', array(
            'q' => '@report '.(new \DateTime('tomorrow'))->format('Y-m-d'), //there should be any result for future. And future is tomorrow
            'name' => 'report' 
        ));
        
        $this->assertTrue($client->getResponse()->isSuccessful());
    }
    
    public function testSearchByDate()
    {
        $client = $this->getAuthenticatedClient();
        
        $crawler = $client->request('GET', '/fr/search', array(
            'q' => '@report date:2015-01-05'
        ));
        
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertRegExp('/Charline/', $crawler->text());
        $this->assertRegExp('/Situation de logement/i', $crawler->text());
    }
    
    public function testSearchDoubleDate()
    {
        $client = $this->getAuthenticatedClient();
        
        $crawler = $client->request('GET', '/fr/search', array(
            'q' => '@report date:2014-05-01 2014-05-06'
        ));
        
        $this->assertGreaterThan(0, $crawler->filter('.error')->count());
    }
    
    public function testSearchEmtpy()
    {
        $client = $this->getAuthenticatedClient();
        
        $crawler = $client->request('GET', '/fr/search', array(
            'q' => '@report '
        ));
        
        $this->assertGreaterThan(0, $crawler->filter('.error')->count());
    }
    
    
    
    
    /**
     * 
     * @return \Symfony\Component\BrowserKit\Client
     */
    private function getAuthenticatedClient()
    {
        return static::createClient(array(), array(
           'PHP_AUTH_USER' => 'center a_social',
           'PHP_AUTH_PW'   => 'password',
        ));
    }
}
