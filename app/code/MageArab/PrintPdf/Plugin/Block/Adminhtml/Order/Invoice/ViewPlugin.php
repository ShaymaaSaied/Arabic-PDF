<?php
/*
 * Copyright (c) Shaymaa Saied  09/05/2021, 02:49.
 */

namespace MageArab\PrintPdf\Plugin\Block\Adminhtml\Order\invoice;


class ViewPlugin
{
    private $_urlBuilder;
    private $_request;

    public function __construct(
        \Magento\Framework\UrlInterface  $urlBuilder,
        \Magento\Framework\App\Request\Http $request
    ){
        $this->_urlBuilder  =   $urlBuilder;
        $this->_request     =   $request;
    }
    public function beforeSetLayout(
        \Magento\Sales\Block\Adminhtml\Order\Invoice\View $view
    )
    {
        $message ='Are you sure you want to do this?';
       $invoiceId=$this->_request->getParam('invoice_id');

        $url = $this->_urlBuilder->getUrl('printpdf/sales/printinvoice', ['invoice_id' => $invoiceId]);


        $view->addButton(
            'print',
            [
                'label' => __('Print'),
                'class' => 'print',
                'onclick' => 'setLocation(\'' . $url . '\')'
            ]
        );

    }



}