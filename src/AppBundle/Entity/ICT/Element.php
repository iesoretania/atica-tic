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

namespace AppBundle\Entity\ICT;

use AppBundle\Entity\Organization;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ICT\ElementRepository")
 * @ORM\Table(name="ict_element",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="reference_idx", columns={"organization_id", "reference"})})
 * @UniqueEntity(fields={"organization", "reference"}, message="reference.duplicated")
 */
class Element
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organization")
     * @ORM\JoinColumn(nullable=false)
     * @var Organization
     */
    private $organization;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\ICT\Location")
     * @ORM\JoinColumn(nullable=true)
     * @var Location
     */
    private $location;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $reference;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $detail;

    /**
     * @ORM\ManyToOne(targetEntity="ElementTemplate")
     * @ORM\JoinColumn(nullable=true)
     * @var ElementTemplate
     */
    private $template;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $serialNumber;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $listedOn;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $delistedOn;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $unavailableSince;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $taintedSince;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $beingRepairedSince;


    public function __toString()
    {
        return $this->getName() . ($this->getDescription() ? ' - ' . $this->getDescription() : '') . ($this->getReference() ? ' - ' . $this->getReference() : '');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     * @return Element
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     * @return Element
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Element
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     * @return Element
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Element
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @param string $detail
     * @return Element
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
        return $this;
    }

    /**
     * @return ElementTemplate
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param ElementTemplate $template
     * @return Element
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function getSerialNumber()
    {
        return $this->serialNumber;
    }

    /**
     * @param string $serialNumber
     * @return Element
     */
    public function setSerialNumber($serialNumber)
    {
        $this->serialNumber = $serialNumber;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getListedOn()
    {
        return $this->listedOn;
    }

    /**
     * @param \DateTime|null $listedOn
     * @return Element
     */
    public function setListedOn($listedOn = null)
    {
        $this->listedOn = $listedOn;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDelistedOn()
    {
        return $this->delistedOn;
    }

    /**
     * @param \DateTime|null $delistedOn
     * @return Element
     */
    public function setDelistedOn($delistedOn = null)
    {
        $this->delistedOn = $delistedOn;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUnavailableSince()
    {
        return $this->unavailableSince;
    }

    /**
     * @param \DateTime|null $unavailableSince
     * @return Element
     */
    public function setUnavailableSince($unavailableSince = null)
    {
        $this->unavailableSince = $unavailableSince;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTaintedSince()
    {
        return $this->taintedSince;
    }

    /**
     * @param \DateTime|null $taintedSince
     * @return Element
     */
    public function setTaintedSince($taintedSince = null)
    {
        $this->taintedSince = $taintedSince;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getBeingRepairedSince()
    {
        return $this->beingRepairedSince;
    }

    /**
     * @param \DateTime|null $beingRepairedSince
     * @return Element
     */
    public function setBeingRepairedSince($beingRepairedSince = null)
    {
        $this->beingRepairedSince = $beingRepairedSince;
        return $this;
    }
}
