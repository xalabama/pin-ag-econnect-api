# PHP Setup

The class will be integrated into your project as usual. 
You can also use Composer for this. In the following examples, the class is simply included for the sake of simplicity.

## Example

```php
require( dirname( __FILE__ ) . '/PinAg_EConnectApi/PinAg_EConnectApi.php' );

$eConnectApiOptions = 
[ 
  'referenceSenderFrontend' => 'USERNAME',          // API Username - like recived from PIN AG
  'codeFrontend'            => 'PASSWORD',          // API Password - You have to set your password on self by first login
  'referenceCustomerNumber' => 'REFERENCENUMBER',   // API CustomerNumber - like recived from PIN AG
  'apiMode'                 => 'staging',           // API Mode -- live / staging
];

$EConnectApi = new PinAg_EConnectApi( $eConnectApiOptions );
```
After the API is initialized, you need to start a new process. You do this with the `prepareProcess` function. 
This function also serves as your first login to eConnect. 
If successful, the parameter `portalProcessJobId` is returned as `result` in the output array.

```php
$prepareProcessResponse = $EConnectApi->prepareProcess();
$portalProcessJobId     = $prepareProcessResponse['result];
```

If successful, the following array is output with a similar value
```php
Array 
( 
  [error] => 0            // Indicates whether an error has occurred
  [result] => 471274425   // Shows the corresponding parameter or a SOAP error code in case of an error
)
```

Now you can start adding documents to the current process. For example we will use the simple function `addPBPInputSingleFileToPreparedProcess`
```php
$addPBPInputSingleFileToPreparedProcessResponse = $EConnectApi->addPBPInputSingleFileToPreparedProcess( $portalProcessJobId, $fileAsByteArray, $fileName, true );
```

After a document has been successfully added, you can perform further functions or complete the job with the `commitProcess' function.
```php
$commitProcessResponse = $EConnectApi->commitProcess( $portalProcessJobId );
```

For further information, please read the eConnect documentation provided by PIN AG.
