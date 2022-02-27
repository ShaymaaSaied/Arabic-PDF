<?php
/*
 * Copyright (c) Shaymaa Saied  10/05/2021, 04:11.
 */

namespace MageArab\PrintPdf\Controller\Adminhtml\Sales;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class PrintOrders extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    private $_filter;
    private $_orderPdf;
    private $_orderModel;
    private $_orderRepository;

    public function __construct(
        \MageArab\PrintPdf\Model\OrderPdf                                       $orderPdf,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory              $collectionFactory,
        Context                                                                 $context,
        \Magento\Sales\Model\Order                                              $orderModel,
        \Magento\Cms\Model\Template\FilterProvider                              $filterProvider,
        \Magento\Sales\Api\OrderRepositoryInterface                             $orderRepository,
        Filter                                                                  $filter
    )
    {
        parent::__construct($context, $filter);
        $this->_orderPdf                                    =   $orderPdf;
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
            $this->_orderPdf->_pdf=new \MageArab\PrintPdf\Model\Pdf ($this->_orderPdf->_helperData,$this->_orderPdf->_storeManager,$this->filterProvider,'A4');
            foreach ($orderIds as $orderId){
                $this->_orderPdf->printOrder($orderId);
            }
            $this->_orderPdf->_pdf->Output('order_' . date('m-d-Y_hia') . '.pdf', 'D');
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
        return $this->_authorization->isAllowed('MageArab_PrintPdf::printorders');
    }
}