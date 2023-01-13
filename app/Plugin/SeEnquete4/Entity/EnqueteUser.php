<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EnqueteUser
 *
 * @ORM\Table(name="plg_se_enquete_user")
 * @ORM\Entity(repositoryClass="Plugin\SeEnquete4\Repository\EnqueteUserRepository")
 */
class EnqueteUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Plugin\SeEnquete4\Entity\Enquete
     *
     * @ORM\ManyToOne(targetEntity="Plugin\SeEnquete4\Entity\Enquete", inversedBy="EnqueteUsers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="enquete_id", referencedColumnName="id")
     * })
     */
    private $Enquete;

    /**
     * @var int
     *
     * @ORM\Column(name="customer_id", type="integer")
     */
    private $customer_id = 0;

    /**
     * @var json
     *
     * @ORM\Column(name="answer_json", type="json", nullable=true)
     */
    private $answer_json;

    /**
     * @var text
     *
     * @ORM\Column(name="user_agent", type="text", nullable=true)
     */
    private $user_agent;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255)
     */
    private $ip;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * @var int
     *
     * @ORM\Column(name="deleted", type="integer")
     */
    private $deleted;

    private $Customer;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Plugin\SeEnquete4\Entity\Enquete|null
     */
    public function getEnquete()
    {
        return $this->Enquete;
    }

    /**
      * @param \Plugin\SeEnquete4\Entity\Enquete|null $enquete
      *
      * @return Enquete
      */
    public function setEnquete(\Plugin\SeEnquete4\Entity\Enquete $enquete = null)
    {
        $this->Enquete = $enquete;

        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * @param int $customer_id
     *
     * @return $this
     */
    public function setCustomerId($customer_id)
    {
        $this->customer_id = $customer_id;

        return $this;
    }

    /**
     * @return array
     */
    public function getAnswerJson()
    {
        return json_decode($this->answer_json, true);
    }

    /**
     * @param array $answer_json
     *
     * @return $this;
     */
    public function setAnswerJson(array $answer_json)
    {
        $this->answer_json = ( !empty($answer_json) ) ? json_encode($answer_json) : null ;

        return $this;
    }

    /**
     * @return text
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * @param text $user_agent
     *
     * @return $this;
     */
    public function setUserAgent($user_agent)
    {
        $this->user_agent = $user_agent;

        return $this;
    }

    /**
     * @return string
     */
    public function getIP()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return $this;
     */
    public function setIP($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * @param \DateTime $create_date
     *
     * @return $this;
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * @param \DateTime $update_date
     *
     * @return $this;
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * @return int
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param integer $deleted
     *
     * @return $this;
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * @return \Eccube\Entity\Customer|null
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
      * @param \Eccube\Entity\Customer|null $enquete
      *
      * @return Customer
      */
    public function setCustomer(\Eccube\Entity\Customer $customer = null)
    {
        $this->Customer = $customer;

        return $this;
    }

}
