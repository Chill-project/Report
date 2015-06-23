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

namespace Chill\ReportBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Chill\MainBundle\DataFixtures\ORM\LoadPermissionsGroup;
use Chill\MainBundle\Entity\RoleScope;
use Chill\MainBundle\DataFixtures\ORM\LoadScopes;

/**
 * Add a role CHILL_REPORT_UPDATE & CHILL_REPORT_CREATE for all groups except administrative,
 * and a role CHILL_REPORT_SEE for administrative
 *
 * @author Julien Fastré <julien.fastre@champs-libres.coop>
 */
class LoadReportACL extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 14999;
    }

    
    public function load(ObjectManager $manager)
    {
        foreach (LoadPermissionsGroup::$refs as $permissionsGroupRef) {
            $permissionsGroup = $this->getReference($permissionsGroupRef);
            foreach (LoadScopes::$references as $scopeRef){
                $scope = $this->getReference($scopeRef);
                //create permission group
                switch ($permissionsGroup->getName()) {
                    case 'social':
                        if ($scope->getName()['en'] === 'administrative') {
                            continue; // we do not want any power on administrative
                        }
                    case 'administrative':
                    case 'direction':
                        if (in_array($scope->getName()['en'], array('administrative', 'social'))) {
                            continue; // we do not want any power on social or administrative
                        }  
                }
                
                printf("Adding CHILL_REPORT_UPDATE & CHILL_REPORT_CREATE to %s "
                        . "permission group, scope '%s' \n", 
                        $permissionsGroup->getName(), $scope->getName()['en']);
                $roleScopeUpdate = (new RoleScope())
                            ->setRole('CHILL_REPORT_UPDATE')
                            ->setScope($scope);
                $permissionsGroup->addRoleScope($roleScopeUpdate);
                $roleScopeCreate = (new RoleScope())
                            ->setRole('CHILL_REPORT_CREATE')
                            ->setScope($scope);
                $permissionsGroup->addRoleScope($roleScopeCreate);
                $manager->persist($roleScopeUpdate);
                $manager->persist($roleScopeCreate);
            }
            
        }
        
        $manager->flush();
    }

}
