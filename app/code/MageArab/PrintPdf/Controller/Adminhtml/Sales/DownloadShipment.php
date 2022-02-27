<?php
/**
 * Created by PhpStorm.
 * User: Shaymaa
 * Date: 07/07/2020
 * Time: 02:18
 */

namespace MageArab\PrintPdf\Controller\Adminhtml\Sales;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class DownloadShipment extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    protected   $_tcpdf;
    protected   $_helperDataData;
    protected   $_storeManager;
    private     $_shipmentRepository;
    protected   $filter;

    public function __construct(
        Context                                                                 $context,
        Filter                                                                  $filter,
        \MageArab\PrintPdf\Helper\Data                                          $data,
        \Magento\Store\Model\StoreManagerInterface                              $storeManager,
        ShipmentRepositoryInterface                                             $shipmentRepository,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory              $collectionFactory
    )
    {
        parent::__construct($context, $filter);

        $this->collectionFactory                =   $collectionFactory;
        $this->_helperData                      =   $data;
        $this->_storeManager                    =   $storeManager;
        $this->filter                           =   $filter;
        $this->_shipmentRepository              =   $shipmentRepository;

        $this->_tcpdf=new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A6', true, 'UTF-8', false);

    }

    protected  function massAction(AbstractCollection $collection){
        $orderIds = array();

        $collection = $this->filter->getCollection($this->collectionFactory->create());

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
            if($this->createPdf($orderIds)){
                $this->messageManager->addSuccess(
                    __('Order(s) Shipment Downloaded')
                );
            }else{
                $this->messageManager->addError(__('Error while creating process'));
            }

            $this->_redirect('sales/order/index');
            return;
        }catch (\Exception $exception){
            $this->messageManager->addError(__($exception->getMessage()));
            $this->_redirect('sales/order/index');
            return;
        }
        //$this->_redirect('sales/order/index');
    }

    protected function createPdf($ordersIds){

        $data=[];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();


        foreach ($ordersIds as $ordersId){
            $order = $objectManager->create('Magento\Sales\Model\Order')->load($ordersId);
            $storeId = $order->getStoreId();
            $logo=$this->_helperData->getConfigValue('identity/logo',$storeId);
            $logoPath= $this->_helperData->getImageUrl() . 'sales/store/logo/' . $logo;;
            $originAddress=$this->_helperData->getConfigValue('identity/address',$storeId);
            $currency=$this->_storeManager->getStore()->getCurrentCurrency()->getCode();
            $shipmentCollection=$order->getShipmentsCollection();
            if($shipmentCollection->getSize()<=0){
                $message['status']=false;
                $message['message']='Could not find Shipment Carrier for Order #'.$order->getIncrementId();
                array_push($data,$message);
                return false;
            }else{
                foreach ($shipmentCollection as $shipment){
                    $shippingAddress=$order->getShippingAddress();
                    /*var_dump($shippingAddress->getData());
                    exit();*/
                    $payment = $order->getPayment();
                    $method = $payment->getMethodInstance();
                    $methodTitle = $method->getTitle();

                    $track='';
                    $shipmentRepo = $this->_shipmentRepository->get($shipment->getId());
                    if(is_array($shipmentRepo->getTracks()) && count($shipmentRepo->getTracks())>0){
                        foreach ($shipmentRepo->getTracks() as $row)

                        $track=$row;
                       // break;
                    }
                    $trackNumber='';
                    $carrier='';
                    /*echo $track;
                    exit();*/

                    if(is_object($track)){
                        $trackNumber=$track->getTrackNumber();
                        $carrier=$track->getTitle();
                    }elseif ($shipment->getCustomerNote()!=null){
                        preg_match('/[A-Za-z\.\s]+(\d+)/i',trim($shipment->getCustomerNote()),$matches);
                        //var_dump($matches);
                        $trackNumber=$matches[1];
                        $carrier='Aramex';
                    }
                    //echo $trackNumber;
                   // var_dump($shipment->getData());
                    //exit();
                    $html='<table border="1" width="100%" cellpadding="5">
                            <tr>
                                <td width="100%" style="padding: 15px;">
                                <div >
                                    <span style="text-align: left"><img width="100" height="40" src="'.$logoPath.'" style="width: 100px;height: 40px; "></span>
                                    <br/>
                                    <span style="font-size:8px;"> &nbsp; '.__('Carrier').': ' . $carrier . ' </span>
                                </div>
                               
                                </td>
                            </tr>
                            <tr>
								<td width="75%" style="padding: 15px;">
								<div style="margin: 10px height: 400px">
									<span style="font-size:12px;">&nbsp; '.__('To').', </span> <br/>
									<span style="width:90%; font-size:9px;"> &nbsp;' . $shippingAddress->getFirstname().''.$shippingAddress->getLastname() . '('.$shippingAddress->getEmail().'.) </span> <br/>
									<span style="width:90%; font-size:9px;"> &nbsp;' .implode ( ' ', $shippingAddress->getStreet())   . ' </span>' .
                                    '<br/><span style="width:90%; font-size:9px;">'. $shippingAddress->getCity().',' . $shippingAddress->getCountryId().',' . $shippingAddress->getTelephone() . '</span> <br/>
								</div>
								</td>
								<td width="25%" rowspan="2"> </td>
							</tr>
							<tr>
							    <td width="75%">
							    <div style="height: 250px"></div>
							    <div style="margin: 10px">
							    <br/>
							    <br/>
							        <span style="width:90%; font-size:10px;">'.__('Order').' : #' . $order->getIncrementId() . ' </span> <br/>
									<span style="width:90%; font-size:10px;padding: 10px">'.__('Order Date').': ' . $order->getCreatedAt() . ' </span> <br/>
									<span style="width:90%; font-size:10px;">'.__('Total Quantity').' : ' . (int) $order->getData('total_qty_ordered') . ' </span> <br/>
									<span style="width:90%; font-size:10px;">'.$currency.' ' . number_format($order->getData('grand_total'), 2) . ' </span>
						
                                </div>
							    </td>
                            </tr>
                            <tr>
                            <td width="75%">
                            <br/>
                            <span style="width:90%; font-size:10px;"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; &nbsp;&nbsp; ' . $carrier . ' </span> 
                            <br/>
                            <br/>
                            <br/>
                            <br/>
                            <br/>
                            <span style="width:90%; font-size:10px;"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; &nbsp;&nbsp; AWB: ' . $trackNumber . ' </span>
                             </td>
                            <td width="25%">
                                <span style="width:90%; font-size:10px; text-align:center; font-weight: bold;"><br/> '.$methodTitle.' </span>
                                <span style="width:90%; font-size:10px; text-align:center; "><br/> '.$currency.' ' . number_format($order->getData('grand_total'), 2) . ' </span>
                            </td>
							</tr>
                            </table>';
                    $this->_tcpdf->SetFont('aealarabiya', '', 12);
                    $this->_tcpdf->SetFont('dejavusans', '', 12);
                    $this->_tcpdf->setPrintHeader(false);
                    // No footer
                    $this->_tcpdf->setPrintFooter(false);
                    $this->_tcpdf->setHeaderData('', 0, '', '', array(0, 0, 0), array(255, 255, 255));
                    $this->_tcpdf->setCellHeightRatio(1);
                    $this->_tcpdf->SetAutoPageBreak(true, 0);
                    $this->_tcpdf->SetMargins(2, 2, 2, true);
                    $this->_tcpdf->AddPage();

                    $this->_tcpdf->writeHTML($html, true, false, false, false, '');
                    $this->_tcpdf->SetFont("helvetica", "", 10);
                    $style = array("position" => "",'stretch' => false, 'align' => 'C',"border" => false, "padding" => 3, "fgcolor" => array(0, 0, 0), "bgcolor" => false, "text" => false, "font" => "helvetica", "fontsize" => 800, "stretchtext" => 4);

                    /*$this->_tcpdf->write1DBarcode($order->getIncrementId(), 'C39', 14, 57, 50, 20, 0.4, $style, 'N');
                    $this->_tcpdf->writeHTMLCell(0, 0, -25, 75, $order->getIncrementId(), 0, 0, false, "L", true);
                    $this->_tcpdf->write1DBarcode($trackNumber['number'], 'C39', 14, 99, 50, 19, 0.4, $style, 'N');*/
                    $this->_tcpdf->write1DBarcode($trackNumber, 'C39', 14, 43, 50, 20, 0.4, $style, 'N');
                    $this->_tcpdf->writeHTMLCell(0, 0, -25, 60, $trackNumber, 0, 0, false, "T", true);
                    $this->_tcpdf->write1DBarcode($trackNumber, 'C39', 14, 90, 50, 19, 0.4, $style, 'N');

                    $this->_tcpdf->Rotate(-90);
                    $this->_tcpdf->write1DBarcode($trackNumber, 'C39', -80, 12, 55, 19, 0.4, $style, 'N');
                    $this->_tcpdf->writeHTMLCell(0, 0, -200, 10, $carrier, 0, 0, false, "L", true);
                    $this->_tcpdf->writeHTMLCell(0, 0, -200, 28, 'AWB: ' . $trackNumber, 0, 0, false, "L", true);


                }
            }
        }
        $this->_tcpdf->Output('shipment_' . date('m-d-Y_hia') . '.pdf', 'D');

        return true;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MageArab_PrintPdf::downloadshipment');
    }
}