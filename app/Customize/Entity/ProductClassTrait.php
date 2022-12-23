<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
  * @EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{
    private $default_price_inc_tax = null;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $default_price;
    
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $initial_breakdown;
    
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $monthly_breakdown;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maintenance_pack;
    
    /**
     * Set default_price IncTax
     *
     * @return ProductClass
     */
    public function setDefaultPriceIncTax($default_price_inc_tax)
    {
        $this->default_price_inc_tax = $default_price_inc_tax;

        return $this;
    }

    /**
     * Get default_price IncTax
     *
     * @return string
     */
    public function getDefaultPriceIncTax()
    {
        return $this->default_price_inc_tax;
    }
    
    /**
     * Get default_price
     * 
     * @return integer
     */
    public function getDefaultPrice()
    {
        return $this->default_price;
    }

    /**
     * @param  integer  $default_price
     *
     * @return this
     */
    public function setDefaultPrice($default_price)
    {
        $this->default_price = $default_price;
        
        return $this;
    }
    
    /**
     * Get initial_breakdown
     * 
     * @return string
     */
    public function getInitialBreakdown()
    {
        return $this->initial_breakdown;
    }

    /**
     * @param  string  $initial_breakdown
     *
     * @return this
     */
    public function setInitialBreakdown($initial_breakdown)
    {
        $this->initial_breakdown = $initial_breakdown;
        
        return $this;
    }
    
    /**
     * Get monthly_breakdown
     * 
     * @return integer
     */
    public function getMonthlyBreakdown()
    {
        return $this->monthly_breakdown;
    }

    /**
     * @param  integer  $monthly_breakdown
     *
     * @return this
     */
    public function setMonthlyBreakdown($monthly_breakdown)
    {
        $this->monthly_breakdown = $monthly_breakdown;
        
        return $this;
    }
    
    /**
     * Get maintenance_pack
     * 
     * @return integer
     */
    public function getMaintenancePack()
    {
        return $this->maintenance_pack;
    }

    /**
     * @param  integer  $maintenance_pack
     *
     * @return this
     */
    public function setMaintenancePack($maintenance_pack)
    {
        $this->maintenance_pack = $maintenance_pack;
        
        return $this;
    }
}