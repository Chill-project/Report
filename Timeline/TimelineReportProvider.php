<?php

/*
 * Chill is a software for social workers
 * Copyright (C) 2015 Champs Libres <info@champs-libres.coop>
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

namespace Chill\ReportBundle\Timeline;

use Chill\MainBundle\Timeline\TimelineProviderInterface;
use Doctrine\ORM\EntityManager;
use Chill\MainBundle\Security\Authorization\AuthorizationHelper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Role\Role;
use Doctrine\ORM\Mapping\ClassMetadata;
use Chill\PersonBundle\Entity\Person;
use Chill\MainBundle\Entity\Scope;

/**
 * Provide report for inclusion in timeline
 *
 * @author Julien Fastré <julien.fastre@champs-libres.coop>
 * @author Champs Libres <info@champs-libres.coop>
 */
class TimelineReportProvider implements TimelineProviderInterface
{
    
    /**
     *
     * @var EntityManager
     */
    protected $em;
    
    /**
     *
     * @var AuthorizationHelper
     */
    protected $helper;
    
    /**
     *
     * @var \Chill\MainBundle\Entity\User 
     */
    protected $user;
    
    public function __construct(EntityManager $em, AuthorizationHelper $helper,
            TokenStorage $storage)
    {
        $this->em = $em;
        $this->helper = $helper;
        
        if (!$storage->getToken()->getUser() instanceof \Chill\MainBundle\Entity\User)
        {
            throw new \RuntimeException('A user should be authenticated !');
        }
        
        $this->user = $storage->getToken()->getUser();
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchQuery($context, array $args)
    {
        $this->checkContext($context);
        
        $metadataReport = $this->em->getClassMetadata('ChillReportBundle:Report');
        $metadataPerson = $this->em->getClassMetadata('ChillPersonBundle:Person');
        
        return array(
           'id' => $metadataReport->getTableName()
                .'.'.$metadataReport->getColumnName('id'),
           'type' => 'report',
           'date' => $metadataReport->getTableName()
                .'.'.$metadataReport->getColumnName('date'),
           'FROM' => $this->getFromClause($metadataReport, $metadataPerson),
           'WHERE' => $this->getWhereClause($metadataReport, $metadataPerson,
                   $args['person'])
        );
    }
    
    private function getWhereClause(ClassMetadata $metadataReport, 
            ClassMetadata $metadataPerson, Person $person)
    {
        $role = new Role('CHILL_REPORT_SEE');
        $reachableCenters = $this->helper->getReachableCenters($this->user, 
                $role);
        $associationMapping = $metadataReport->getAssociationMapping('person');
        
        // we start with reports having the person_id linked to person 
        // (currently only context "person" is supported)
        $whereClause = sprintf('%s = %d',
                 $associationMapping['joinColumns'][0]['name'],
                 $person->getId());
        
        // we add acl (reachable center and scopes)
        $centerAndScopeLines = array();
        foreach ($reachableCenters as $center) {
            $reachablesScopesId = array_map(
                    function(Scope $scope) { return $scope->getId(); },
                    $this->helper->getReachableScopes($this->user, $role, 
                        $person->getCenter())
                );
                    
            $centerAndScopeLines[] = sprintf('(%s = %d AND %s IN (%s))',
                    $metadataPerson->getTableName().'.'.
                        $metadataPerson->getAssociationMapping('center')['joinColumns'][0]['name'],
                    $center->getId(),
                    $metadataReport->getTableName().'.'.
                        $metadataReport->getAssociationMapping('scope')['joinColumns'][0]['name'],
                    implode(',', $reachablesScopesId));
            
        }
        $whereClause .= ' AND ('.implode(' OR ', $centerAndScopeLines).')';
        
        return $whereClause;
    }
    
    private function getFromClause(ClassMetadata $metadataReport,
            ClassMetadata $metadataPerson)
    {
        $associationMapping = $metadataReport->getAssociationMapping('person');
        
        return $metadataReport->getTableName().' JOIN '
            .$metadataPerson->getTableName().' ON '
            .$metadataPerson->getTableName().'.'.
                $associationMapping['joinColumns'][0]['referencedColumnName']
            .' = '
            .$associationMapping['joinColumns'][0]['name']
            ;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function getEntities(array $ids)
    {
        $reports = $this->em->getRepository('ChillReportBundle:Report')
              ->findBy(array('id' => $ids));
        
        $result = array();
        foreach($reports as $report) {
            $result[$report->getId()] = $report;
        }
        
        return $result;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function getEntityTemplate($entity, $context, array $args)
    {
        $this->checkContext($context);
        
        //gather all custom fields which should appears in summary
        $customFieldsInSummary = array();
        if (array_key_exists('summary_fields', $entity->getCFGroup()->getOptions())) {
            
            foreach ($entity->getCFGroup()->getCustomFields() as $customField) {
                if (in_array($customField->getSlug(), 
                      $entity->getCFGroup()->getOptions()['summary_fields'])) {
                    $customFieldsInSummary[] = $customField;
                }
            }
        }

        
        
        return array(
           'template' => 'ChillReportBundle:Timeline:report_person_context.html.twig',
           'template_data' => array(
              'report' => $entity,
              'custom_fields_in_summary' => $customFieldsInSummary,
              'person' => $args['person'],
              'user' => $entity->getUser()
           )
        );
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function supportsType($type)
    {
        return $type === 'report';
    }
    
    /**
     * check if the context is supported
     * 
     * @param string $context
     * @throws \LogicException if the context is not supported
     */
    private function checkContext($context)
    {
        if ($context !== 'person') {
            throw new \LogicException("The context '$context' is not "
                  . "supported. Currently only 'person' is supported");
        }
    }

}
