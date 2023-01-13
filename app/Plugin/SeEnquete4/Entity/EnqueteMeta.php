<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EnqueteMeta
 *
 * @ORM\Table(name="plg_se_enquete_meta")
 * @ORM\Entity(repositoryClass="Plugin\SeEnquete4\Repository\EnqueteMetaRepository")
 */
class EnqueteMeta
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
     * @ORM\ManyToOne(targetEntity="Plugin\SeEnquete4\Entity\Enquete", inversedBy="EnqueteMetas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="enquete_id", referencedColumnName="id")
     * })
     */
    private $Enquete;

    /**
     * @var \Plugin\SeEnquete4\Entity\EnqueteConfig
     *
     * @ORM\ManyToOne(targetEntity="Plugin\SeEnquete4\Entity\EnqueteConfig", inversedBy="EnqueteMetas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="config_id", referencedColumnName="id")
     * })
     */
    private $EnqueteConfig;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var text
     *
     * @ORM\Column(name="text", type="text", nullable=true)
     */
    private $text;

    /**
     * @var text
     *
     * @ORM\Column(name="placeholder", type="text", nullable=true)
     */
    private $placeholder;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer")
     */
    private $sort_no;

    /**
     * @var int
     *
     * @ORM\Column(name="ness_flg", type="integer")
     */
    private $ness_flg = 0;

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
     * @return \Plugin\SeEnquete4\Entity\EnqueteConfig|null
     */
    public function getEnqueteConfig()
    {
        return $this->EnqueteConfig;
    }

    /**
      * @param \Plugin\SeEnquete4\Entity\EnqueteConfig|null $enquete_config
      *
      * @return EnqueteConfig
      */
    public function setEnqueteConfig(\Plugin\SeEnquete4\Entity\EnqueteConfig $enquete_config = null)
    {
        $this->EnqueteConfig = $enquete_config;

        return $this;
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
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param text $text
     *
     * @return $this;
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return text
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * @param text $placeholder
     *
     * @return $this;
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * @return int
     */
    public function getSortNo()
    {
        return $this->sort_no;
    }

    /**
     * @param int $sortNo
     *
     * @return $this
     */
    public function setSortNo($sortNo)
    {
        $this->sort_no = $sortNo;

        return $this;
    }

    /**
     * @return int
     */
    public function getNessFlg()
    {
        return $this->ness_flg;
    }

    /**
     * @param int $ness_flg
     *
     * @return $this
     */
    public function setNessFlg($ness_flg)
    {
        $this->ness_flg = $ness_flg;

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
