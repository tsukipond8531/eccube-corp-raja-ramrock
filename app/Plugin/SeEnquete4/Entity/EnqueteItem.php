<?php

/*
 * Copyright(c) 2020 Shadow Enterprise, Inc. All rights reserved.
 * http://www.shadow-ep.co.jp/
 */

namespace Plugin\SeEnquete4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EnqueteItem
 *
 * @ORM\Table(name="plg_se_enquete_item")
 * @ORM\Entity(repositoryClass="Plugin\SeEnquete4\Repository\EnqueteItemRepository")
 */
class EnqueteItem
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
     * @var \Plugin\SeEnquete4\Entity\EnqueteMeta
     *
     * @ORM\ManyToOne(targetEntity="Plugin\SeEnquete4\Entity\EnqueteMeta", inversedBy="EnqueteItems")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="meta_id", referencedColumnName="id")
     * })
     */
    private $EnqueteMeta;

    /**
     * @var text
     *
     * @ORM\Column(name="item_values", type="text", nullable=true)
     */
    private $values;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer")
     */
    private $sort_no;

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
     * @return \Plugin\SeEnquete4\Entity\EnqueteMeta|null
     */
    public function getEnqueteMeta()
    {
        return $this->EnqueteMeta;
    }

    /**
      * @param \Plugin\SeEnquete4\Entity\EnqueteMeta|null $enquete_meta
      *
      * @return EnqueteMeta
      */
    public function setEnqueteMeta(\Plugin\SeEnquete4\Entity\EnqueteMeta $enquete_meta = null)
    {
        $this->EnqueteMeta = $enquete_meta;

        return $this;
    }

    /**
     * @return text
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param text $values
     *
     * @return $this;
     */
    public function setValues($values)
    {
        $this->values = $values;

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
