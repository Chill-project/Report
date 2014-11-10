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

namespace Chill\PersonBundle\DataFixtures\ORM;

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
        $manager->flush();
    }
}