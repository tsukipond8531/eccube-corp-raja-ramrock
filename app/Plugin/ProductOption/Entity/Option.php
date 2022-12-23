<?php
/*
 * Plugin Name : ProductOption
 *
 * Copyright (C) BraTech Co., Ltd. All Rights Reserved.
 * http://www.bratech.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ProductOption\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Option
 *
 * @ORM\Table(name="plg_productoption_dtb_option")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ProductOption\Repository\OptionRepository")
 */
class Option extends \Eccube\Entity\AbstractEntity
{

    const SELECT_TYPE = 1;
    const RADIO_TYPE = 2;
    const CHECKBOX_TYPE = 3;
    const TEXT_TYPE = 4;
    const TEXTAREA_TYPE = 5;
    const DATE_TYPE = 6;
    const NUMBER_TYPE = 7;

    const DISP_ON = 1;
    const DISP_OFF = 0;

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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="backend_name", type="string", length=255, nullable=false)
     */
    private $backend_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=4000, nullable=true)
     */
    private $description;

    /**
     * @var int|null
     *
     * @ORM\Column(name="type", type="smallint", options={"unsigned":true})
     */
    private $type;

    /**
     * @var boolean|null
     *
     * @ORM\Column(name="pricedisp_flg", type="boolean", nullable=true)
     */
    private $pricedisp_flg;

    /**
     * @var boolean|null
     *
     * @ORM\Column(name="description_flg", type="boolean", nullable=true)
     */
    private $description_flg;

    /**
     * @var boolean|null
     *
     * @ORM\Column(name="is_required", type="boolean", nullable=true)
     */
    private $is_required;

    /**
     * @var int|null
     *
     * @ORM\Column(name="require_min", type="integer", nullable=true)
     */
    private $require_min;

    /**
     * @var int|null
     *
     * @ORM\Column(name="require_max", type="integer", nullable=true)
     */
    private $require_max;

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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\ProductOption\Entity\OptionCategory", mappedBy="Option", cascade={"persist","remove"})
     * @ORM\OrderBy({
     *     "sort_no"="DESC"
     * })
     */
    private $OptionCategories;

    /**
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
     * })
     */
    private $Creator;

    private $optionCategories = [];
    private $defaultCategorys = [];
    private $disableCategory;

    public function __construct()
    {
        $this->OptionCategories = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addConstraint(new UniqueEntity([
            'fields'  => 'backend_name',
            'message' => 'productoption.option.error.backend_name_registered',
        ]));
    }

    public function getDefaultCategory()
    {
        if($this->defaultCategorys){
            if($this->getType() == self::CHECKBOX_TYPE){
                return $this->defaultCategorys;
            }else{
                return isset($this->defaultCategorys[0]) ? $this->defaultCategorys[0] : null;
            }
        }
        foreach ($this->getOptionCategories() as $OptionCategory) {
            if ($OptionCategory->getInitFlg() == true) {
                $this->defaultCategorys[] = $OptionCategory;
            }
        }
        if($this->getType() == self::CHECKBOX_TYPE){
            return $this->defaultCategorys;
        }else{
            return isset($this->defaultCategorys[0]) ? $this->defaultCategorys[0] : null;
        }
    }

    public function getDisableCategory()
    {
        if($this->disableCategory)return $this->disableCategory;
        foreach ($this->getOptionCategories() as $OptionCategory) {
            if ($OptionCategory->getDisableFlg() == true) {
                $this->disableCategory = $OptionCategory;
                break;
            }
        }
        return $this->disableCategory;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setBackendName($name)
    {
        $this->backend_name = $name;

        return $this;
    }

    public function getBackendName()
    {
        return $this->backend_name;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setCreateDate($date)
    {
        $this->create_date = $date;

        return $this;
    }

    public function getCreateDate()
    {
        return $this->create_date;
    }

    public function setUpdateDate($date)
    {
        $this->update_date = $date;

        return $this;
    }

    public function getUpdateDate()
    {
        return $this->update_date;
    }

    public function setSortNo($sortNo)
    {
        $this->sort_no = $sortNo;

        return $this;
    }

    public function getSortNo()
    {
        return $this->sort_no;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setDescriptionFlg($flg)
    {
        $this->description_flg = $flg;

        return $this;
    }

    public function getDescriptionFlg()
    {
        return $this->description_flg;
    }

    public function setPricedispFlg($flg)
    {
        $this->pricedisp_flg = $flg;

        return $this;
    }

    public function getPricedispFlg()
    {
        return $this->pricedisp_flg;
    }

    public function setIsRequired($flg)
    {
        $this->is_required = $flg;

        return $this;
    }

    public function getIsRequired()
    {
        return $this->is_required;
    }

    public function setRequireMin($min)
    {
        $this->require_min = $min;

        return $this;
    }

    public function getRequireMin()
    {
        return $this->require_min;
    }

    public function setRequireMax($max)
    {
        $this->require_max = $max;

        return $this;
    }

    public function getRequireMax()
    {
        return $this->require_max;
    }

    public function addOptionCategory(\Plugin\ProductOption\Entity\OptionCategory $optionCategory)
    {
        $this->OptionCategories[] = $optionCategory;

        return $this;
    }

    public function removeOptionCategory(\Plugin\ProductOption\Entity\OptionCategory $optionCategory)
    {
        $this->OptionCategories->removeElement($optionCategory);
    }

    public function getOptionCategories()
    {
        return $this->OptionCategories;
    }

    public function setCreator(\Eccube\Entity\Member $creator)
    {
        $this->Creator = $creator;

        return $this;
    }

    public function getCreator()
    {
        return $this->Creator;
    }

}
