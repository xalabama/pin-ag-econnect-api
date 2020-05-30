<?php
/**
  * API from PIN AG for creating and sending e-letters
  *
  * This API based on eConnect 0.9.5 service from PIN AG. ( https://www.pin-ag.de/ )
  * To use the interface, you need the corresponding access data from PIN AG.
  * PIN AG will normally provide you with the API access data on request.
  * For further information, please read the documentation or visit the PIN AG website.
  *
  * @param    array $options set rquired options for login to the API
  * @return   object
  * @author		Sandro Rümmler <kontakt@sandro-ruemmler.de>
  * @version	0.1.0 28/05/2020 15:57
  */

class PinAg_EConnectApi
{
  public function __construct( $options = array() )
  {
    $defaults =
    [
      'apiMode'                 => 'staging',                               // REQUIRED -- API Mode -- live / staging
      'apiEndpoint'             => 'https://api.ebrief.de/API095',          // REQUIRED -- API endpoint - like recived from PIN AG
      'apiEndpointStaging'      => 'https://api.staging.ebrief.de/API095',  // REQUIRED -- API staging endpoint - like recived from PIN AG - only for staging mode
      'referenceSenderFrontend' => '',                                      // REQUIRED -- API Username - like recived from PIN AG
      'codeFrontend'            => '',                                      // REQUIRED -- API Password - You have to set your password on self by first login
      'referenceCustomerNumber' => '',                                      // REQUIRED -- API CustomerNumber - like recived from PIN AG
      'referenceCustomerUser'   => '',                                      // OPTIONAL -- User who places the order
      'referenceCustomerBranch' => '',                                      // OPTIONAL -- Department of sender
    ];

    $this->options = array_merge( $defaults, $options );

    switch( $this->options['apiMode'] )
    {
      case 'live':
        $this->SoapClient = new SoapClient( $this->options['apiEndpoint'] );
        break;

      case 'staging':
        $this->SoapClient = new SoapClient( $this->options['apiEndpointStaging'] );
        break;

      default:
        $this->SoapClient = new SoapClient( $this->options['apiEndpoint'] );
        break;
    }

    return $this->SoapClient;
  }

  /**
   * Process the customerAttributes parameter.
   *
   * @param       array  $customerAttributes  defines a list of possible customer attributes
   * @return      array
   */
  protected function process_customer_attributes( $customerAttributes )
  {
    $defaultCustomerAttributes =
    [
      'printColor'                      => 'COLOR',     // BLACKWHITE / COLOR – specifies whether the letter is to be printed in black and white or full color (set by the delivering system)
      'printMode'                       => 'SIMPLEX',   // SIMPLEX / DUPLEX – specifies whether the letter is to be printed in simplex or duplex gedruckt werden soll (set by the delivering system)
      'pinProcessSilent'                => 'TRUE',      // FALSE / TRUE – determines whether the process job automatically processes the documents until they are printed. TRUE: Documents are passed on directly to the print service provider FALSE or no specification: The order must be released separately with the method setPortalProcessJobStatusCodeByPortalProcessJobId and the order status DISTRIBUTION_READY_FOR.
      'notificationMailTo'              => '',          // comma-separated list of email addresses to send a report about the documents sent.
      'pinManipulateMarginLeft'         => '',          // The delivered document is moved to the right with the specified value (float).
      'pinManipulateMarginRight'        => '',          // The delivered document is moved to the left with the specified value (float).
      'pinManipulateMarginBottom'       => '',          // The delivered document is moved up with the specified value (float).
      'pinManipulateScalePercentWidth'  => '',          // The width of the delivered document is reduced by the specified value (float).
      'pinManipulateScalePercentHeight' => '',          // The height of the delivered document is reduced by the specified value (float).
      'pinPrintPresetPaperType'         => '',          // Business paper can be deposited with the print service provider. For this it is essential to get in touch with the account manager.
      'pinPrintPresetEnvelope'          => '',          // Customer may store their own envelopes with the print service provider. For this it is essential to get in touch with the account manager.
      'pinPrintPresetTransferVoucher'   => '',          // Transfer carriers with a perforation can be used. FP / LP - indicates the position of the transfer slip FP (FirstPage) or LP (LastPage). Please use the distance template provided.
    ];

    $customerAttributes = array_merge( $defaultCustomerAttributes, $customerAttributes );

    return $customerAttributes;
  }

