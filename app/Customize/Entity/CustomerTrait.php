<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Eccube\Entity\CustomerAddress;

/**
  * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
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