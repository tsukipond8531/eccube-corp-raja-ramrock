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
     * @var string|null
     *
     * @ORM\Column(name="image1", type="string", nullable=true)
     */
    private $image1;
    
    /**
     * @var string|null
     *
     * @ORM\Column(name="image2", type="string", nullable=true)
     */
    private $image2;

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

    /**
     * Set image1.
     *
     * @param string $image1
     *
     * @return this
     */
    public function setImage1($image1)
    {
        $this->image1 = $image1;

        return $this;
    }

    /**
     * Get image1.
     *
     * @return string
     */
    public function getImage1()
    {
        return $this->image1;
    }

    /**
     * Set image2.
     *
     * @param string $image2
     *
     * @return this
     */
    public function setImage2($image2)
    {
        $this->image2 = $image2;

        return $this;
    }

    /**
     * Get image2.
     *
     * @return string
     */
    public function getImage2()
    {
        return $this->image2;
    }
}