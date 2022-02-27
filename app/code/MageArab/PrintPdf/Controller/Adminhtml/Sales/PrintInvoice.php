<?php
/*
 * Copyright (c) Shaymaa Saied  09/05/2021, 03:03.
 */

namespace MageArab\PrintPdf\Controller\Adminhtml\Sales;


use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Registry;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Api\InvoiceRepositoryInterface;


class PrintInvoice extends \Magento\Backend\App\Action
{

    private $_invoicePdf;
    private $_invoiceRepository;
    private $_orderModel;
    /**
     * Abstractpdf constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context                                                     $context,
        \MageArab\PrintPdf\Model\InvoicePdf                         $invoicePdf,
        InvoiceRepositoryInterface                                  $invoiceRepository,
        \Magento\Sales\Model\Order                                              $orderModel,
        JsonFactory                                                 $resultJsonFactory
    ) {

        parent::__construct($context);
        $this->_invoicePdf                                  =   $invoicePdf;
        $this->_orderModel                                  =   $orderModel;
        $this->resultJsonFactory                            =   $resultJsonFactory;
        $this->_invoiceRepository                           =   $invoiceRepository;
    }

    public function execute()
    {
       $invoiceId = $this->getRequest()->getParam('invoice_id');
        try {
            $this->_invoicePdf->_pdf=new \MageArab\PrintPdf\Model\Pdf ($this->_invoicePdf->_helperData,$this->_invoicePdf->_storeManager,$this->_invoicePdf->_filterProvider,'A4');
            $invoice = $this->_invoiceRepository->get($invoiceId);
            $order=$this->_orderModel->load($invoice->getOrderId());
            $this->_invoicePdf->createInvoice($invoice,$order);
            $this->_invoicePdf->_pdf->Output('invoice_' . date('m-d-Y_hia') . '.pdf', 'D');

            $this->_redirect('sales/order/index');
            return;
        }catch (\Exception $exception){
            $this->messageManager->addError(__($exception->getMessage()));
            $this->_redirect('sales/order/index');
            return;
        }
    }
}
