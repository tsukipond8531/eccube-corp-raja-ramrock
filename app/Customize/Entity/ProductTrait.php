<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
  * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $price_zero = false;
    
    /**
     * Get price_zero
     * 
     * @return boolean
     */
    public function getPriceZero()
    {
        return $this->price_zero;
    }

    /**
     * @param  boolean  $price_zero
     *
     * @return this
     */
    public function setPriceZero($price_zero)
    {
        $this->price_zero = $price_zero;
        
        return $this;
    }
}