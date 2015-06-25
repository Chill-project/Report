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

namespace Chill\ReportBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Chill\PersonBundle\Entity\Person;
use Chill\ReportBundle\Entity\Report;
use Chill\ReportBundle\Form\ReportType;
use Symfony\Component\Security\Core\Role\Role;

/**
 * Report controller.
 *
 */
class ReportController extends Controller
{
    /**
     * List all the report entities for a given person.
     *
     * @param integer $person_id The id of the person.
     * @param Request $request The request
     * @return Response The web page.
     */
    public function listAction($person_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $person = $em->getRepository('ChillPersonBundle:Person')->find($person_id);
        
        $this->denyAccessUnlessGranted('CHILL_PERSON_SEE', $person);
        
        $reachableScopes = $this->get('chill.main.security.authorization.helper')
                ->getReachableScopes($this->getUser(), new Role('CHILL_REPORT_SEE'),
                        $person->getCenter());
        $reports = $em->getRepository('ChillReportBundle:Report')
                ->findBy(array('person' => $person, 'scope' => $reachableScopes));

        return $this->render('ChillReportBundle:Report:list.html.twig', array(
            'reports' => $reports,
            'person'   => $person
        ));
    }

    /**
     * Display a form for selecting which type of report to add for a given person
     *
     * @param integer $person_id The id of the person.
     * @param Request $request The request
     * @return Response The web page.
     */
    public function selectReportTypeAction($person_id, Request $request)
    {
        $cFGroupId = $request->query->get('cFGroup');

        if($cFGroupId) {
            return $this->redirect(
                $this->generateUrl('report_new',
                    array('person_id' => $person_id, 'cf_group_id' => $cFGroupId)));
        }

        $em = $this->getDoctrine()->getManager();

        $cFGroups = $em->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')
            ->findByEntity('Chill\ReportBundle\Entity\Report');

        if(count($cFGroups) === 1 ){
            return $this->redirect(
                $this->generateUrl('report_new', 
                    array('person_id' => $person_id, 'cf_group_id' => $cFGroups[0]->getId())));
        }

        $cFGroupsChoice = array();

        foreach ($cFGroups as $cFGroup) {
            $cFGroupsChoice[$cFGroup->getId()] = $cFGroup->getName($request->getLocale());
        }

        $form = $this->get('form.factory')
            ->createNamedBuilder(null, 'form', null, array(
                'method' => 'GET',
                'csrf_protection' => false
            ))
            ->add('cFGroup', 'choice', array(
                'choices' => $cFGroupsChoice
            ))
            ->getForm();

        $person = $em->getRepository('ChillPersonBundle:Person')->find($person_id);

        return $this->render('ChillReportBundle:Report:select_report_type.html.twig', array(
            'form'     => $form->createView(),
            'person'   => $person
        ));
    }

    /**
     * Display a form for selecting which type of report to export 
     * (a csv file with all the report of this type)
     *
     * @param Request $request The request
     * @return Response The web page.
     */
    public function selectReportTypeForExportAction(Request $request)
    {
        $cFGroupId = $request->query->get('cFGroup');

        if($cFGroupId) {
            return $this->redirect(
                $this->generateUrl('report_export_list', 
                    array('cf_group_id' => $cFGroupId)));
        }

        $em = $this->getDoctrine()->getManager();

        $cFGroups = $em->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')
            ->findByEntity('Chill\ReportBundle\Entity\Report');

        if(count($cFGroups) === 1 ){
            return $this->redirect(
                $this->generateUrl('report_export_list',
                    array('cf_group_id' => $cFGroups[0]->getId())));
        }

        $cFGroupsChoice = array();

        foreach ($cFGroups as $cFGroup) {
            $cFGroupsChoice[$cFGroup->getId()] = $cFGroup->getName($request->getLocale());
        }

        $form = $this->get('form.factory')
            ->createNamedBuilder(null, 'form', null, array(
                'method' => 'GET',
                'csrf_protection' => false
            ))
            ->add('cFGroup', 'choice', array(
                'choices' => $cFGroupsChoice
            ))
            ->getForm();

        return $this->render('ChillReportBundle:Report:select_report_type_for_export.html.twig', array(
            'form'     => $form->createView(),
            'layout_name' => "ChillMainBundle::Export/layout.html.twig"
        ));
    }

