<?php
/*
 * Copyright (c) Shaymaa Saied  06/05/2021, 06:04.
 */

namespace MageArab\PrintPdf\Model;


class Pdf extends \TCPDF
{
    protected   $_tcpdf;
    protected   $_helperData;
    protected   $_storeManager;
    public      $_storeId;
    public      $_pdfType;



    public function __construct(
        \MageArab\PrintPdf\Helper\Data                                          $data,
        \Magento\Store\Model\StoreManagerInterface                              $storeManager,
        \Magento\Cms\Model\Template\FilterProvider                              $filterProvider,
        $_pdfType

    )
    {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, $_pdfType, true, 'UTF-8', false);
        $this->_helperData                          =   $data;
        $this->_storeManager                        =   $storeManager;
        $this->_filterProvider                      =   $filterProvider;
        //

    }
    public function Header() {
        // Logo
        $this->SetY(15);
        $logo=$this->_helperData->getConfigValue('identity/logo',$this->_storeId);
        $logoPath= $this->_helperData->getImageUrl() . 'sales/store/logo/' . $logo;
        //$this->Image($logoPath, '', '', '', '', 'JPG', '', 'C', false, 100, 'C', false, false, 0, 'CM', false, false);
        // Set font
        $this->SetFont('aealarabiya', 'B', 8);
        $header=$this->_helperData->getConfigValue("pdf_config/general/invoice_header",$this->_storeId);
        $header=$this->_filterProvider->getPageFilter()->filter($header);
        // Title
        $html=
        $this->writeHTMLCell($w = 0, $h = 0, $x = 0, $y = '', $header, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = 'C', $autopadding = true);
       // $this->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $html, $border = 0, $ln = 1, $fill = 0, '', $align = 'top', true);
       // $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');


    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        $footerHtml=$this->_helperData->getConfigValue("pdf_config/general/invoice_footer",$this->_storeId);
        $footerHtml=$this->_filterProvider->getPageFilter()->filter($footerHtml);

        $this->SetFont('aealarabiya', '', 8);
        // Page number
        $this->writeHTML($footerHtml, false, true, false, true);
    }

}