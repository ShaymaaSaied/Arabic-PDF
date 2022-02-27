<?php
/*
 * Copyright (c) Shaymaa Saied  17/05/2021, 18:32.
 */

namespace MageArab\PrintPdf\Controller\Adminhtml\Sales;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Registry;
use Magento\Backend\App\Action\Context;

class PrintOrder extends \Magento\Backend\App\Action
{
    private $_orderModel;
    private $_orderPdf;

    public function __construct(
        Context                                                     $context,
        \MageArab\PrintPdf\Model\OrderPdf                           $orderPdf,
        \Magento\Sales\Model\Order                                              $orderModel,
        JsonFactory                                                 $resultJsonFactory
    ) {

        parent::__construct($context);
        $this->_orderModel                                  =   $orderModel;
        $this->_orderPdf                                           =   $orderPdf;
        $this->resultJsonFactory                            =   $resultJsonFactory;
    }
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        try {
            $this->_orderPdf->_pdf=new \MageArab\PrintPdf\Model\Pdf ($this->_orderPdf->_helperData,$this->_orderPdf->_storeManager,$this->_orderPdf->_filterProvider,'A4');
            $this->_orderPdf->printOrder($orderId);
            $this->_orderPdf->_pdf->Output('order_' . date('m-d-Y_hia') . '.pdf', 'D');
            $this->_redirect('sales/order/view/order_id'.$orderId);
            return;
        }catch (\Exception $exception){
            $this->messageManager->addError(__($exception->getMessage()));
            $this->_redirect('sales/order/index');
            return;
        }
    }
}