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

namespace Chill\ReportBundle\Tests\Security\Authorization;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Chill\MainBundle\Test\PrepareUserTrait;
use Chill\MainBundle\Test\PrepareCenterTrait;
use Chill\MainBundle\Test\PrepareScopeTrait;
use Chill\ReportBundle\Entity\Report;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Chill\MainBundle\Entity\User;
use Chill\MainBundle\Entity\Center;
use Chill\PersonBundle\Entity\Person;

/**
 * 
 *
 * @author Julien Fastré <julien.fastre@champs-libres.coop>
 */
class ReportVoterTest extends KernelTestCase
{    
    
    use PrepareUserTrait, PrepareCenterTrait, PrepareScopeTrait;
    
    /**
     *
     * @var \Chill\ReportBundle\Security\Authorization\ReportVoter
     */
    protected $voter;
    
    /**
     *
     * @var \Prophecy\Prophet
     */
    protected $prophet;
    
    public static function setUpBeforeClass()
    {
        
    }
    
    public function setUp()
    {
        static::bootKernel();
        $this->voter = static::$kernel->getContainer()
                ->get('chill.report.security.authorization.report_voter');
        $this->prophet = new \Prophecy\Prophet();
    }
    
    /**
     * @dataProvider dataProvider
     * @param type $expectedResult
     * @param Report $report
     * @param User $user
     * @param type $action
     * @param type $message
     */
    public function testAccess($expectedResult, Report $report, $action, 
            $message, User $user = null)
    {
        $token = $this->prepareToken($user);
        $result = $this->voter->vote($token, $report, [$action]);
        $this->assertEquals($expectedResult, $result, $message);
    }
    
    /**
     * prepare a person
     * 
     * The only properties set is the center, others properties are ignored.
     * 
     * @param Center $center
     * @return Person
     */
    protected function preparePerson(Center $center)
    {
        return (new Person())
              ->setCenter($center)
              ;
    }
    
    /**
     * prepare a token interface with correct rights
     * 
     * if $permissions = null, user will be null (no user associated with token
     * 
     * @param User $user
     * @return \Symfony\Component\Security\Core\Authentication\Token\TokenInterface
     */
    protected function prepareToken(User $user = null)
    {        
        $token = $this->prophet->prophesize();
        $token
            ->willImplement('\Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        if ($user === NULL) {
            $token->getUser()->willReturn(null);
        } else {
            $token->getUser()->willReturn($user);
        }
        
        return $token->reveal();
    }
    
    public function dataProvider()
    {
        $centerA = $this->prepareCenter(1, 'center A');
        $centerB = $this->prepareCenter(2, 'center B');
        $scopeA = $this->prepareScope(1, 'scope default');
        $scopeB = $this->prepareScope(2, 'scope B');
        $scopeC = $this->prepareScope(3, 'scope C');
        
        $userA = $this->prepareUser(array(
            array(
                'center' => $centerA, 
                'permissionsGroup' => array(
                    ['scope' => $scopeB, 'role' => 'CHILL_REPORT_SEE'],
                    ['scope' => $scopeA, 'role' => 'CHILL_REPORT_UPDATE']
                )
            ),
            array(
               'center' => $centerB,
               'permissionsGroup' => array(
                     ['scope' => $scopeA, 'role' => 'CHILL_REPORT_SEE'],
               )
            )
            
        ));
        
        $reportA = (new Report)
                ->setScope($scopeA)
                ->setPerson($this->preparePerson($centerA))
                ;
        $reportB = (new Report())
                ->setScope($scopeB)
                ->setPerson($this->preparePerson($centerA))
                ;
        $reportC = (new Report())
                ->setScope($scopeC)
                ->setPerson($this->preparePerson($centerB))
                ;
        
        
        return array(
            array(
                VoterInterface::ACCESS_DENIED,
                $reportA,
                'CHILL_REPORT_SEE',
                "assert is denied to a null user",
                null
            ),
            array(
                VoterInterface::ACCESS_GRANTED,
                $reportA,
                'CHILL_REPORT_SEE',
                "assert access is granted to a user with inheritance UPDATE > SEE",
                $userA
            ),
            array(
                VoterInterface::ACCESS_GRANTED,
                $reportB,
                'CHILL_REPORT_SEE',
                "assert access is granted to a user without inheritance",
                $userA
            ),
            array(
                VoterInterface::ACCESS_DENIED,
                $reportC,
                'CHILL_REPORT_SEE',
                'assert access is denied to a report',
                $userA
            )
        );
        
    }
    
    
}
