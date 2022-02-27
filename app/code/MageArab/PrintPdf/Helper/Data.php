<?php
/**
 * Created by PhpStorm.
 * User: Shaymaa
 * Date: 07/07/2020
 * Time: 03:08
 */

namespace MageArab\PrintPdf\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Directory\Model\CurrencyFactory;
class Data extends AbstractHelper
{
    protected   $_storeManager;
    protected  $_scopeConfig;
    private     $timezone;
    private     $_currencyFactory;
    private     $_currencyCode;
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface                          $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface                $timezone,
        CurrencyFactory                                                     $currencyFactory,

        \Magento\Framework\App\Config\ScopeConfigInterface                  $scopeConfig
    ){
        $this->_scopeConfig         =   $scopeConfig;
        $this->timezone             =   $timezone;
        $this->_storeManager        =   $storeManager;
        $this->_currencyCode        =   $currencyFactory->create();
        parent::__construct($context);
    }

    private function getStore($storeId){
        $store=$this->_storeManager->getStore($storeId);
        return $store->getCode();
    }

    public function getConfigValue($configPath = null, $storeId){
        return $this->_scopeConfig->getValue($configPath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->getStore($storeId));
    }
    public function getImageUrl()
    {
        return $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function convertDate($date,$storeId){
        $timezone = $this->timezone->getConfigTimezone(\Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);

        $value=$this->timezone->formatDateTime(
            $date,
            \IntlDateFormatter::MEDIUM ,
            \IntlDateFormatter::MEDIUM,
            $timezone,
            null,
            'y/MM/dd hh:mm a'
        );
        return $value;
        //$order->setCreatedAt($date);
    }

    /**
     * @return string
     */
    public function getCurrencySymbol()
    {
        $currentCurrency = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        $currency = $this->_currencyCode->load($currentCurrency);
        if ($currentCurrency == 'EGP') {
            $currentCurrency = 'جنيه';
        }
        return  $currentCurrency;//$currency->getCurrencySymbol();
    }

    public function formatPrice($value){
        return number_format($value, 2, ".", ",").' '.$this->getCurrencySymbol();
    }
}