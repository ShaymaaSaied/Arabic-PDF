<?php
/*
 * Copyright (c) Shaymaa Saied  09/05/2021, 03:29.
 */

namespace MageArab\PrintPdf\Model;


use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Model\Cart\TotalsConverter;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class InvoicePdf extends SalesPdf
{

    public function createInvoices($order){

        $invoices=$this->getInvoiceDataByOrderId($order->getId());
        foreach ($invoices as $invoice){
           $invoice->getIncrementId();
            $this->createInvoice($invoice,$order);
        }
    }
    public function createInvoice($invoice,$order){
        $storeId = $order->getStoreId();
        $this->configureLang($order,7);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();
        if (-$order->getWalletAmount() > 0) {
            if ($methodTitle != __('Wallet')) {
                $methodTitle =$methodTitle.' + Wallet';
            }
        }
        $methodCode =$method->getCode();
        $shippingAddress=$order->getShippingAddress();

        $header=$this->_helperData->getConfigValue("pdf_config/general/invoice_header",$storeId);
        $header=$this->_filterProvider->getPageFilter()->filter($header);
        $footer=$this->_helperData->getConfigValue("pdf_config/general/invoice_footer",$storeId);
        $footer=$this->_filterProvider->getPageFilter()->filter($footer);
        $invoiceItems=$invoice->getItemsCollection();

        $invoiceHtml='<table border=".5" cellpadding="1.5">
                                <thead>
                                <tr>
                                <th style="width: 50%">'.__('Product').'</th>
                                <th style="width: 15%">'.__('Price').'</th>
                                <th style="width: 15%">'.__('Qty').'</th>
                                <th style="width: 20%">'.__('Total').'</th>
                                </tr>
                                </thead>';
        $invoiceTotals=$this->getInvoiceTotals($invoice,$methodCode,$storeId);

        foreach ($invoiceItems as $invoiceItem){

            $invoiceHtml=$invoiceHtml.'<tr>
                                            <td style="width: 50%">'.$invoiceItem->getName().'</td>
                                            <td style="width: 15%">'.round($invoiceItem->getPrice(),2).'</td>
                                            <td style="width: 15%">'.round($invoiceItem->getQty(),2).'</td>
                                            <td style="width: 20%">'.round($invoiceItem->getRowTotal(),2).'</td>
                                            
                                        </tr>';
        }
        $invoiceHtml=$invoiceHtml.'</table>';
        $totalHtml='<table>';
        foreach ($invoiceTotals as $invoiceTotal){

            $totalHtml.='<tr>
                                <th>'.$invoiceTotal['title'].'</th>
                                <td>'.$invoiceTotal['value'].'</td>
                           </tr>';
        }
        $totalHtml.='</table>';
        $html='<table border="0" width="100%" style="border-collapse: collapse;">
                            <tr style="text-align: center">
                            <td style="text-align: center;">'.$header.'</td>
                           </tr>
                            <tr>
                                <td width="100%">
                                '.$this->createOrderDetailHtml($order, false).'
                                </td>
                            </tr>
                            <tr>
                            <td width="100%">
                            '.$this->createInvoiceInfoHtml($invoice).'<br>
                            '.$this->createCustomerDetailHtml($order).'<br>
                            '.$this->createShippingAddressHtml($order).'
                            </td>
                            </tr>
                            <br>
                             <tr>
							    <td width="100%">
							     '.$invoiceHtml.'
							    </td>
                            </tr>
                            
                            <tr>
                          
                            <td width="100%">
                            <!--order totals-->
                                
                               <div>'.$totalHtml.'</div> 
                            </td>
							</tr>
							
							<tr>
                          
                            <td width="100%">
                            <!--order totals-->
                                
                               <div>'.$footer.'</div> 
                            </td>
							</tr>
                            </table>';

        //  $this->_pdf->_pdfType='A8';
        $this->generatePdf($html,array(75,$this->calcPdfHeight($invoiceItems)));
    }
    public function getInvoiceDataByOrderId( $orderId)
    {
        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter('order_id', $orderId)->create();
        try {
            $invoices = $this->_invoiceRepository->getList($searchCriteria);
            $invoiceRecords = $invoices->getItems();
        } catch (Exception $exception)  {
            $this->logger->critical($exception->getMessage());
            $invoiceRecords = null;
        }
        return $invoiceRecords;
    }
    function getInvoiceTotals($invoice,$paymentMethod,$storeId){
        // $quote=$this->quoteRepository->get($quoteId);
        //var_dump($quote->getId());
       // $this->_total->setTotalAmount('grand_total',$invoice->getGrandTotal());
        // $this->_total->get

        $totals[]=[
            'code'=>'subtotal',
            'value'=>$this->_helperData->formatPrice($invoice->getSubtotal()),
            'title'=>__('Subtotal')
        ];

        $totals[]=[
            'code'=>'shipping_amount',
            'value'=>$this->_helperData->formatPrice($invoice->getShippingAmount()),
            'title'=>__('Shipping')
        ];
        if($invoice->getTaxAmount()>0){
            $totals[]=[
                'code'=>'tax_amount',
                'value'=>$this->_helperData->formatPrice($invoice->getTaxAmount()),
                'title'=>__('Taxes')
            ];
        }

        if($paymentMethod=='cashondelivery') {
            if($this->_helperData->getConfigValue('payment/cashondelivery/enable_payment_fee',$storeId)){
                $codFees=$this->_helperData->getConfigValue('payment/cashondelivery/payment_fee',$storeId);
                if($this->_helperData->getConfigValue('payment/cashondelivery/fee_type',$storeId)){
                    $codFees=($invoice->getGrandTotal()*$this->_helperData->getConfigValue('payment/cashondelivery/payment_fee',$storeId))/100;

                }
                $totals[]=[
                    'code'=>'cod_fees',
                    'value'=>$this->_helperData->formatPrice($codFees,),
                    'title'=>__('COD')
                ];
            }
        }
        $totals[]=[
            'code'=>'grand_total',
            'value'=>$this->_helperData->formatPrice($invoice->getGrandTotal()),
            'title'=>__('grand total')
        ];

        if($invoice->getDiscountAmount()!=0){
            $totals[]=[
                'code'=>'discount_amount',
                'value'=>$this->_helperData->formatPrice($invoice->getDiscountAmount()),
                'title'=>__('Discount')
            ];
        }

        if($invoice->getWalletAmount()!=0){
            $totals[]=[
                'code'=>'wallet_amount',
                'value'=>$this->_helperData->formatPrice($invoice->getWalletAmount()),
                'title'=>__('Wallet')
            ];
        }
        $totals[]=[
            'code'=>'total_due',
            'value'=>$this->_helperData->formatPrice($invoice->getBaseGrandTotal()),
            'title'=>__('Total Due')
        ];

        return $totals;
    }
    public function calcPdfHeight($invoiceItems): float
    {
        return 130 + (count($invoiceItems) * 4);
    }

}