    /**
     * Return a csv file with all the reports of a given type
     *
     * @param integer $cf_group_id The id of the report type to export
     * @param Request $request The request
     * @return A csv file with all the reports of the selected type 
     */
    public function exportAction($cf_group_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $cFGroup = $em->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')->find($cf_group_id);
        $reports = $em->getRepository('ChillReportBundle:Report')->findByCFGroup($cFGroup);


        $response = $this->render('ChillReportBundle:Report:export.csv.twig', array(
            'reports' => $reports,
            'cf_group' => $cFGroup
        ));

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');
        return $response;
    }

    /**
     * Display a form for creating a new report for a given person and of a given type
     *
     * @param integer $person_id The id of the person.
     * @param integer $cf_group_id The id of the report type.
     * @param Request $request The request
     * @return Response The web page.
     */
    public function newAction($person_id, $cf_group_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $person = $em->getRepository('ChillPersonBundle:Person')->find($person_id);        
        $cFGroup = $em
                ->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')
                ->find($cf_group_id);
        
        if ($person === NULL) {
            throw $this->createNotFoundException("Person not found");
        }
        
        $this->denyAccessUnlessGranted('CHILL_PERSON_SEE', $person);
        
        if ($cFGroup === NULL){
            throw $this->createNotFoundException("custom fields group not found");
        }

        $entity = new Report();
        $entity->setUser($this->get('security.context')->getToken()->getUser());
        $entity->setDate(new \DateTime('now'));

        $entity->setCFGroup($cFGroup);

        $form = $this->createCreateForm($entity, $person, $cFGroup);

        return $this->render('ChillReportBundle:Report:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'person'   => $person
        ));
    }

    /**
     * Create a new report for a given person and of a given type
     *
     * @param integer $person_id The id of the person.
     * @param integer $cf_group_id The id of the report type.
     * @param Request $request The request containing the form data (from the newAction)
     * @return Response The web page.
     */
    public function createAction($person_id, $cf_group_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new Report();
        $cFGroup = $em->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')
              ->find($cf_group_id);
        
        $person = $em->getRepository('ChillPersonBundle:Person')
              ->find($person_id);
        
        if($person === NULL || $cFGroup === NULL) {
            throw $this->createNotFoundException();
        }
        
        $this->denyAccessUnlessGranted('CHILL_PERSON_SEE', $person);

        $form = $this->createCreateForm($entity, $person, $cFGroup);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity->setCFGroup($cFGroup);
            $entity->setPerson($person);
            
            $this->denyAccessUnlessGranted('CHILL_REPORT_CREATE', $entity);
            
            $em->persist($entity);
            $em->flush();

            $this->get('session')
                ->getFlashBag()
                ->add('success', 
                    $this->get('translator')
                        ->trans('Success : report created!')
                );

            return $this->redirect($this->generateUrl('report_view', 
                array('person_id' => $person_id,'report_id' => $entity->getId())));
        }


        $this->get('session')
            ->getFlashBag()->add('danger',
                $this->get('translator')
                    ->trans('The form is not valid. The report has not been created !')
            );

        return $this->render('ChillReportBundle:Report:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'person' => $person
        ));
    }

    /**
     * Creates a form to create a Report entity.
     *
     * @param Report $entity The entity
     * @param integer $person_id The id of the person.
     * @param integer $cf_group_id The id of the report type.
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Report $entity, Person $person, $cFGroup)
    {
        $form = $this->createForm('chill_reportbundle_report', $entity, array(
            'action' => $this->generateUrl('report_create', 
                array('person_id' => $person->getId(), 
                          'cf_group_id' => $cFGroup->getId())),
            'method' => 'POST',
            'cFGroup' => $cFGroup,
            'role' => new Role('CHILL_REPORT_CREATE'),
            'center' => $person->getCenter()
        ));

        return $form;
    }

    /**
     * Find and display a report.
     *
     * @param integer $report_id The id of the report.
     * @param integer $person_id The id of the person.
     * @return Response The web page.
     */
    public function viewAction($report_id, $person_id)
    {
        $em = $this->getDoctrine()->getManager();

        $person = $em->getRepository('ChillPersonBundle:Person')->find($person_id);

        $entity = $em->getRepository('ChillReportBundle:Report')->find($report_id);

        if (!$entity || !$person) {
            throw $this->createNotFoundException(
                $this->get('translator')->trans('Unable to find this report.'));
        }
        
        $this->denyAccessUnlessGranted('CHILL_REPORT_SEE', $entity);

        return $this->render('ChillReportBundle:Report:view.html.twig', array(
            'entity' => $entity,
            'person' => $person,
        ));
    }

    /**
     * Display a form to edit an existing Report entity.
     *
     * @param integer $person_id The id of the person.
     * @param integer $report_id The id of the report.
     * @return Response The web page.
     */
    public function editAction($person_id, $report_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $report = $em->getRepository('ChillReportBundle:Report')->find($report_id);

        if (!$report) {
            throw $this->createNotFoundException(
                $this->get('translator')->trans('Unable to find this report.'));
        }

        if(intval($person_id) !== intval($report->getPerson()->getId())) {
            throw new \RuntimeException(
                $this->get('translator')->trans('This is not the report of the person.'), 1);
        }
        
        $this->denyAccessUnlessGranted('CHILL_REPORT_UPDATE', $report);

        $person = $report->getPerson();

        $editForm = $this->createEditForm($report);

        return $this->render('ChillReportBundle:Report:edit.html.twig', array(
            'edit_form'   => $editForm->createView(),
            'person' => $person,
        ));
    }

    /**
     * Creates a form to edit a Report entity.
     *
     * @param Report $entity The report to edit.
     * @param integer $person_id The id of the person.
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Report $entity)
    {
        $form = $this->createForm('chill_reportbundle_report', $entity, array(
            'action' => $this->generateUrl('report_update', 
                array('person_id' => $entity->getPerson()->getId(), 
                    'report_id' => $entity->getId())),
            'method' => 'PUT',
            'cFGroup' => $entity->getCFGroup(),
            'role' => new Role('CHILL_REPORT_UPDATE'),
            'center' => $entity->getPerson()->getCenter()
        ));

        return $form;
    }

    /**
     * Web page for editing an existing report.
     *
     * @param integer $person_id The id of the person.
     * @param integer $report_id The id of the report.
     * @return Response The web page.
     */
    public function updateAction($person_id, $report_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $report = $em->getRepository('ChillReportBundle:Report')->find($report_id);

        if (!$report) {
            throw $this->createNotFoundException(
                $this->get('translator')->trans('Unable to find this report.'));
        }
        
        $this->denyAccessUnlessGranted('CHILL_REPORT_UPDATE', $report);

        $editForm = $this->createEditForm($report);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->get('session')
                ->getFlashBag()
                ->add('success', 
                    $this->get('translator')
                        ->trans('Success : report updated!')
                );

            return $this->redirect($this->generateUrl('report_view', 
                array('person_id' => $report->getPerson()->getId(), 'report_id' => $report_id)));
        }

        $this->get('session')
            ->getFlashBag()
            ->add('danger',
                $this->get('translator')
                    ->trans('The form is not valid. The report has not been updated !')
            );

        return $this->render('ChillReportBundle:Report:edit.html.twig', array(
            'edit_form'   => $editForm->createView(),
            'person' => $report->getPerson()
        ));
    }
}
