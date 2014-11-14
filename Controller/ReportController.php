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

use Chill\ReportBundle\Entity\Report;
use Chill\ReportBundle\Form\ReportType;

/**
 * Report controller.
 *
 */
class ReportController extends Controller
{

    /**
     * Lists all Report entities.
     *
     */
    public function listAction($person_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $reports = $em->getRepository('ChillReportBundle:Report')->findByPerson($person_id);

        $person = $em->getRepository('ChillPersonBundle:Person')->find($person_id);

        return $this->render('ChillReportBundle:Report:list.html.twig', array(
            'reports' => $reports,
            'person'   => $person
        ));
    }


    /**
     * Select the type of the Report
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

        $person = $em->getRepository('ChillPersonBundle:Person')->find($person_id);

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

        return $this->render('ChillReportBundle:Report:select_report_type.html.twig', array(
            'form'     => $form->createView(),
            'person'   => $person
        ));
    }

    /**
     * Displays a form to create a new Report entity.
     *
     */
    public function newAction($person_id, $cf_group_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $person = $em->getRepository('ChillPersonBundle:Person')->find($person_id);

        $entity = new Report();
        $entity->setUser($this->get('security.context')->getToken()->getUser());

        $cFGroup = $em->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')->find($cf_group_id);
        $entity->setCFGroup($cFGroup);

        $form = $this->createCreateForm($entity, $person_id, $cFGroup);

        return $this->render('ChillReportBundle:Report:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'person'   => $person
        ));
    }

    /**
     * Creates a new Report entity.
     *
     */
    public function createAction($person_id, $cf_group_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new Report();
        $cFGroup = $em->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')->find($cf_group_id);

        $form = $this->createCreateForm($entity, $person_id, $cFGroup);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $cFGroup = $em->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')->find($cf_group_id);
            $entity->setCFGroup($cFGroup);

            $person = $em->getRepository('ChillPersonBundle:Person')->find($person_id);
            $entity->setPerson($person);
            
            $em->persist($entity);
            $em->flush();

            $this->get('session')
                ->getFlashBag()
                ->add('success', 
                    $this->get('translator')
                    ->trans('Report created')
                );

            return $this->redirect($this->generateUrl('report_view', 
                array('person_id' => $person_id,'report_id' => $entity->getId())));
        }

        $person = $em->getRepository('ChillPersonBundle:Person')->find($person_id);

        $this->get('session')
            ->getFlashBag()->add('danger', 'Errors : the report has not been created !');

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
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Report $entity, $person_id, $cFGroup)
    {
        $form = $this->createForm(new ReportType(), $entity, array(
            'action' => $this->generateUrl('report_create', 
                array('person_id' => $person_id, 'cf_group_id' => $cFGroup->getId())),
            'method' => 'POST',
            'em' => $this->getDoctrine()->getManager(),
            'cFGroup' => $cFGroup,
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Finds and displays a Report entity.
     *
     */
    public function viewAction($report_id, $person_id)
    {
        $em = $this->getDoctrine()->getManager();

        $person = $em->getRepository('ChillPersonBundle:Person')->find($person_id);

        $entity = $em->getRepository('ChillReportBundle:Report')->find($report_id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Report entity.');
        }

        return $this->render('ChillReportBundle:Report:view.html.twig', array(
            'entity' => $entity,
            'person' => $person,
        ));
    }

    /**
     * Displays a form to edit an existing Report entity.
     *
     */
    public function editAction($person_id, $report_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $report = $em->getRepository('ChillReportBundle:Report')->find($report_id);

        if (!$report) {
            throw $this->createNotFoundException('Unable to find the report.');
        }

        if(intval($person_id) !== intval($report->getPerson()->getId())) {
            throw new Exception("This is not the report of the person", 1);
        }

        $person = $report->getPerson();

        $editForm = $this->createEditForm($report, $person->getId());

        return $this->render('ChillReportBundle:Report:edit.html.twig', array(
            'edit_form'   => $editForm->createView(),
            'person' => $person,
        ));
    }

    /**
     * Creates a form to edit a Report entity.
     *
     * @param Report $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Report $entity, $person_id)
    {
        $form = $this->createForm(new ReportType(), $entity, array(
            'action' => $this->generateUrl('report_update', 
                array('person_id' => $person_id, 'report_id' => $entity->getId())),
            'method' => 'PUT',
            'em' => $this->getDoctrine()->getManager(),
            'cFGroup' => $entity->getCFGroup(),
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Report entity.
     *
     */
    public function updateAction($person_id, $report_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $report = $em->getRepository('ChillReportBundle:Report')->find($report_id);

        if (!$report) {
            throw $this->createNotFoundException('Unable to find the report '.$report_id.'.');
        }

        $editForm = $this->createEditForm($report, $person_id);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            $this->get('session')
                ->getFlashBag()
                ->add('success', 
                    $this->get('translator')
                    ->trans('Report updated')
                );

            return $this->redirect($this->generateUrl('report_view', 
                array('person_id' => $report->getPerson()->getId(), 'report_id' => $report_id)));
        }

        $errors = $editForm->getErrorsAsString();

        $this->get('session')
            ->getFlashBag()->add('danger', 'Errors : the report has not been updated !');

        return $this->render('ChillReportBundle:Report:edit.html.twig', array(
            'edit_form'   => $editForm->createView(),
            'person' => $report->getPerson()
        ));
    }
}
