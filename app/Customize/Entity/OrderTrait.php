<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Eccube\Entity\CustomerAddress;

/**
  * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Eccube\Entity\CustomerAddress", mappedBy="Order", cascade={"persist","remove"})
     */
    private $Addresses;

    /**
     * Constructor
     */
    public function __construct(Master\OrderStatus $orderStatus = null)
    {
        $this->Addresses = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add customerAddress.
     *
     * @param \Eccube\Entity\CustomerAddress $customerAddress
     *
     * @return Product
     */
    public function addAddress(CustomerAddress $address)
    {
        $this->Addresses[] = $address;

        return $this;
    }

    /**
     * Remove customerAddress.
     *
     * @param \Eccube\Entity\CustomerAddress $customerAddress
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeAddress(CustomerAddress $address)
    {
        return $this->Addresses->removeElement($address);
    }

    /**
     * Get Addresses.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAddresses()
    {
        return $this->Addresses;
    }
}