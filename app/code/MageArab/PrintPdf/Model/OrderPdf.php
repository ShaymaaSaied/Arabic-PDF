<?php
/*
 * Copyright (c) Shaymaa Saied  06/05/2021, 02:00.
 */

namespace MageArab\PrintPdf\Model;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Model\Cart\TotalsConverter;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class OrderPdf extends SalesPdf
{

    public function printOrder($orderId){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /* @var \Magento\Sales\Api\Data\OrderInterface $order*/

        $order = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);

        $storeId = $order->getStoreId();
        $header=$this->_helperData->getConfigValue("pdf_config/general/order_header",$storeId);
        $header=$this->_filterProvider->getPageFilter()->filter($header);
        $footer=$this->_helperData->getConfigValue("pdf_config/general/order_footer",$storeId);
        $footer=$this->_filterProvider->getPageFilter()->filter($footer);
        $this->configureLang($order,10);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();
        $methodCode =$method->getCode();

        $orderTotals=$this->getOrderTotals($order,$methodCode);

        $totalHtml='<table>';
        foreach ($orderTotals as $orderTotal){

            $totalHtml.='<tr>
                                    <th>'.$orderTotal['title'].'</th>
                                    <td>'.$orderTotal['value'].'</td>
                                </tr>';
        }
        $totalHtml.='</table>';
        $html='<table border="0" width="100%" style="border-collapse: collapse;" >
                    <tr >
                        <td width="100%" style="font-size: 15px; text-align: center;">'.$header.'</td>
                   </tr>
                    <tr>
                        <td width="50%">
                    '.$this->createOrderDetailHtml($order).'
                    </td>
                        <td>
                        '.$this->createCustomerDetailHtml($order).'<br>
                        '.$this->createShippingAddressHtml($order).'
                        </td>
                    </tr>
                    <br>
                    <tr>
                        <td width="100%">
                        '.$this->createOrderItemsHtml($order).'
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
                        
                       <div>'.$footer.'</div> 
                    </td>
                    </tr>
                </table>';
                 $this->generatePdf($html,null);
    }

    function getOrderTotals($order,$paymentMethod){
        // $quote=$this->quoteRepository->get($quoteId);
        //var_dump($quote->getId());
        $this->_total->setTotalAmount('grand_total',$order->getGrandTotal());
      // $this->_total->get

        $totals[]=[
            'code'=>'subtotal',
            'value'=>$this->_helperData->formatPrice($order->getSubtotal()),
            'title'=>__('Subtotal')
        ];

        $totals[]=[
            'code'=>'shipping_amount',
            'value'=>$this->_helperData->formatPrice($order->getShippingAmount()),
            'title'=>__('Shipping')
        ];
        if($order->getTaxAmount()>0){
            $totals[]=[
                'code'=>'tax_amount',
                'value'=>$this->_helperData->formatPrice($order->getTaxAmount()),
                'title'=>__('Taxes')
            ];
        }

        if($paymentMethod=='cashondelivery'){
            if($this->_helperData->getConfigValue('payment/cashondelivery/enable_payment_fee',$order->getStoreId())){
                $codFees=$this->_helperData->getConfigValue('payment/cashondelivery/payment_fee',$order->getStoreId());
                if($this->_helperData->getConfigValue('payment/cashondelivery/fee_type',$order->getStoreId())){
                    $codFees=($order->getGrandTotal()*$this->_helperData->getConfigValue('payment/cashondelivery/payment_fee',$order->getStoreId()))/100;

                }
                $totals[]=[
                    'code'=>'cod_fees',
                    'value'=>$this->_helperData->formatPrice($codFees),
                    'title'=>__('COD')
                ];
            }

        }
        if($order->getDiscountAmount()!=0){
            $totals[]=[
                'code'=>'discount_amount',
                'value'=>$this->_helperData->formatPrice($order->getDiscountAmount()),
                'title'=>__('Discount')
            ];
        }
        if($order->getWalletAmount()!=0){
            $totals[]=[
                'code'=>'wallet_amount',
                'value'=>$this->_helperData->formatPrice($order->getWalletAmount()),
                'title'=>__('Wallet')
            ];
        }

        $totals[]=[
            'code'=>'grand_total',
            'value'=>$this->_helperData->formatPrice($order->getGrandTotal()),
            'title'=>__('grand total')
        ];
        $totals[]=[
            'code'=>'total_paid',
            'value'=>$this->_helperData->formatPrice($order->getTotalPaid()),
            'title'=>__('Total Paid')
        ];
        if($order->getTotalRefunded()){
            $totals[]=[
                'code'=>'total_refunded',
                'value'=>$this->_helperData->formatPrice($order->getTotalRefunded()),
                'title'=>__('Total Refunded')
            ];
        }

        $totals[]=[
            'code'=>'total_due',
            'value'=>$this->_helperData->formatPrice($order->getTotalDue()),
            'title'=>__('Total Due')
        ];
        return $totals;
    }
}