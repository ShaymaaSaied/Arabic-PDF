<?php
/*
 * Copyright (c) Shaymaa Saied  05/05/2021, 03:22.
 */

namespace MageArab\PrintPdf\Controller\Adminhtml\Sales;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;


class PrintInvoices extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    private $_filter;
    private $_invoicePdf;
    private $_orderModel;
    private $_orderRepository;

    public function __construct(
        \MageArab\PrintPdf\Model\InvoicePdf                                       $invoicePdf,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory              $collectionFactory,
        Context                                                                 $context,
        \Magento\Sales\Model\Order                                              $orderModel,
        \Magento\Cms\Model\Template\FilterProvider                              $filterProvider,
        \Magento\Sales\Api\OrderRepositoryInterface                             $orderRepository,
        Filter                                                                  $filter
    )
    {
        parent::__construct($context, $filter);
        $this->_invoicePdf                                  =   $invoicePdf;
        $this->_filter                                      =   $filter;
        $this->collectionFactory                            =   $collectionFactory;
        $this->filterProvider                               =   $filterProvider;
        $this->_orderModel                                  =   $orderModel;
        $this->_orderRepository                             =   $orderRepository;

    }

    protected  function massAction(AbstractCollection $collection){
        $orderIds = array();

        $collection = $this->_filter->getCollection($this->collectionFactory->create());

        foreach ($collection->getItems() as $order)
        {
            $orderIds[] = $order->getId();
        }

        if (empty($orderIds)) {
            $this->messageManager->addError(__('There is no order to process'));
            $this->_redirect('sales/order/index');
            return;
        }
        try {
            $this->_invoicePdf->_pdf=new \MageArab\PrintPdf\Model\Pdf ($this->_invoicePdf->_helperData,$this->_invoicePdf->_storeManager,$this->filterProvider,'A4');
            foreach ($orderIds as $orderId){
                $orderModel=$this->_orderRepository->get($orderId);
                $orderModel->getId();
                $this->_invoicePdf->createInvoices($orderModel);
            }
            $this->_invoicePdf->_pdf->Output('invoice_' . date('m-d-Y_hia') . '.pdf', 'D');
            $this->_redirect('sales/order/index');
            return;
        }catch (\Exception $exception){
            $this->messageManager->addError(__($exception->getMessage()));
            $this->_redirect('sales/order/index');
            return;
        }
        //$this->_redirect('sales/order/index');
    }



    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MageArab_PrintPdf::printinvoice');
    }
}
