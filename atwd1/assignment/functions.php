<?php
/*=============================================================================
|      Editors:  Martyn Fitzgerald - 16025948
|
|  Module Code:  UFCFX3-15-3
| Module Title:  ADVANCED TOPICS IN WEB DEVELOPMENT 1
|                
|   Instructor:  Prakash Chatterjee
|     Due Date:  21/11/2019
|
|  Description:  Creating functions used in both of the index.php file
|
*===========================================================================*/
function archiveRatesFile($timeStampSuffix) {
    //Find position of dot after the 3rd value
    $pos = strrpos(RATES_PATH_DIRECTORY, ".", 3);
    //Remove file extention
    $filePathWithoutExenstion = substr(RATES_PATH_DIRECTORY,0,$pos); 
    //Rename XML file to inlcude date
    copy(RATES_PATH_DIRECTORY, $filePathWithoutExenstion . $timeStampSuffix . ".xml");
}
//Function defination to convert array to xml
//source: https://www.codexworld.com/convert-array-to-xml-in-php/
function arrayToXML($array, &$xmlDocument) {
    foreach($array as $key => $value) {
        if(is_array($value)) {
            if(!is_numeric($key)) {
                $subnode = $xmlDocument->addChild("$key");
                arrayToXML($value, $subnode);
            }
            else {
                $subnode = $xmlDocument->addChild("item$key");
                arrayToXML($value, $subnode);
            }
        }
        else {
            $xmlDocument->addChild("$key",htmlspecialchars("$value"));
        }
    }
}
//This is converts the PHP array to XML or JSON depending on request  
function convertArrayToFormatForOutput($outputNode, $format = null,$actionType = null) {
    //Default to XML if json isn't specified  
    if ($format == "json") {
        $outputJSON = json_encode($outputNode);
        //displayJSON($outputJSON);

        header('Content-Type: application/json');
        echo $outputJSON;
    } else {
        $firstNodeKey = array_keys($outputNode)[0];

        if ($actionType != null) {
            $dom = new SimpleXMLElement("<?xml version=\"1.0\"?><" . $firstNodeKey . " type='". $actionType  ."'></" . $firstNodeKey . ">");
        } else {
            $dom = new SimpleXMLElement("<?xml version=\"1.0\"?><" . $firstNodeKey . "></" . $firstNodeKey . ">");
        }
        arrayToXML($outputNode[$firstNodeKey], $dom);
        header('Content-Type: text/xml');
        echo $dom->asXML();
    }
}
//Search list of the errors with the code to get the message.
function getErrorMessageByErrorCode($errorCode){
    //Getting errors information from the file stored locally.
    $errors = getErrorsFromDataFile();
    //Getting the error message from the XML data file
    $errorMessage = $errors->xpath("/errors/error[code='" . $errorCode . "']/message");
    return $errorMessage[0];
}
//This outputs the error message to the user. Then exits out of the programe.
function outputErrorMessageResponse($errorCode, $message = null){
    //If message isn't declared then search for it in the errors.xml
    if ($message == null) {
        $message = getErrorMessageByErrorCode($errorCode);
    }
    //Build the PHP array so we can convert it too xml or json.
    $dataNode = array("code"=>$errorCode, "msg"=>(string) $message);
    $errorNode = array("error"=>$dataNode);
    $outputNode = array("conv"=>$errorNode);
    convertArrayToFormatForOutput($outputNode); 
    //Terminate the current script 
    exit();
}
//Get list of the rates.
function getRatesFromDataFile(){
    //Getting rates information from the file stored locally. @ is suppress wearning so we can handle myself.
    try {
        $xml = @simplexml_load_file(RATES_PATH_DIRECTORY);
    } catch (Exception $e) {
        //rates does not exist - even after trying to create the file
        outputErrorMessageResponse(1500, "Error in service");
    }
    return $xml;
}
//Get list of the errors. 
function getErrorsFromDataFile(){
    //Getting errors information from the file stored locally. @ is suppress wearning so we can handle myself.
    try {
        $xml = @simplexml_load_file(ERRORS_PATH_DIRECTORY);
    } catch (Exception $e) {
        //Send error message to user and then kill the service
        outputErrorMessageResponse(1500, "Error in service");
    }
    return $xml;
}
//Get list of the contries that support all of the currencies. 
function getCountriesFromDataFile(){
    //Getting contries information from the file stored locally. @ is suppress wearning so we can handle myself.
    try {
        $xml = @simplexml_load_file(COUNTRIES_PATH_DIRECTORY);
    } catch (Exception $e) {
        //Send error message to user and then kill the service
        outputErrorMessageResponse(1500);
    }
    return $xml;
}
//Get list of the currencies and their rates at current time.
function getCurrencyRatesFromExternalAPI() {
    //Getting current currencies information from the API. @ is suppress wearning so we can handle myself.
    $json = @file_get_contents(URL_RATES);
    $jsonFormatted = json_decode($json, true);
    //Check if we don't have valid XML file
    if ($json === false || $jsonFormatted["success"] === false) {
        //Send error message to user and then kill the service
        outputErrorMessageResponse(1500);
    }
    return $jsonFormatted;
}
//Initialize rates file if there isnt one already
function initializeRatesXML($currenciesISOCodes, $currencies) {
    //Creating timestamp for when created in XML document
    $timeStamp =  time();
    //Create XML Document
    $dom = new DOMDocument("1.0");
    //Creating "currencies" Node
    $root = $dom->createElement("currencies");
    //Adding attribute "base" and "ts" to "currencies" Node
    $root->setAttributeNode(new DOMAttr("base", BASE));
    $root->setAttributeNode(new DOMAttr("ts", $timeStamp));
    //Setting root to the XML document
    $dom->appendChild($root);

    //This loop cycles through the predefined rates.
    for ($i = 0; $i < sizeof($currenciesISOCodes); $i++) {
        //Calculating the rate, since the API base currency isnt GBP
        $currencyRate = $currencies[$currenciesISOCodes[$i]] / $currencies[BASE];
        //Setting the ISO code for this currency
        $currencyCode = $currenciesISOCodes[$i];
        //Create main currency node for each currency pre-defined
        $itemNode = $dom->createElement("currency");
        //Adding attributes to the  currency node above
        $itemNode->setAttributeNode(new DOMAttr("rate", $currencyRate));
        $itemNode->setAttributeNode(new DOMAttr("code", $currencyCode));
        $itemNode->setAttributeNode(new DOMAttr("isAvailable", "1"));
        //Attach the main currency node to the main node in the XML document
        $root->appendChild($itemNode);
    }
    //Saving XML document to the filename defined above
    $dom->save(RATES_PATH_DIRECTORY);
}
//Get the data from api and inialized rates XML
function initializeDataFromAPI() {
    //Call function to get current currencies information from the API
    $currentCurrencies = getCurrencyRatesFromExternalAPI();
    //Setting the rates of all currency rates to the a varible.
    $currencies = $currentCurrencies["rates"];
    //Call function to create new rates file
    initializeRatesXML(PRE_DEFINED_ISO_CODES, $currencies);
}
//Get the data from api and inialized rates XML
function updateDataFromAPI($currentRates, $timeStamp, $newCur = null) {
    $currenciesISOCodes = [];
    //Call function to get current currencies information from the API
    $currentCurrencies = getCurrencyRatesFromExternalAPI();
    //Setting the rates of all currency rates to the a varible.
    $currencies = $currentCurrencies["rates"];  
    //Getting the currency from the XML data file
    $currenciesCodes = $currentRates->xpath("/currencies/currency/@code");
    //Go throuhg all currencies codes that are in the file and add them into a array.
    foreach ($currenciesCodes as $currency) {
        array_push($currenciesISOCodes, (string) $currency->code);
    }
    //Add any new currency from put function
    if ($newCur == true)
    {
        array_push($currenciesISOCodes, (string) $newCur);
    }
    //Rename XML file to inlcude date.
    archiveRatesFile($timeStamp);
    //Call function to create new rates file
    initializeRatesXML($currenciesISOCodes, $currencies);
}
//Check if the parameters in the GET method are correct to the ones pre defined
function checkParametersAreRecognized($getPreDefinedParameters) {
    //Get the amount of values within the parameters expected
    $amountOfGetParameters = sizeof($getPreDefinedParameters);
    //Get the amount of values within the current parameters
    $amountOfGetKeys = sizeof(array_keys($_REQUEST));
    //Check if there is the wrong amount of parameters
    for($i = 0; $i < $amountOfGetKeys; $i++) {
        //Cycle through all of the pre-defined expected parameters 
        for($j = 0; $j < $amountOfGetParameters; $j++) {
            //Check if the HTTP GET Request key equals to the expected parameter
            if (array_keys($_REQUEST)[$i] == $getPreDefinedParameters[$j]) {
                //If it does then break out of the first for loop to move on to the next HTTP GET Request key
                break;
            } else {
                //If not then check if it has cycled through all of the pre-definedexpected parameters 
                if ($j >= $amountOfGetParameters - 1) {
                    //Output error 1100 - Parameter not recognized
                    outputErrorMessageResponse(1100); 
                }
            }
        }
    }
}
//Checking if the amount given is a float. 
function checkAmountIsFloat($value) {
    if (!(is_numeric($value ) || floor($value ) != $value)){
        //Output error 1300 - Currency amount must be a decimal number 
        outputErrorMessageResponse(1300); 
    }
}
//check if the currency code is valid to rates.
function checkCurrencyCode($rates, $currencyCode, $errorCode) {
    //Getting the currency code from the XML data file
    $ratesCode = $rates->xpath("/currencies/currency[@code='". $currencyCode ."']/@code");
    //If the Xpath returned false then show error
    if (@$ratesCode[0] != $currencyCode) {   
        //Output error 1200 || 2200 - Currency type not recognized 
        outputErrorMessageResponse($errorCode);  
    }
}
//check if the currency code is valid to countries.
function checkCurrencyCodeToXML($currencyCode) {
    //Getting contries information from the file stored locally.
    $countries = getCountriesFromDataFile();
    //Getting the currency code from the XML data file
    $currencyLocations = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='". $currencyCode ."']/Ccy");
    //If the Xpath returned false then show error
    if (@$currencyLocations[0] != $currencyCode) {  
        //Output error 2200 - Currency code not found for update 
        outputErrorMessageResponse(2200);  
    }
}
//Get the currency's country name.
function getCountryNameForCurrencyCode($countries, $currencyCode){
    //Getting the currency name from the XML data file
    $countryName = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='" . $currencyCode . "']/CcyNm");
    return $countryName[0];
}
//Get the currency's locations where it's used.
function getCountryLocationForCurrencyCode($countries, $currencyCode){
    //Getting the currency name from the XML data file
    $currencyLocations = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='" . $currencyCode . "']/CtryNm");
    //Formatted the locations to put them into a string and also capitalization the first letter within a word
    $countryLocation = str_replace(['Of', 'And'], ['of', 'and'], ucwords(strtolower(implode(", ",$currencyLocations))));
    return $countryLocation;
}
?>