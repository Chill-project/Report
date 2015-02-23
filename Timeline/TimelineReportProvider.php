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
    
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchQuery($context, array $args)
    {
        $this->checkContext($context);
        
        $metadata = $this->em->getClassMetadata('ChillReportBundle:Report');
        
        return array(
           'id' => $metadata->getColumnName('id'),
           'type' => 'report',
           'date' => $metadata->getColumnName('date'),
           'FROM' => $metadata->getTableName(),
           'WHERE' => sprintf('%s = %d',
                 $metadata
                    ->getAssociationMapping('person')['joinColumns'][0]['name'],
                 $args['person']->getId())
        );
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
        return array(
           'template' => 'ChillReportBundle:Timeline:report_person_context.html.twig',
           'template_data' => array(
              'report' => $entity,
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
