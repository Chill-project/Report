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

namespace Chill\ReportBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Chill\CustomFieldsBundle\Entity\CustomFieldsGroup;

/**
 * Load CustomFieldsGroup for Report into database
 */
class LoadCustomFieldsGroup extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 15000;
    }
    
    public function load(ObjectManager $manager)
    {
        echo "loading customFieldsGroup...\n";
        
        $report = $this->createReport($manager, array('fr' => 'Situation de logement'));
        $this->addReference('cf_group_report_logement', $report);
        
        $report = $this->createReport($manager, array('fr' => 'Alphabétisme'));
        $this->addReference('cf_group_report_education', $report);

        for($i=0; $i <= 3; $i++) {
            
            $report = $this->createReport($manager, array('fr' => 'ZZ Rapport aléatoire '.$i));

            $this->addReference('cf_group_report_'.$i, $report);
        }
        
        
        
        $manager->flush();
    }
    
    /**
     * create a report and persist in the db
     * 
     * @param ObjectManager $manager
     * @param array $name
     * @return CustomFieldsGroup
     */
    private function createReport(ObjectManager $manager, array $name)
    {
        echo $name['fr']." \n";
        
        $cFGroup = (new CustomFieldsGroup())
            ->setName($name)
            ->setEntity('Chill\ReportBundle\Entity\Report');

        $manager->persist($cFGroup);
        
        return $cFGroup;
    }
}