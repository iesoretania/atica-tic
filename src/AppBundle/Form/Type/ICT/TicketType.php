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

namespace AppBundle\Form\Type\ICT;

use AppBundle\Entity\ICT\Priority;
use AppBundle\Entity\ICT\Ticket;
use AppBundle\Entity\ICT\Location;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    private $userExtensionService;

    private $entityManager;

    public function __construct(UserExtensionService $userExtensionService, EntityManagerInterface $locationRepository)
    {
        $this->userExtensionService = $userExtensionService;
        $this->entityManager = $locationRepository;
    }

    public function addElements(FormInterface $form, $options, $persons, Location $location = null)
    {
        $elements = null === $location ?
            [] :
            $this->entityManager->getRepository('AppBundle:ICT\Element')->findByLocation($location);

        $placeholder = (count($elements) === 0) ? 'form.no_elements' : 'form.select_element';

        $locations = $this->entityManager->getRepository(Location::class)->
            findVisibleByOrganization($this->userExtensionService->getCurrentOrganization());

        $admin = $this->userExtensionService->isUserLocalAdministrator();

        if (true === $admin || false === $options['new']) {
            $form
                ->add('createdOn', null, [
                    'label' => 'form.created_on',
                    'widget' => 'single_text',
                    'required' => true,
                    'disabled' => false === $admin
                ]);
        }

        $form
            ->add('location', EntityType::class, [
                'label' => 'form.location',
                'class' => Location::class,
                'choice_translation_domain' => false,
                'choices' => $locations,
                'mapped' => false,
                'data' => $location,
                'placeholder' => 'form.select_location'
            ])
            ->add('element', null, [
                'label' => 'form.element',
                'choice_translation_domain' => false,
                'choices' => $elements,
                'placeholder' => $placeholder
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.description',
                'attr' => [
                    'rows' => 8
                ]
            ]);

        if (true === $admin || false === $options['new']) {
            $form
                ->add('priority', EntityType::class, [
                    'label' => 'form.priority',
                    'class' => Priority::class,
                    'choice_translation_domain' => false,
                    'choices' => $this->entityManager->getRepository('AppBundle:ICT\Priority')->
                        findAllByOrganizationSortedByPriority($this->userExtensionService->getCurrentOrganization()),
                    'placeholder' => 'form.select_priority',
                    'required' => false,
                    'disabled' => false === $admin
                ])
                ->add('assignee', null, [
                    'label' => 'form.assignee',
                    'choice_translation_domain' => false,
                    'choices' => $persons,
                    'placeholder' => 'form.no_assignee',
                    'required' => false,
                    'disabled' => false === $admin
                ])
                ->add('dueOn', null, [
                    'label' => 'form.due_on',
                    'widget' => 'single_text',
                    'required' => false,
                    'disabled' => false === $admin
                ])
                ->add('closedOn', null, [
                    'label' => 'form.closed_on',
                    'widget' => 'single_text',
                    'required' => false,
                    'disabled' => false === $admin
                ])
                ->add('closedBy', null, [
                    'label' => 'form.closed_by',
                    'choice_translation_domain' => false,
                    'choices' => $persons,
                    'placeholder' => 'form.no_assignee',
                    'required' => false,
                    'disabled' => false === $admin
                ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $persons = $this->entityManager->getRepository('AppBundle:Person')->
            findAllByOrganizationSorted($this->userExtensionService->getCurrentOrganization());

        $builder
            ->add('createdBy', null, [
                'label' => 'form.created_by',
                'choices' => $persons,
                'disabled' => (false === $this->userExtensionService->isUserLocalAdministrator())
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options, $persons)  {
            $form = $event->getForm();
            $data = $event->getData();

            $location = $data->getElement() ? $data->getElement()->getLocation() : null;

            $this->addElements($form, $options, $persons, $location);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options, $persons) {

            $form = $event->getForm();
            $data = $event->getData();

            $location = isset($data['location']) ?
                $this->entityManager->getRepository('AppBundle:ICT\Location')->find($data['location']) :
                null;

            $this->addElements($form, $options, $persons, $location);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'translation_domain' => 'ict_ticket',
            'new' => false,
            'own' => false
        ]);
    }
}
