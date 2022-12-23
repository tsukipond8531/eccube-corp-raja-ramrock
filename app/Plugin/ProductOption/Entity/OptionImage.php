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

/**
 * OptionImage
 *
 * @ORM\Table(name="plg_productoption_dtb_option_image")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ProductOption\Repository\OptionImageRepository")
 */
class OptionImage extends \Eccube\Entity\AbstractEntity
{
    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getFileName();
    }

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
         * @ORM\Column(name="file_name", type="string", length=255)
         */
        private $file_name;

        /**
         * @var int
         *
         * @ORM\Column(name="sort_no", type="smallint", options={"unsigned":true})
         */
        private $sort_no;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="create_date", type="datetimetz")
         */
        private $create_date;

        /**
         * @var \Plugin\ProductOption\Entity\OptionCategory
         *
         * @ORM\ManyToOne(targetEntity="Plugin\ProductOption\Entity\OptionCategory", inversedBy="OptionImages")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="option_category_id", referencedColumnName="id")
         * })
         */
        private $OptionCategory;

        /**
         * @var \Eccube\Entity\Member
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
         * })
         */
        private $Creator;

        /**
         * Get id.
         *
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * Set fileName.
         *
         * @param string $fileName
         *
         * @return ProductImage
         */
        public function setFileName($fileName)
        {
            $this->file_name = $fileName;

            return $this;
        }

        /**
         * Get fileName.
         *
         * @return string
         */
        public function getFileName()
        {
            return $this->file_name;
        }

        /**
         * Set sortNo.
         *
         * @param int $sortNo
         *
         * @return ProductImage
         */
        public function setSortNo($sortNo)
        {
            $this->sort_no = $sortNo;

            return $this;
        }

        /**
         * Get sortNo.
         *
         * @return int
         */
        public function getSortNo()
        {
            return $this->sort_no;
        }

        /**
         * Set createDate.
         *
         * @param \DateTime $createDate
         *
         * @return ProductImage
         */
        public function setCreateDate($createDate)
        {
            $this->create_date = $createDate;

            return $this;
        }

        /**
         * Get createDate.
         *
         * @return \DateTime
         */
        public function getCreateDate()
        {
            return $this->create_date;
        }

        /**
         * Set optionCategory.
         *
         * @param \Plugin\ProductOption\Entity\OptionCategory|null $optionCategory
         *
         * @return OptionImage
         */
        public function setOptionCategory(\Plugin\ProductOption\Entity\OptionCategory $optionCategory = null)
        {
            $this->OptionCategory = $optionCategory;

            return $this;
        }

        /**
         * Get optionCategory.
         *
         * @return \Plugin\ProductOption\Entity\OptionCategory|null
         */
        public function getOptionCategory()
        {
            return $this->OptionCategory;
        }

        /**
         * Set creator.
         *
         * @param \Eccube\Entity\Member|null $creator
         *
         * @return ProductImage
         */
        public function setCreator(\Eccube\Entity\Member $creator = null)
        {
            $this->Creator = $creator;

            return $this;
        }

        /**
         * Get creator.
         *
         * @return \Eccube\Entity\Member|null
         */
        public function getCreator()
        {
            return $this->Creator;
        }

}
