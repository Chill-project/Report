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
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ChillReportBundle:Report')->findAll();

        $cFGroups = $em->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')
            ->findByEntity('Chill\ReportBundle\Entity\Report');

        $cFGroupsChoice = array();

        foreach ($cFGroups as $cFGroup) {
            $cFGroupsChoice[$cFGroup->getId()] = $cFGroup->getName($request->getLocale());
        }

        $form = $this->get('form.factory')
            ->createNamedBuilder(null, 'form', null, array(
                'method' => 'GET',
                'action' => $this->generateUrl('report_new'),
                'csrf_protection' => false
            ))
            ->add('cFGroup', 'choice', array(
                'choices' => $cFGroupsChoice
            ))
            ->getForm();

        return $this->render('ChillReportBundle:Report:index.html.twig', array(
            'entities' => $entities,
            'form'     => $form->createView()
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

        $cFGroups = $em->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')
            ->findByEntity('Chill\ReportBundle\Entity\Report');

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
            'form'     => $form->createView()
        ));
    }

    /**
     * Displays a form to create a new Report entity.
     *
     */
    public function newAction($person_id, $cf_group_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new Report();

        $cFGroup = $em->getRepository('ChillCustomFieldsBundle:CustomFieldsGroup')->find($cf_group_id);
        $entity->setCFGroup($cFGroup);

        $form = $this->createCreateForm($entity, $person_id, $cFGroup);

        return $this->render('ChillReportBundle:Report:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
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
        
            $user = $this->get('security.context')->getToken()->getUser();
            $entity->setUser($user);
            
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('report_show', array('id' => $entity->getId())));
        }

        return $this->render('ChillReportBundle:Report:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
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
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ChillReportBundle:Report')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Report entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ChillReportBundle:Report:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Report entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ChillReportBundle:Report')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Report entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ChillReportBundle:Report:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Report entity.
    *
    * @param Report $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Report $entity)
    {
        $form = $this->createForm(new ReportType(), $entity, array(
            'action' => $this->generateUrl('report_update', array('id' => $entity->getId())),
            'method' => 'PUT',
            'em' => $this->getDoctrine()->getManager(),
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Report entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ChillReportBundle:Report')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Report entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('report_edit', array('id' => $id)));
        }

        return $this->render('ChillReportBundle:Report:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Report entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ChillReportBundle:Report')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Report entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('report'));
    }

    /**
     * Creates a form to delete a Report entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('report_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