  /**
   * With prepareProcess the upload of documents is prepared. The return (portalProcessJobId) is an order number to which the documents to be entered are added and to which the order is confirmed.
   *
   * @param       bool    $hasCustomerAttributes  set true if you want define global customer attributes
   * @param       array   $customerAttributes     Specification of any key-value pairs. Required specification printMode and printColor (see also section CustomerAttribute Parameter).
   * @return      array
   */
  public function prepareProcess( $hasCustomerAttributes = false, $customerAttributes = array() )
  {
    $processArray =
    [
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
    ];

    if ( $hasCustomerAttributes )
    {
      $customerAttributes = self::process_customer_attributes( $customerAttributes );

      array_push( $processArray, [ 'customerAttributes' => $customerAttributes ] );
    }

    try
    {
      $return = $this->SoapClient->prepareProcess( $processArray );

      $result = $return->portalProcessJobId;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * The commitProcess method completes the upload of documents and prepares or triggers processing. The return (portalProcessId) is the order number to which the documents were added.
   *
   * @param       string  $portalProcessJobId     Order number
   * @return      array
   */
  public function commitProcess( $portalProcessJobId )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $reutrn = $this->SoapClient->commitProcess( $processArray );

      $result = $reutrn->portalProcessId;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * Adding a document to an order.
   *
   * @param       string  $portalProcessJobId     Order number
   * @param       string  $fileAsByteArray        File as ByteArray
   * @param       string  $fileName               File name
   * @param       bool    $hasCustomerAttributes  set true if you want define specific customer attributes
   * @param       array   $customerAttributes     Specification of any key-value pairs. Required specification printMode and printColor (see also section CustomerAttribute Parameter).
   * @return      array
   */
  public function addPBPInputSingleFileToPreparedProcess( $portalProcessJobId, $fileAsByteArray, $fileName, $hasCustomerAttributes = false, $customerAttributes = array() )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
      'fileAsByteArray'         => $fileAsByteArray,
      'fileName'                => $fileName,
    ];

    if ( $hasCustomerAttributes )
    {
      $customerAttributes = self::process_customer_attributes( $customerAttributes );

      array_push( $processArray, [ 'customerAttributes' => $customerAttributes ] );
    }

    try
    {
      $return = $this->SoapClient->addPBPInputSingleFileToPreparedProcess( $processArray );

      $result = $return->return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * Add serial letter with page separation according to fixed page numbers to the order.
   *
   * @param       string  $portalProcessJobId     Order number
   * @param       string  $fileAsByteArray        File as ByteArray
   * @param       string  $fileName               File name
   * @param       string  $pagesPerDocument       Number of pages of the mailing.
   * @param       bool    $hasCustomerAttributes  set true if you want define specific customer attributes
   * @param       array   $customerAttributes     Specification of any key-value pairs. Required specification printMode and printColor (see also section CustomerAttribute Parameter).
   * @return      array
   */
  public function addPBPInputFileSplitOnFixedPageToPreparedProcess( $portalProcessJobId, $fileAsByteArray, $fileName, $pagesPerDocument, $hasCustomerAttributes = false, $customerAttributes = array() )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
      'fileAsByteArray'         => $fileAsByteArray,
      'fileName'                => $fileName,
      'pagesPerDocument'        => $pagesPerDocument,
    ];

    if ( $hasCustomerAttributes )
    {
      $customerAttributes = self::process_customer_attributes( $customerAttributes );

      array_push( $processArray, [ 'customerAttributes' => $customerAttributes ] );
    }

    try
    {
      $return = $this->SoapClient->addPBPInputFileSplitOnFixedPageToPreparedProcess( $processArray );

      $result = $return->return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * Add a form letter with page separation using the white text field, which is only contained on the first page, to the order.
   *
   * @param       string  $portalProcessJobId     Order number
   * @param       string  $fileAsByteArray        File as ByteArray
   * @param       string  $fileName               File name
   * @param       string  $textMark               White text field which is included in the document.
   * @param       bool    $hasCustomerAttributes  set true if you want define specific customer attributes
   * @param       array   $customerAttributes     Specification of any key-value pairs. Required specification printMode and printColor (see also section CustomerAttribute Parameter).
   * @return      array
   */
  public function addPBPInputFileSplitOnMarkToPreparedProcess( $portalProcessJobId, $fileAsByteArray, $fileName, $textMark, $hasCustomerAttributes = false, $customerAttributes = array() )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
      'fileAsByteArray'         => $fileAsByteArray,
      'fileName'                => $fileName,
      'textMark'                => $textMark,
    ];

    if ( $hasCustomerAttributes )
    {
      $customerAttributes = self::process_customer_attributes( $customerAttributes );

      array_push( $processArray, [ 'customerAttributes' => $customerAttributes ] );
    }

    try
    {
      $return = $this->SoapClient->addPBPInputFileSplitOnMarkToPreparedProcess( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * Serial letter with a template and an address file or data file.
   *
   * @param       string  $portalProcessJobId       Order number
   * @param       string  $fileAsByteArray          File as ByteArray
   * @param       string  $fileNameCsv              File name
   * @param       string  $fileNameTemplate         File name of template file.
   * @param       string  $fileTemplateAsByteArray  Templatefile as ByteArray..
   * @param       bool    $hasCustomerAttributes    set true if you want define specific customer attributes
   * @param       array   $customerAttributes       Specification of any key-value pairs. Required specification printMode and printColor (see also section CustomerAttribute Parameter).
   * @return      array
   */
  public function addPBPInputCsvTemplateToPreparedProcess( $portalProcessJobId, $fileAsByteArray, $fileNameCsv, $fileNameTemplate, $fileTemplateAsByteArray, $hasCustomerAttributes = false, $customerAttributes = array() )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
      'fileAsByteArray'         => $fileAsByteArray,
      'fileNameCsv'             => $fileNameCsv,
      'fileNameTemplate'        => $fileNameTemplate,
      'fileTemplateAsByteArray' => $fileTemplateAsByteArray,
    ];

    if ( $hasCustomerAttributes )
    {
      $customerAttributes = self::process_customer_attributes( $customerAttributes );

      array_push( $processArray, [ 'customerAttributes' => $customerAttributes ] );
    }

    try
    {
      $return = $this->SoapClient->addPBPInputCsvTemplateToPreparedProcess( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method allows one or more attachments to be added to an order. The attachments are added to all documents of the order during generation.
   *
   * @param       string  $portalProcessJobId       Order number
   * @param       string  $fileAsByteArray          File as ByteArray
   * @param       string  $fileName                 File name
   * @param       bool    $hasCustomerAttributes    set true if you want define specific customer attributes
   * @param       array   $customerAttributes       Specification of any key-value pairs. Required specification printMode and printColor (see also section CustomerAttribute Parameter).
   * @return      array
   */
  public function addPBPFileAsAttachmentToPreparedProcess( $portalProcessJobId, $fileAsByteArray, $fileName, $hasCustomerAttributes = false, $customerAttributes = array() )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
      'fileAsByteArray'         => $fileAsByteArray,
      'fileName'                => $fileName,
    ];

    if ( $hasCustomerAttributes )
    {
      $customerAttributes = self::process_customer_attributes( $customerAttributes );

      array_push( $processArray, [ 'customerAttributes' => $customerAttributes ] );
    }

    try
    {
      $return = $this->SoapClient->addPBPFileAsAttachmentToPreparedProcess( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method can be used to add a letterhead to an order. Here the letterhead and the documents of the ProcessJob are merged during the generation.
   *
   * @param       string  $portalProcessJobId       Order number
   * @param       string  $fileAsByteArray          File as ByteArray
   * @param       string  $fileName                 File name
   * @param       bool    $hasCustomerAttributes    set true if you want define specific customer attributes
   * @param       array   $customerAttributes       Specification of any key-value pairs. Required specification printMode and printColor (see also section CustomerAttribute Parameter).
   * @return      array
   */
  public function addPBPFileAsLetterPaperToPreparedProcess( $portalProcessJobId, $fileAsByteArray, $fileName, $hasCustomerAttributes = false, $customerAttributes = array() )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
      'fileAsByteArray'         => $fileAsByteArray,
      'fileName'                => $fileName,
    ];

    if ( $hasCustomerAttributes )
    {
      $customerAttributes = self::process_customer_attributes( $customerAttributes );

      array_push( $processArray, [ 'customerAttributes' => $customerAttributes ] );
    }

    try
    {
      $return = $this->SoapClient->addPBPFileAsLetterPaperToPreparedProcess( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method allows one or more attachments to be added to a document in an order. The attachments are added to all documents of the order during generation.
   *
   * @param       string  $portalProcessJobId       Order number
   * @param       string  $fileAsByteArray          File as ByteArray
   * @param       string  $fileName                 File name
   * @param       bool    $hasCustomerAttributes    set true if you want define specific customer attributes
   * @param       array   $customerAttributes       Specification of any key-value pairs. Required specification printMode and printColor (see also section CustomerAttribute Parameter).
   * @return      array
   */
  public function addPBPFileAsAttachmentToPreparedProcessAndInput( $portalProcessJobId, $inputId, $fileAsByteArray, $fileName, $hasCustomerAttributes = false, $customerAttributes = array() )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
      'fileAsByteArray'         => $fileAsByteArray,
      'fileName'                => $fileName,
      'inputId'                 => $inputId,
    ];

    if ( $hasCustomerAttributes )
    {
      $customerAttributes = self::process_customer_attributes( $customerAttributes );

      array_push( $processArray, [ 'customerAttributes' => $customerAttributes ] );
    }

    try
    {
      $return = $this->SoapClient->addPBPFileAsAttachmentToPreparedProcessAndInput( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method can be used to attach a letterhead to a document of an order. Here the letterhead and the documents of the ProcessJob are merged during the generation.
   *
   * @param       string  $portalProcessJobId       Order number
   * @param       string  $fileAsByteArray          File as ByteArray
   * @param       string  $fileName                 File name
   * @param       bool    $hasCustomerAttributes    set true if you want define specific customer attributes
   * @param       array   $customerAttributes       Specification of any key-value pairs. Required specification printMode and printColor (see also section CustomerAttribute Parameter).
   * @return      array
   */
  public function addPBPFileAsLetterPaperToPreparedProcessAndInput( $portalProcessJobId, $inputId, $fileAsByteArray, $fileName, $hasCustomerAttributes = false, $customerAttributes = array() )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
      'fileAsByteArray'         => $fileAsByteArray,
      'fileName'                => $fileName,
      'inputId'                 => $inputId,
    ];

    if ( $hasCustomerAttributes )
    {
      $customerAttributes = self::process_customer_attributes( $customerAttributes );

      array_push( $processArray, [ 'customerAttributes' => $customerAttributes ] );
    }

    try
    {
      $return = $this->SoapClient->addPBPFileAsLetterPaperToPreparedProcessAndInput( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method can be used to view order statistics (total number of mailings and number of mailings) that are in 'OK', 'Warning', 'Error', and 'Unprocessed' status.
   *
   * @param       string  $portalProcessJobId       Order number
   * @return      array
   */
  public function getPortalProcessJobStatisticByPortalProcessJobId( $portalProcessJobId )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalProcessJobStatisticByPortalProcessJobId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * Retrieval of a document.
   *
   * @param       string  $portalDocumentId   Document number
   * @return      array
   */
  public function getPortalDocumentBinaryByPortalDocumentId( $portalDocumentId )
  {
    $processArray =
    [
      'portalDocumentId'        => $portalDocumentId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalDocumentBinaryByPortalDocumentId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method is used to retrieve a document, including markings for electronic franking (marking for postage paid, German term: DV-Freimachung).
   *
   * @param       string  $portalDocumentId   Document number
   * @return      array
   */
  public function getPortalDocumentFileWithMarksByPortalDocumentId( $portalDocumentId )
  {
    $processArray =
    [
      'portalDocumentId'        => $portalDocumentId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalDocumentFileWithMarksByPortalDocumentId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method finds all portal documents for an order.
   *
   * @param       string  $portalProcessJobId   Order number
   * @return      array
   */
  public function getPortalDocumentCollectionByPortalProcessJobId( $portalProcessJobId )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalDocumentCollectionByPortalProcessJobId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * An image of the recipient field can be retrieved using this method.
   *
   * @param       string  $portalDocumentId   Document number
   * @return      array
   */
  public function getPortalDocumentAddressImageFileByPortalDocumentId( $portalDocumentId )
  {
    $processArray =
    [
      'portalDocumentId'      => $portalDocumentId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalDocumentAddressImageFileByPortalDocumentId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method can be used to determine the number of items in an order.
   *
   * @param       string  $portalProcessJobId   Order number
   * @return      array
   */
  public function getNumberOfDocumentsByPortalProcessJobId( $portalProcessJobId )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getNumberOfDocumentsByPortalProcessJobId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * The number of mailings in an order can be determined using this method.
   *
   * @param       string  $portalProcessJobId       Order number
   * @param       string  $customerAttribute        Attribute (Key)
   * @param       string  $customerAttributeValue   value
   * @return      array
   */
  public function setPortalProcessJobCustomerAttributeByPortalProcessJobId( $portalProcessJobId, $customerAttribute, $customerAttributeValue )
  {
    $processArray =
    [
      'portalProcessJobId'      => $portalProcessJobId,
      'codeFrontend'            => $this->options['codeFrontend'],
      'referenceCustomerNumber' => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'   => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch' => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend' => $this->options['referenceSenderFrontend'],
      'customerAttribute'       => $customerAttribute,
      'customerAttributeValue'  => $customerAttributeValue,
    ];

    try
    {
      $return = $this->SoapClient->setPortalProcessJobCustomerAttributeByPortalProcessJobId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method is used to change the document status, for example, if a document status is WARNING_USER_INTERACTION_REQUIRED.
   *
   * @param       string  $portalProcessJobId         Order number
   * @param       string  $portalDocumentIds          Document number
   * @param       string  $portalDocumentStatusCode   New document status code
   * @return      array
   */
  public function setPortalDocumentStatusCodeByPortalProcessJobIdAndPortalDocumentIds( $portalProcessJobId, $portalDocumentIds, $portalDocumentStatusCode )
  {
    $processArray =
    [
      'portalProcessJobId'        => $portalProcessJobId,
      'codeFrontend'              => $this->options['codeFrontend'],
      'referenceCustomerNumber'   => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'     => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'   => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'   => $this->options['referenceSenderFrontend'],
      'portalDocumentIds'         => $portalDocumentIds,
      'portalDocumentStatusCode'  => $portalDocumentStatusCode,
    ];

    try
    {
      $return = $this->SoapClient->setPortalDocumentStatusCodeByPortalProcessJobIdAndPortalDocumentIds( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * With this method the article information of an order can be retrieved.
   *
   * @param       string  $portalProcessJobId         Order number
   * @return      array
   */
  public function findPortalDocumentArticleInformationByPortalProcessJobId( $portalProcessJobId )
  {
    $processArray =
    [
      'portalProcessJobId'        => $portalProcessJobId,
      'codeFrontend'              => $this->options['codeFrontend'],
      'referenceCustomerNumber'   => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'     => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'   => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'   => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->findPortalDocumentArticleInformationByPortalProcessJobId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * With this method the article information of an order can be retrieved.
   *
   * @param       string  $portalDocumentId         Document number
   * @return      array
   */
  public function getPortalDocumentAddressImageBinaryByPortalDocumentId( $portalDocumentId )
  {
    $processArray =
    [
      'portalDocumentId'          => $portalDocumentId,
      'codeFrontend'              => $this->options['codeFrontend'],
      'referenceCustomerNumber'   => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'     => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'   => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'   => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalDocumentAddressImageBinaryByPortalDocumentId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method is used to change the order status, for example, in the case of manual approval.
   *
   * @param       string  $portalProcessJobId           Order number
   * @param       string  $portalProcessJobStatusCode   New order status
   * @return      array
   */
  public function setPortalProcessJobStatusCodeByPortalProcessJobId( $portalProcessJobId, $portalProcessJobStatusCode )
  {
    $processArray =
    [
      'portalProcessJobId'          => $portalProcessJobId,
      'codeFrontend'                => $this->options['codeFrontend'],
      'referenceCustomerNumber'     => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'       => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'     => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'     => $this->options['referenceSenderFrontend'],
      'portalProcessJobStatusCode'  => $portalProcessJobStatusCode,
    ];

    try
    {
      $return = $this->SoapClient->setPortalProcessJobStatusCodeByPortalProcessJobId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * All information of a set mailing can be called up with this method.
   *
   * @param       string  $portalDocumentId           Document number
   * @return      array
   */
  public function getPortalDocumentById( $portalDocumentId )
  {
    $processArray =
    [
      'portalDocumentId'          => $portalDocumentId,
      'codeFrontend'                => $this->options['codeFrontend'],
      'referenceCustomerNumber'     => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'       => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'     => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'     => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalDocumentById( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method is used to retrieve a set document.
   *
   * @param       string  $portalDocumentId           Document number
   * @return      array
   */
  public function getPortalDocumentFileByPortalDocumentId( $portalDocumentId )
  {
    $processArray =
    [
      'portalDocumentId'          => $portalDocumentId,
      'codeFrontend'                => $this->options['codeFrontend'],
      'referenceCustomerNumber'     => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'       => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'     => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'     => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalDocumentFileByPortalDocumentId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method is used to get the error symptoms of a mailing.
   *
   * @param       string  $portalDocumentId           Document number
   * @return      array
   */
  public function getPortalDocumentErrorImageBinaryByPortalDocumentId( $portalDocumentId )
  {
    $processArray =
    [
      'portalDocumentId'          => $portalDocumentId,
      'codeFrontend'                => $this->options['codeFrontend'],
      'referenceCustomerNumber'     => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'       => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'     => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'     => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalDocumentErrorImageBinaryByPortalDocumentId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method is used to get the error symptoms of a mailing.
   *
   * @param       string  $portalDocumentId           Document number
   * @return      array
   */
  public function getPortalDocumentErrorImageFileByPortalDocumentId( $portalDocumentId )
  {
    $processArray =
    [
      'portalProcessJobId'          => $portalProcessJobId,
      'codeFrontend'                => $this->options['codeFrontend'],
      'referenceCustomerNumber'     => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'       => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'     => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'     => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalDocumentErrorImageFileByPortalDocumentId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method is used to retrieve the number of pages in an order.
   *
   * @param       string  $portalProcessJobId   Order number
   * @return      array
   */
  public function getNumberOfPagesLogicalByPortalProcessJobId( $portalProcessJobId )
  {
    $processArray =
    [
      'portalProcessJobId'          => $portalProcessJobId,
      'codeFrontend'                => $this->options['codeFrontend'],
      'referenceCustomerNumber'     => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'       => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'     => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'     => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getNumberOfPagesLogicalByPortalProcessJobId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method is used to retrieve an image of a mailing, including markings for electronic franking (marking for postage paid, German term: DV-Freimachung).
   *
   * @param       string  $portalDocumentId   Document number
   * @return      array
   */
  public function getPortalDocumentImageFileWithMarksByPortalDocumentId( $portalDocumentId )
  {
    $processArray =
    [
      'portalDocumentId'            => $portalDocumentId,
      'codeFrontend'                => $this->options['codeFrontend'],
      'referenceCustomerNumber'     => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'       => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'     => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'     => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalDocumentImageFileWithMarksByPortalDocumentId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * All the information and the mailings of an order can be retrieved using this method.
   *
   * @param       string  $portalProcessJobId   Order number
   * @return      array
   */
  public function getPortalProcessJobById( $portalProcessJobId )
  {
    $processArray =
    [
      'portalProcessJobId'          => $portalProcessJobId,
      'codeFrontend'                => $this->options['codeFrontend'],
      'referenceCustomerNumber'     => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'       => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'     => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'     => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalProcessJobById( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * This method can beused to method to retrieve the transferred CustomerAttributes.
   *
   * @param       string  $portalProcessJobId   Order number
   * @return      array
   */
  public function findPortalProcessJobCustomerAttributesByPortalProcessJobId( $portalProcessJobId )
  {
    $processArray =
    [
      'portalProcessJobId'          => $portalProcessJobId,
      'codeFrontend'                => $this->options['codeFrontend'],
      'referenceCustomerNumber'     => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'       => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'     => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'     => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->findPortalProcessJobCustomerAttributesByPortalProcessJobId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * With this method the status of a shipment can be changed based on an order number and the current status.
   *
   * @param       string  $portalProcessJobId                 Order number
   * @param       string  $currentPortalDocumentStatusCode    Current document status
   * @param       string  $portalDocumentStatusCode           New document status
   * @return      array
   */
  public function setPortalDocumentStatusCodeByPortalProcessJobIdAndPortalDocumentStatusCode( $portalProcessJobId, $currentPortalDocumentStatusCode, $portalDocumentStatusCode )
  {
    $processArray =
    [
      'portalProcessJobId'              => $portalProcessJobId,
      'codeFrontend'                    => $this->options['codeFrontend'],
      'referenceCustomerNumber'         => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'           => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'         => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'         => $this->options['referenceSenderFrontend'],
      'currentPortalDocumentStatusCode' => $currentPortalDocumentStatusCode,
      'portalDocumentStatusCode '       => $portalDocumentStatusCode,
    ];

    try
    {
      $return = $this->SoapClient->setPortalDocumentStatusCodeByPortalProcessJobIdAndPortalDocumentStatusCode( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * With this method the status of a mailing can be changed based on an order number and a document number.
   *
   * @param       string  $portalProcessJobId                 Order number
   * @param       string  $portalDocumentId                   Document number
   * @param       string  $portalDocumentStatusCode           New document status
   * @return      array
   */
  public function setPortalDocumentStatusCodeByPortalProcessJobIdAndPortalDocumentId( $portalProcessJobId, $portalDocumentId, $portalDocumentStatusCode )
  {
    $processArray =
    [
      'portalProcessJobId'              => $portalProcessJobId,
      'codeFrontend'                    => $this->options['codeFrontend'],
      'referenceCustomerNumber'         => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'           => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'         => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'         => $this->options['referenceSenderFrontend'],
      'portalDocumentId'                => $portalDocumentId,
      'portalDocumentStatusCode '       => $portalDocumentStatusCode,
    ];

    try
    {
      $return = $this->SoapClient->setPortalDocumentStatusCodeByPortalProcessJobIdAndPortalDocumentId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }

  /**
   * With this method the current order status can be retrieved.
   *
   * @param       string  $portalProcessJobId   Order number
   * @return      array
   */
  public function getPortalProcessJobStatusCodeByPortalProcessJobId( $portalProcessJobId )
  {
    $processArray =
    [
      'portalProcessJobId'              => $portalProcessJobId,
      'codeFrontend'                    => $this->options['codeFrontend'],
      'referenceCustomerNumber'         => $this->options['referenceCustomerNumber'],
      'referenceCustomerUser'           => $this->options['referenceCustomerUser'],
      'referenceCustomerBranch'         => $this->options['referenceCustomerBranch'],
      'referenceSenderFrontend'         => $this->options['referenceSenderFrontend'],
    ];

    try
    {
      $return = $this->SoapClient->getPortalProcessJobStatusCodeByPortalProcessJobId( $processArray );

      $result = $return;
      $error  = 0;
    }
    catch(SoapFault $excaption)
    {
      $result = $excaption->getMessage();
      $error  = 1;
    }

    $response =
    [
      'error'   => $error,
      'result'  => $result,
    ];

    return $response;
  }
}
