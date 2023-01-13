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
     * @var string|null
     *
     * @ORM\Column(name="email1", type="string", nullable=true)
     */
    private $email1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password_tip_query", type="string", nullable=true)
     */
    private $password_tip_query;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password_tip_answer", type="string", nullable=true)
     */
    private $password_tip_answer;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enquete", type="boolean", options={"default":false})
     */
    private $enquete = false;

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    /**
     * Set email1.
     *
     * @param string $email1
     *
     * @return this
     */
    public function setEmail1($email1)
    {
        $this->email1 = $email1;

        return $this;
    }

    /**
     * Get email1.
     *
     * @return string
     */
    public function getEmail1()
    {
        return $this->email1;
    }

    /**
     * Set password_tip_query.
     *
     * @param string $passwordTipQuery
     *
     * @return this
     */
    public function setPasswordTipQuery($passwordTipQuery)
    {
        $this->password_tip_query = $passwordTipQuery;

        return $this;
    }

    /**
     * Get password_tip_query.
     *
     * @return string
     */
    public function getPasswordTipQuery()
    {
        return $this->password_tip_query;
    }

    /**
     * Set password_tip_answer.
     *
     * @param string $passwordTipAnswer
     *
     * @return this
     */
    public function setPasswordTipAnswer($passwordTipAnswer)
    {
        $this->password_tip_answer = $passwordTipAnswer;

        return $this;
    }

    /**
     * Get password_tip_answer.
     *
     * @return string
     */
    public function getPasswordTipAnswer()
    {
        return $this->password_tip_answer;
    }
    
    /**
     * Set enquete.
     *
     * @param boolean $enquete
     *
     * @return this
     */
    public function setEnquete($enquete)
    {
        $this->enquete = $enquete;

        return $this;
    }

    /**
     * Get enquete.
     *
     * @return boolean
     */
    public function getEnquete()
    {
        return $this->enquete;
    }
}