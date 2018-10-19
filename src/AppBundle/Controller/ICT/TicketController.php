<?php
/*
  Copyright (C) 2018: Luis Ramón López López

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see [http://www.gnu.org/licenses/].
*/

namespace AppBundle\Controller\ICT;

use AppBundle\Entity\ICT\Ticket;
use AppBundle\Entity\Organization;
use AppBundle\Form\Type\ICT\TicketType;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\TicketVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class TicketController extends Controller
{
    /**
     * @Route("/tic/incidencia/nueva", name="ict_ticket_form_new", methods={"GET", "POST"})
     * @Route("/tic/incidencia/{id}", name="ict_ticket_form_edit", methods={"GET", "POST"})
     */
    public function ictTicketFormAction(
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Request $request,
        Ticket $ticket = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $admin = $this->isGranted(OrganizationVoter::MANAGE, $organization);
        $em = $this->getDoctrine()->getManager();

        if (null === $ticket) {
            $ticket = new Ticket();
            $ticket
                ->setOrganization($organization)
                ->setCreatedBy($this->getUser())
                ->setCreatedOn(new \DateTime())
                ->setLastUpdatedOn(new \DateTime());

            $em->persist($ticket);
        } else {
            $this->denyAccessUnlessGranted(TicketVoter::ACCESS, $ticket);
        }

        $disabled = false === $this->isGranted(TicketVoter::MANAGE, $ticket);
        $form = $this->createForm(TicketType::class, $ticket, [
            'disabled' => $disabled
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'ict_ticket'));
                return $this->redirectToRoute($admin ? 'ict_ticket_list' : 'ict_menu');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.save_error', [], 'ict_ticket'));
            }
        }

        return $this->render('ict/ticket/form.html.twig', [
            'menu_path' => $admin ? 'ict_ticket_list' : 'ict_menu',
            'breadcrumb' => [
                ['fixed' => $ticket->getId() ?
                    $translator->trans('title.edit', ['%id%' => $ticket->getId()], 'ict_ticket') :
                    $translator->trans('title.new', [], 'ict_ticket')]
            ],
            'title' => $translator->
            trans($ticket->getId() ? 'title.edit' : 'title.new', [], 'ict_ticket'),
            'form' => $form->createView(),
            'disabled' => $disabled
        ]);
    }

    /**
     * @Route("/listar/{page}", name="ict_ticket_list", requirements={"page" = "\d+"},
     *     defaults={"page" = "1"}, methods={"GET"})
     */
    public function listAction($page, Request $request, UserExtensionService $userExtensionService, TranslatorInterface $translator)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $admin = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->from('AppBundle:ICT\Ticket', 't')
            ->leftJoin('t.createdBy', 'u')
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.dueOn', 'DESC')
            ->addOrderBy('t.createdOn', 'DESC');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->orWhere('t.id = :q')
                ->orWhere('t.description LIKE :tq')
                ->orWhere('u.firstName LIKE :tq')
                ->orWhere('u.lastName LIKE :tq')
                ->setParameter('q', $q)
                ->setParameter('tq', '%' . $q . '%');
        }

        $queryBuilder
            ->andWhere('t.organization = :organization')
            ->setParameter('organization', $userExtensionService->getCurrentOrganization());

        if (false === $admin) {
            $queryBuilder
                ->andWhere('t.createdBy = :user')
                ->orWhere('t.assignee = :user')
                ->setParameter('user', $this->getUser());
        }
        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $translator->trans('title.list', [], 'ict_ticket');

        return $this->render('ict/ticket/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'ict_ticket'
        ]);
    }

    /**
     * @Route("/operacion", name="ict_ticket_operation", methods={"POST"})
     */
    public function operationAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator)
    {
        $organization = $userExtensionService->getCurrentOrganization();

        list($redirect, $items) = $this->processOperations($request, $organization);

        if ($redirect) {
            return $this->redirectToRoute('ict_ticket_list');
        }

        return $this->render('ict/ticket/delete.html.twig', [
            'menu_path' => 'ict_ticket_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'ict_ticket')]],
            'title' => $translator->trans('title.delete', [], 'ict_ticket'),
            'items' => $items
        ]);
    }

    /**
     * Borrar los datos de las incidencias que han sido pasadas como parámetros
     *
     * @param Ticket[] $items
     */
    private function deleteItems($items)
    {
        $em = $this->getDoctrine()->getManager();

        /* Finalmente eliminamos los elementos */
        $em->createQueryBuilder()
            ->delete('AppBundle:ICT\Ticket', 't')
            ->where('t IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    /**
     * @param Request $request
     * @param $items
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     * @return bool
     */
    private function processRemoveItems(Request $request, $items, $em)
    {
        $redirect = false;
        if ($request->get('confirm', '') === 'ok') {
            try {
                $this->deleteItems($items);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'ict_ticket'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'ict_ticket'));
            }
            $redirect = true;
        }
        return $redirect;
    }

    /**
     * @param Request $request
     * @return array
     */
    private function processOperations(Request $request, Organization $organization)
    {
        $em = $this->getDoctrine()->getManager();

        $redirect = false;

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            $redirect = true;
        }

        $selectedItems = [];
        if (!$redirect) {
            $selectedItems = $em->getRepository('AppBundle:ICT\Ticket')->
            findInListByIdAndOrganization($items, $organization);
            $redirect = $this->processRemoveItems($request, $selectedItems, $em);
        }
        return array($redirect, $selectedItems);
    }
}
