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

namespace Chill\ReportBundle\Search;

use Chill\MainBundle\Search\AbstractSearch;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Chill\MainBundle\Search\ParsingException;

/**
 * Search amongst reports
 *
 * @author Julien Fastré <julien.fastre@champs-libres.coop>
 */
class ReportSearch extends AbstractSearch implements ContainerAwareInterface
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;
    
    /**
     * @var EntityManagerInterface
     */
    private $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getOrder()
    {
        return 10000;
    }

    public function isActiveByDefault()
    {
        return false;
    }

    public function renderResult(array $terms, $start = 0, $limit = 50, array $options = array())
    {
        return $this->container->get('templating')->render('ChillReportBundle:Search:results.html.twig', array(
            'reports' => $this->getReports($terms, $start, $limit),
            'total' => $this->count($terms),
            'pattern' => $this->recomposePattern($terms, array( 'date'), 'report')
        ));
    }
    
    private function getReports(array $terms, $start, $limit)
    {
        $qb = $this->buildQuery($terms);
        
        $qb->select('r')
                ->setMaxResults($limit)
                ->setFirstResult($start)
                ->orderBy('r.date', 'desc')
                ;
        
        $reportQuery = $qb->getQuery();
        $reportQuery->setFetchMode("Chill\ReportBundle\Entity\Report", "person", \Doctrine\ORM\Mapping\ClassMetadata::FETCH_EAGER);
        
        return $reportQuery->getResult();
    }
    
    private function count(array $terms)
    {
        $qb = $this->buildQuery($terms);
        
        $qb->select('COUNT(r.id)');
        
        return $qb->getQuery()->getSingleScalarResult();
    }
    
    
    /**
     * @param array $terms the terms
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function buildQuery(array $terms)
    {
        
        $query = $this->em->createQueryBuilder();
        
        $query->from('ChillReportBundle:Report', 'r');
        
        //throw a parsing exception if key 'date' and default is set
        if (array_key_exists('date', $terms) && $terms['_default'] !== '') {
            throw new ParsingException('You may not set a date argument and a date in default');
        }
        //throw a parsing exception if no argument except report
        if (!array_key_exists('date', $terms) && $terms['_default'] === '') {
            throw new ParsingException('You must provide either a date:YYYY-mm-dd argument or a YYYY-mm-dd default search');
        }
                
        
        if (array_key_exists('date', $terms)) {
            $query->andWhere($query->expr()->eq('r.date', ':date'))
                    ->setParameter('date', $this->parseDate($terms['date']))
                    ;
        } elseif (array_key_exists('_default', $terms)) {
            $query->andWhere($query->expr()->eq('r.date', ':date'))
                    ->setParameter('date', $this->parseDate($terms['_default']))
                    ;
        }
        
        return $query;
    }

    public function supports($domain)
    {
        return $domain === 'report';
    }

}
