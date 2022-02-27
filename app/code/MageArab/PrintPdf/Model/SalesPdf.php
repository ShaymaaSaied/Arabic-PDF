<?php
/*
 * Copyright (c) Shaymaa Saied  14/10/2021, 23:23.
 */

namespace MageArab\PrintPdf\Model;


use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Model\Cart\TotalsConverter;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class SalesPdf
{
    public          $_pdf;
    public          $_helperData;
    public          $_storeManager;
    protected       $_shipmentRepository;
    protected       $_filter;
    protected       $_invoiceInterface;
    protected       $_searchCriteriaBuilder;
    protected       $_invoiceRepository;
    protected       $_pdfType;
    protected       $_quoteFactory;
    protected       $_totalsConverter;
    protected       $_total;
    protected       $_priceHelper;
    protected       $_productRepository;
    protected       $_customerRepository;
    private         $_addressRepository;
    private         $_appEmulation;

    public function __construct(
        \MageArab\PrintPdf\Helper\Data                                          $data,
        \Magento\Store\Model\StoreManagerInterface                              $storeManager,
        SearchCriteriaBuilder                                                   $searchCriteriaBuilder,
        \Magento\Quote\Model\Quote\Address\Total                                $total,
        \Magento\Cms\Model\Template\FilterProvider                              $filterProvider,
        TotalsConverter                                                         $totalsConverter,
        \Magento\Framework\Pricing\Helper\Data                                  $priceHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface                         $productRepository ,
        \Magento\Customer\Api\CustomerRepositoryInterface                       $customerRepository,
        \Magento\Customer\Api\AddressRepositoryInterface                        $_addressRepository,
        \Magento\Sales\Api\InvoiceRepositoryInterface                           $_invoiceRepository,
        \Magento\Store\Model\App\Emulation                                      $appEmulation,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory              $collectionFactory
    )
    {
        $this->_collectionFactory                   =   $collectionFactory;
        $this->_helperData                          =   $data;
        $this->_priceHelper                         =   $priceHelper;
        $this->_productRepository                   =   $productRepository;
        $this->_total                               =   $total;
        $this->_appEmulation                        =   $appEmulation;
        $this->_customerRepository                  =   $customerRepository;
        $this->_storeManager                        =   $storeManager;
        $this->_searchCriteriaBuilder               =   $searchCriteriaBuilder;
        $this->_filterProvider                      =   $filterProvider;
        $this->_totalsConverter                     =   $totalsConverter;
        $this->_addressRepository                   =   $_addressRepository;
        $this->_invoiceRepository                   =   $_invoiceRepository;
        //

    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * */
    public function createOrderDetailHtml($order, $showStatus = true) {

        $html='<table>';
        $html.='<tr>
                    <th style="width: 25%"><strong>'.__('Order').'</strong></th>
                    <td style="width: 75%">'.'#'.$order->getIncrementId().'</td>
                </tr>';
        $html.='<tr>
                    <th style="width: 25%"><strong>'.__('Order Date').'</strong></th>
                    <td style="width: 75%">'.$this->_helperData->convertDate($order->getCreatedAt(),$order->getStoreId()).'</td>
                </tr>';
        if ($showStatus) {
            $html.='<tr>
            <th style="width: 25%"><strong>'.__('Order Status').'</strong></th>
            <td style="width: 75%">'.ucfirst(__($order->getStatus())).'</td>
            </tr>';
        }
        $html.='<tr>
                    <th style="width: 25%"><strong>'.__('Payment').'</strong></th>
                    <td style="width: 75%">'.$order->getPayment()->getMethodInstance()->getTitle().'</td>
                </tr>';
        $html.='<tr>
                    <th style="width: 25%"><strong>'.__('Shipping').'</strong></th>
                    <td style="width: 75%">'.$order->getShippingDescription().'</td>
                </tr>';
        if($order->getDeliveryDate()){
            $html.='<tr>
                    <th style="width: 25%"><strong>'.__('Delivery Date').'</strong></th>
                    <td style="width: 75%">'.$order->getDeliveryDate().'</td>
                </tr>';
        }


        $html.='</table>';

        return $html;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     * */
    public function createCustomerDetailHtml(\Magento\Sales\Api\Data\OrderInterface $order){
        $html='<table>';
        $html.='<tr>
                    <th style="width: 25%"><strong>'.__('Customer').'</strong></th>
                    <td style="width: 75%">'.$order->getCustomerFirstname().' '.$order->getCustomerLastname().'</td>
                </tr>';
        $customer=$this->_customerRepository->get($order->getCustomerEmail());
        if($customer->getId()){
            $html.='<tr>
                        <th style="width: 25%"><strong>'.__('Customer Mobile').'</strong></th>
                        <td style="width: 75%">'.$customer->getCustomAttribute('customer_mobile_code')->getValue().$customer->getCustomAttribute('customer_mobile')->getValue().'</td>
                    </tr>';
        }
        $html.='</table>';
        return $html;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     * */
    public function createShippingAddressHtml(\Magento\Sales\Api\Data\OrderInterface $order){
        $shippingAddress=$order->getShippingAddress();
        $addressDetails='';
        if($addressId=$shippingAddress->getCustomerAddressId()){
            $address=$this->_addressRepository->getById($addressId);
            if($address->getCustomAttribute('address_title')){
                $addressDetails='<strong>'.$address->getCustomAttribute('address_title')->getValue().'</strong><br>';

            }
            if($address->getCustomAttribute('flat_no')){
                $addressDetails.='<span>'.__('Flat').'</span><strong>'.$address->getCustomAttribute('flat_no')->getValue().',</strong>';

            }
            if($address->getCustomAttribute('floor_no')){
                $addressDetails.='<span>'.__('Floor').'</span><strong>'.$address->getCustomAttribute('floor_no')->getValue().',</strong>';

            }
            if($address->getCustomAttribute('building_no')){
                $addressDetails.='<span>'.__('Building').'</span><strong>'.$address->getCustomAttribute('building_no')->getValue().'</strong>';

            }
        }
        $html='<table>';
        $html.='<tr>
                    <th style="width: 25%">'.__('Shipping Address').'</th>
                    <td style="width: 75%; border: 0.2px solid black;">
                    '.$addressDetails.'<br>'
                    .implode(' ',$shippingAddress->getStreet()).'<br>'
                    .$shippingAddress->getCity().'
                    </td>
                </tr>';
        $html.='</table>';
        return $html;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     * */
    public function createOrderItemsHtml(\Magento\Sales\Api\Data\OrderInterface $order){
        $orderItems=$order->getAllItems() ;
        $itemsHtml='<table border="1" cellpadding="2">
                        <thead>
                            <tr>
                                <th style="width: 3%"></th>
                                <th style="width: 35%">'.__('Product').'</th>
                                <th style="width: 5%">'.__('Qty').'</th>
                                <th style="width: 15%">'.__('SKU').'</th>
                                <th style="width: 15%">'.__('Barcode').'</th>
                                <th style="width: 7%">'.__('Weight').'</th>
                                <th style="width: 10%">'.__('Price').'</th>
                                <th style="width: 10%">'.__('Total').'</th>
                            </tr>
                        </thead>';

        $i=1;
        foreach ($orderItems as $orderItem){
            $product=$this->_productRepository->get($orderItem->getSku());
            $barcode='';
            if($product->getCustomAttribute('barcode')){
                $barcode=$product->getCustomAttribute('barcode')->getValue();
            }
            if($product->getCustomAttribute('weight')){
                $weight=$product->getCustomAttribute('weight')->getValue();
            }

            $itemsHtml.='<tr>
                            <td style="width: 3%">'.$i.'</td>
                            <td style="width: 35%">'.$orderItem->getName().'</td>
                            <td style="width: 5%">'.round($orderItem->getQtyOrdered(),2).'</td>
                            <td style="width: 15%">'.$orderItem->getSku().'</td>
                            <td style="width: 15%">'.$barcode.'</td>
                            <td style="width: 7%" dir="ltr">'.$weight.'</td>
                            <td style="width: 10%" dir="ltr">'.$orderItem->getPrice().'</td>
                            <td style="width: 10%" dir="ltr">'.number_format($orderItem->getRowTotal(), 2, ".", ",").'</td>
                            
                        </tr>';
            $i++;
        }
        $itemsHtml.='</table>';
        return  $itemsHtml;
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @return string
     * */
    public function createInvoiceInfoHtml($invoice){
        $html='<table>';
        $html.='<tr>
                    <th style="width: 25%"><strong>'.__('Invoice').'</strong></th>
                    <td style="width: 75%">'.'#'.$invoice->getIncrementId().'</td>
                </tr>';
        $html.='<tr>
                    <th style="width: 25%"><strong>'.__('Invoice Date').'</strong></th>
                    <td style="width: 75%">'.$this->_helperData->convertDate($invoice->getCreatedAt(),$invoice->getStoreId()).'</td>
                </tr>';
        $html.='</table>';

        return $html;

    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param float $font
     * */
    public function configureLang(\Magento\Sales\Api\Data\OrderInterface $order,$font){
        $this->_appEmulation->startEnvironmentEmulation(
            $order->getStoreId(),
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );
        $this->_storeManager->setCurrentStore($order->getStoreId());
        if($order->getStoreId()==1) {

            $lg = array();
            $lg['a_meta_charset'] = 'UTF-8';
            $lg['a_meta_dir'] = 'rtl';
            $lg['a_meta_language'] = 'ar';
            $lg['w_page'] = 'page';
            $this->_pdf->setLanguageArray($lg);
            $this->_pdf->setRTL(true);
            $this->_pdf->SetFont('aealarabiya', '', $font);
        }
    }
    /**
     * @param string $html
     * */
    public function generatePdf($html,$pageLayout){

        $this->_pdf->setPrintHeader(false);
        $this->_pdf->setPrintFooter(false);
        $this->_pdf->SetAutoPageBreak(true);
        $this->_pdf->SetMargins(2, 2, 2, true);
        $this->_pdf->addPage('P', $pageLayout, false, false);
        $this->_pdf->writeHTML($html, true, false, true, false, '');
        $this->_pdf->endPage();
    }
}
