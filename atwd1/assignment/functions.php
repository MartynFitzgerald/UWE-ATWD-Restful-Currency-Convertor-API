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
    //Rename XML file to inlcude date
    copy("./data/" . $GLOBALS['ratesFilename'], "./data/rates" . $timeStampSuffix . ".xml");
}
//Function defination to convert array to xml
//source: https://www.codexworld.com/convert-array-to-xml-in-php/
function arrayToXML($array, &$xml_user_info) {
    foreach($array as $key => $value) {
        if(is_array($value)) {
            if(!is_numeric($key)) {
                $subnode = $xml_user_info->addChild("$key");
                arrayToXML($value, $subnode);
            }
            else {
                $subnode = $xml_user_info->addChild("item$key");
                arrayToXML($value, $subnode);
            }
        }
        else {
            $xml_user_info->addChild("$key",htmlspecialchars("$value"));
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
//
function outputErrorMessageResponse($errorCode, $message = null){
    if ($message == null) {
        $message = getErrorMessageByErrorCode($errorCode);
    }
        
    $dataNode = array("code"=>$errorCode, "msg"=>(string) $message);
    $errorNode = array("error"=>$dataNode);
    $outputNode = array("conv"=>$errorNode);

    //$outputNode = constructOutputArray($dataArray, "error");

    convertArrayToFormatForOutput($outputNode); 
    //Terminate the current script 
    exit();
}
//Get list of the rates.
function getRatesFromDataFile(){
    //Getting rates information from the file stored locally. @ is suppress wearning so we can handle myself.
    try {
        if (is_file('./data/' . $GLOBALS['ratesFilename'])) {
            $xml = @simplexml_load_file('./data/' . $GLOBALS['ratesFilename']);
        }
        else {
            $xml = @simplexml_load_file('../data/'. $GLOBALS['ratesFilename']);
        }
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
        if (is_file('./data/errors.xml')) {
            $xml = @simplexml_load_file('./data/errors.xml');
        }
        else {
            $xml = @simplexml_load_file('../data/errors.xml');
        }
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
        if (is_file('./data/countries.xml')) {
            $xml = @simplexml_load_file('./data/countries.xml');
        }
        else {
            $xml = @simplexml_load_file('../data/countries.xml');
        }
    } catch (Exception $e) {
        //Send error message to user and then kill the service
        outputErrorMessageResponse(1500);
    }
    return $xml;
}
//Get list of the currencies and their rates at current time.
function getCurrencyRatesFromExternalAPI() {
    //Getting current currencies information from the API. @ is suppress wearning so we can handle myself.
    $json = @file_get_contents('http://data.fixer.io/api/latest?access_key=313f82e98f94595c11df26da43b9835f');
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
    //Base currency for the rates stored.
    $baseCurrency = "GBP";

    //Creating timestamp for when created in XML document
    $timeStamp =  time();
    //Create XML Document
    $dom = new DOMDocument("1.0");
    //Creating "currencies" Node
    $root = $dom->createElement("currencies");
    //Adding attribute "base" and "ts" to "currencies" Node
    $root->setAttributeNode(new DOMAttr("base", $baseCurrency));
    $root->setAttributeNode(new DOMAttr("ts", $timeStamp));
    //Setting root to the XML document
    $dom->appendChild($root);

    //This loop cycles through the predefined rates.
    for ($i = 0; $i < sizeof($currenciesISOCodes); $i++) {
        //Calculating the rate, since the API base currency isnt GBP
        $currencyRate = $currencies[$currenciesISOCodes[$i]] / $currencies[$baseCurrency];
        //Setting the ISO code for this currency
        $currencyCode = $currenciesISOCodes[$i];
        //Create main currency node for each currency pre-defined
        $itemNode = $dom->createElement("currency");
        //Adding attributes to the  currency node above
        $itemNode->setAttributeNode(new DOMAttr("rate", $currencyRate));
        $itemNode->setAttributeNode(new DOMAttr("code", $currencyCode));
        //Attach the main currency node to the main node in the XML document
        $root->appendChild($itemNode);
    }
    //Saving XML document to the filename defined above
    $dom->save('./data/'. $GLOBALS['ratesFilename']);
}
//Get the data from api and inialized rates XML
function initializeDataFromAPI() {
    //This array holds the predefined rates for the application. 
    $currenciesISOCodes = ["AUD","BRL","CAD","CHF","CNY","DKK","EUR","GBP","HKD","HUF","INR","JPY","MXN","MYR","NOK","NZD","PHP","RUB","SEK","SGD","THB","TRY","USD","ZAR"];
    //Call function to get current currencies information from the API
    $currentCurrencies = getCurrencyRatesFromExternalAPI();
    //Setting the rates of all currency rates to the a varible.
    $currencies = $currentCurrencies["rates"];
    //Call function to create new rates file
    initializeRatesXML($currenciesISOCodes, $currencies);
}
//Get the data from api and inialized rates XML
function updateDataFromAPI($currentRates) {
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
    //Call function to create new rates file
    initializeRatesXML($currenciesISOCodes, $currencies);
}
//Check if the parameters in the GET method are correct to the ones pre defined
function checkRequestKeys($amountOfGetKeys, $amountOfGetParameters, $getPreDefinedParameters) {
    //Check if there is the wrong amount of parameters
    if ($amountOfGetKeys == $amountOfGetParameters) {
        //Cycle through all of the HTTP GET Request keys
        for($i = 0; $i < $amountOfGetKeys; $i++) {
            //Cycle through all of the pre-defined expected parameters 
            for($j = 0; $j < $amountOfGetParameters; $j++) {
                //Check if the HTTP GET Request key equals to the expected parameter
                if (array_keys($_REQUEST)[$i] == $getPreDefinedParameters[$j]) {
                    //If it does then break out of the first for loop to move on to the next HTTP GET Request key
                    break;
                }
                else {
                    //If not then check if it has cycled through all of the pre-definedexpected parameters 
                    if ($j >= $amountOfGetParameters - 1) {
                        //Output error 1100 - Parameter not recognized
                        outputErrorMessageResponse(1100); 
                    }
                }
            }
        }
    }
    else if ($amountOfGetKeys > $amountOfGetParameters) {
        //Output error 1100 - Parameter not recognized
        outputErrorMessageResponse(1100); 
    }
    else if ($amountOfGetKeys < $amountOfGetParameters) {
        //Output error 1000 - Required parameter is missing
        outputErrorMessageResponse(1000); 
    }
}
// Checking the format and retunr it.
function checkFormatIsXmlOrJson($format) {
    if ($format != "json" && $format != "xml" && $format != null){
        //Output error 1400	- Format must be xml or json
        var_dump($format);
        outputErrorMessageResponse(1400); 
    } 
}
//Checking if the amount given is a float. 
function checkAmountIsFloat($value) {
    if (!is_numeric($value ) || floor($value ) != $value) {
        //Output error 1300 - Currency amount must be a decimal number 
        outputErrorMessageResponse(1300); 
    }
}
//check if the currency code is valid to rates.
function checkCurrencyCode($rates, $currencyCode) {
    //Getting the currency code from the XML data file
    $ratesCode = $rates->xpath("/currencies/currency[@code='". $currencyCode ."']/@code");
    //If the Xpath returned false then show error
    if (@$ratesCode[0] != $currencyCode) {   
        //Output error 1200 - Currency type not recognized 
        outputErrorMessageResponse(1200);  
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
    $countryLocation = ucwords(strtolower(implode(", ",$currencyLocations)));
    return $countryLocation;
}
//Get currency rate in the rates API.
function getNewCurrencyRate($cur){
    //Getting currencies information from the APi.
    $currencies = getCurrencyRatesFromExternalAPI();
    //Setting the rates of all currency rates to the a varible.
    return ($currencies["rates"][$cur] / $currencies["rates"]["GBP"]);
}
?>