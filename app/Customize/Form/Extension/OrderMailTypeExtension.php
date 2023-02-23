<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Form\Extension;

use Doctrine\ORM\EntityRepository;
use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Admin\OrderMailType;
use Eccube\Form\Type\Master\MailTemplateType;
use Eccube\Form\Validator\TwigLint;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class OrderMailTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * MailType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $templateIds = [
            $this->eccubeConfig['eccube_proof_request_again_mail_template_id'],
            $this->eccubeConfig['eccube_proof_request_mail_template_id'],
            $this->eccubeConfig['eccube_proof_complete_mail_template_id'],
            $this->eccubeConfig['eccube_shipping_ready_mail_template_id'],
            $this->eccubeConfig['eccube_shipping_complete_mail_template_id'],
            $this->eccubeConfig['eccube_continuous_use_mail_template_id'],
            $this->eccubeConfig['eccube_short_plan_mail_template_id'],
            $this->eccubeConfig['eccube_line_plan_change_mail_template_id'],
            $this->eccubeConfig['eccube_trial_cancel_mail_template_id'],
            $this->eccubeConfig['eccube_cancel_complete_mail_template_id'],
            $this->eccubeConfig['eccube_withdrawal_complete_mail_template_id'],
            $this->eccubeConfig['eccube_free_form_mail_template_id']
        ];

        $builder
            ->add('template', MailTemplateType::class, [
                'required' => false,
                'mapped' => false,
                'query_builder' => function (EntityRepository $er) use ($templateIds) {
                    return $er->createQueryBuilder('mt')
                        ->andWhere('mt.id IN (:id)')
                        ->setParameter(':id', $templateIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
                        ->orderBy('mt.id', 'ASC');
                },
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedTypes(): iterable
    {
        return [OrderMailType::class];
    }

}