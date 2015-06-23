<?php


/*
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

namespace Chill\ReportBundle\Entity;

use Chill\MainBundle\Entity\HasCenterInterface;
use Chill\MainBundle\Entity\HasScopeInterface;
use Chill\MainBundle\Entity\Scope;
use Chill\MainBundle\Entity\User;
use Chill\PersonBundle\Entity\Person;
use Chill\CustomFieldsBundle\Entity\CustomFieldsGroup;

/**
 * Report
 */
class Report implements HasCenterInterface, HasScopeInterface
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Person
     */
    private $person;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var Scope
     */
    private $scope;

    /**
     * @var array
     */
    private $cFData;

    /**
     * @var CustomFieldsGroup
     */
    private $cFGroup;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param \User $user
     *
     * @return Report
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set person
     *
     * @param Person $person
     *
     * @return Report
     */
    public function setPerson(Person $person)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get person
     *
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Report
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set scope
     *
     * @param string $scope
     *
     * @return Report
     */
    public function setScope(Scope $scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get scope
     *
     * @return Scope
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set cFData
     *
     * @param array $cFData
     *
     * @return Report
     */
    public function setCFData(array $cFData)
    {
        $this->cFData = $cFData;

        return $this;
    }

    /**
     * Get cFData
     *
     * @return array
     */
    public function getCFData()
    {
        return $this->cFData;
    }

    /**
     * Set cFGroup
     *
     * @param CustomFieldsGroup $cFGroup
     *
     * @return Report
     */
    public function setCFGroup(CustomFieldsGroup $cFGroup)
    {
        $this->cFGroup = $cFGroup;

        return $this;
    }

    /**
     * Get cFGroup
     *
     * @return CustomFieldsGroup
     */
    public function getCFGroup()
    {
        return $this->cFGroup;
    }

    public function getCenter()
    {
        return $this->person->getCenter();
    }

}
