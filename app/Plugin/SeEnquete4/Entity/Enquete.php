<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Enquete
 *
 * @ORM\Table(name="plg_se_enquete")
 * @ORM\Entity(repositoryClass="Plugin\SeEnquete4\Repository\EnqueteRepository")
 */
class Enquete
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
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var text
     *
     * @ORM\Column(name="sub_title", type="text", nullable=true)
     */
    private $sub_title;

    /**
     * @var int
     *
     * @ORM\Column(name="member_flg", type="integer")
     */
    private $member_flg;

    /**
     * @var int
     *
     * @ORM\Column(name="mail_flg", type="integer")
     */
    private $mail_flg;

    /**
     * @var text
     *
     * @ORM\Column(name="address_list", type="text", nullable=true)
     */
    private $address_list;

    /**
     * @var string
     *
     * @ORM\Column(name="thumbnail", type="string", length=255, nullable=true)
     */
    private $thumbnail;

    /**
     * @var int
     *
     * @ORM\Column(name="personal_flg", type="integer")
     */
    private $personal_flg;

    /**
     * @var string
     *
     * @ORM\Column(name="personal_title", type="string", length=255, nullable=true)
     */
    private $personal_title;

    /**
     * @var text
     *
     * @ORM\Column(name="personal_text", type="text", nullable=true)
     */
    private $personal_text;

    /**
     * @var string
     *
     * @ORM\Column(name="submit_title", type="string", length=255)
     */
    private $submit_title;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetimetz")
     */
    private $start_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetimetz")
     */
    private $end_date;

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

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this;
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return text
     */
    public function getSubTitle()
    {
        return $this->sub_title;
    }

    /**
     * @param text $sub_title
     *
     * @return $this;
     */
    public function setSubTitle($sub_title)
    {
        $this->sub_title = $sub_title;

        return $this;
    }

    /**
     * @return int
     */
    public function getMemberFlg()
    {
        return $this->member_flg;
    }

    /**
     * @param integer $member_flg
     *
     * @return $this;
     */
    public function setMemberFlg($member_flg)
    {
        $this->member_flg = $member_flg;

        return $this;
    }

    /**
     * @return int
     */
    public function getMailFlg()
    {
        return $this->mail_flg;
    }

    /**
     * @param integer $mail_flg
     *
     * @return $this;
     */
    public function setMailFlg($mail_flg)
    {
        $this->mail_flg = $mail_flg;

        return $this;
    }

    /**
     * @return int
     */
    public function getPersonalFlg()
    {
        return $this->personal_flg;
    }

    /**
     * @param integer $personal_flg
     *
     * @return $this;
     */
    public function setPersonalFlg($personal_flg)
    {
        $this->personal_flg = $personal_flg;

        return $this;
    }

    /**
     * @return text
     */
    public function getPersonalTitle()
    {
        return $this->personal_title;
    }

    /**
     * @param text $personal_title
     *
     * @return $this;
     */
    public function setPersonalTitle($personal_title)
    {
        $this->personal_title = $personal_title;

        return $this;
    }

    /**
     * @return text
     */
    public function getPersonalText()
    {
        return $this->personal_text;
    }

    /**
     * @param text $personal_text
     *
     * @return $this;
     */
    public function setPersonalText($personal_text)
    {
        $this->personal_text = $personal_text;

        return $this;
    }

    /**
     * @return text
     */
    public function getAddressList()
    {
        return $this->address_list;
    }

    /**
     * @param text $address_list
     *
     * @return $this;
     */
    public function setAddressList($address_list)
    {
        $this->address_list = $address_list;

        return $this;
    }

    /**
     * @return string
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * @param string $thumbnail
     *
     * @return $this;
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubmitTitle()
    {
        return $this->submit_title;
    }
    
    /**
     * @param string $submit_title
     *
     * @return $this;
     */ 
    public function setSubmitTitle($submit_title)
    {
        $this->submit_title = $submit_title;
     
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param integer $status
     *
     * @return $this;
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * @param \DateTime $start_date
     *
     * @return $this;
     */
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * @param \DateTime $end_date
     *
     * @return $this;
     */
    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;

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

}
