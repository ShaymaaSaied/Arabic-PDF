<?php
/*
 * Copyright (c) Shaymaa Saied  17/05/2021, 18:26.
 */

namespace MageArab\PrintPdf\Block\Adminhtml;


class PrintOrderButton extends \Magento\Backend\Block\Widget\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry           $registry
     * @param array                                 $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    // phpcs:ignore PSR2.Methods.MethodDeclaration -- Magento 2 core use
    protected function _construct()
    {
        $this->addButton(
            'print_order',
            [
                'label'   => __('Print'),
                'class'   => 'print',
                'onclick' => 'setLocation(\'' . $this->getPdfPrintUrl() . '\')'
            ]
        );

        parent::_construct();
    }

    /**
     * @return string
     */
    public function getPdfPrintUrl()
    {
        return $this->getUrl('printpdf/sales/printorder/order_id/' . $this->getOrderId());
    }

    /**
     * @return integer
     */
    public function getOrderId()
    {
        return $this->coreRegistry->registry('sales_order')->getId();
    }
}