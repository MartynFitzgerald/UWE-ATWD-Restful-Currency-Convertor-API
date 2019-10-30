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
//Function to output json to the user
function displayJSON($json) {
    header('Content-Type: application/json');
    echo $json;
}
//Function to output XML to the user
function displayXML($dom) {
    header('Content-Type: text/xml');
    echo $dom->asXML();
}
//Function to contruct the array with the main node within the output.
function constructOutputArray($dataNode, $type) {
    if($type === "error") {
        $errorNode = array("error"=>$dataNode);
        $outputNode = array("conv"=>$errorNode);
    }
    else if($type === "conv") {
        $outputNode = array("conv"=>$dataNode);
    }
    else if($type === "action") {
        $outputNode = array("action"=>$dataNode);
    }
    return $outputNode;
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
function convertArrayToFormatForOutput($outputNode) {
    //Default to XML if json isn't specified  
    if (@$_REQUEST["format"] === "json") {
        $outputJSON = json_encode($outputNode);
        displayJSON($outputJSON);
    }
    else { 
        $firstNodeKey = array_keys($outputNode)[0];

        $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><" . $firstNodeKey . "></" . $firstNodeKey . ">");
        
        arrayToXML($outputNode[$firstNodeKey],$xml);
        displayXML($xml);
    } 
}
//Search list of the errors with the code to get the message.
function searchForError($errorCode){
    //Getting errors information from the file stored locally.
    $errors = getErrors();

    //Getting the error message from the XML data file
    $errorMessage = $errors->xpath("/errors/error[code='" . $errorCode . "']/message");

    return $errorMessage[0];
}
//
function conductErrorMessage($errorCode, $message = null){
    if ($message == null) {
        $message = searchForError($errorCode);
    }
        
    $dataArray = array("code"=>$errorCode, "msg"=>(string) $message);

    $outputNode = constructOutputArray($dataArray, "error");

    convertArrayToFormatForOutput($outputNode); 
    //Terminate the current script 
    exit();
}
//Get list of the rates.
function getRates(){
    //Getting rates information from the file stored locally. @ is suppress wearning so we can handle myself.
    if (is_file('./data/rates.xml')) {
        $xml = @simplexml_load_file('./data/rates.xml');
    }
    else {
        $xml = @simplexml_load_file('../data/rates.xml');
    }
    //Check if we don't have valid XML file
    if ($xml === false) {
        //Send error message to user and then kill the service
        conductErrorMessage(1500, "Error in service");
    }
    return $xml;
}
//Get list of the errors.
function getErrors(){
    //Getting errors information from the file stored locally. @ is suppress wearning so we can handle myself.
    if (is_file('./data/errors.xml')) {
        $xml = @simplexml_load_file('./data/errors.xml');
    }
    else {
        $xml = @simplexml_load_file('../data/errors.xml');
    }
    //Check if we don't have valid XML file
    if ($xml === false) {
        //Send error message to user and then kill the service
        conductErrorMessage(1500, "Error in service");
    }
    return $xml;
}
//Get list of the contries that support all of the currencies. Also declaring the return to SimpleXMLElement in this function.
function getCountries(){
    //Getting contries information from the file stored locally. @ is suppress wearning so we can handle myself.
    if (is_file('./data/countries.xml')) {
        $xml = @simplexml_load_file('./data/countries.xml');
    }
    else {
        $xml = @simplexml_load_file('../data/countries.xml');
    }
    //Check if we don't have valid XML file
    if ($xml === false) {
        //Send error message to user and then kill the service
        conductErrorMessage(1500);
    }
    return $xml;
}
//Get list of the currencies and their rates at current time.
function getCurrencies() {
    //Getting current currencies information from the API. @ is suppress wearning so we can handle myself.
    $json = @file_get_contents('http://data.fixer.io/api/latest?access_key=313f82e98f94595c11df26da43b9835f');
    $jsonFormatted = json_decode($json, true);
    //Check if we don't have valid XML file
    if ($json === false || $jsonFormatted["success"] === false) {
        //Send error message to user and then kill the service
        conductErrorMessage(1500);
    }
    return $jsonFormatted;
}
//Initialize rates file if there isnt one already
function initializeRatesXML($currenciesISOCodes, $baseCurrency, $xmlFileName, $currencies) {
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
    $dom->save('./data/'. $xmlFileName);
}
//Get the data from api and inialized rates XML
function requestDataFromAPI($currenciesISOCodes, $baseCurrency, $xmlFileName) {
    //Call function to get current currencies information from the API
    $currentCurrencies = getCurrencies();
    //Setting the rates of all currency rates to the a varible.
    $currencies = $currentCurrencies["rates"];
    //Call function to create new rates file
    initializeRatesXML($currenciesISOCodes, $baseCurrency, $xmlFileName, $currencies);
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
                        conductErrorMessage(1100); 
                    }
                }
            }
        }
    }
    else if ($amountOfGetKeys > $amountOfGetParameters) {
        //Output error 1100 - Parameter not recognized
        conductErrorMessage(1100); 
    }
    else if ($amountOfGetKeys < $amountOfGetParameters) {
        //Output error 1000 - Required parameter is missing
        conductErrorMessage(1000); 
    }
}
//Checking if they have given me a format that is allowed.
function checkFormatGetValue() {
    if (!(@$_REQUEST["format"] == "xml" || @$_REQUEST["format"] == "json")) {
        //Output error 1400 - Format must be xml or json 
        conductErrorMessage(1400);
    }
}
//Checking if the amount given is a float. 
function checkAmountIsFloat($value) {
    if (!(is_numeric($value ) || floor($value ) != $value)) {
        //Output error 1300 - Currency amount must be a decimal number 
        conductErrorMessage(1300); 
    }
}
//check if the currency code is valid to rates.
function checkCurrencyCode($currencyCode) {
    //Getting contries information from the file stored locally.
    $rates = getRates();
    //Getting the currency code from the XML data file
    $ratesCode = $rates->xpath("/currencies/currency[@code='". $currencyCode ."']/@code");
    //If the Xpath returned false then show error
    if (!@$ratesCode[0] == $currencyCode) {   
        //Output error 1200 - Currency type not recognized 
        conductErrorMessage(1200);  
    }
}
//check if the currency code is valid to countries.
function checkCurrencyCodeToXML($currencyCode) {
    //Getting contries information from the file stored locally.
    $countries = getCountries();
    //Getting the currency code from the XML data file
    $currencyLocations = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='". $currencyCode ."']/Ccy");
    //If the Xpath returned false then show error
    if (!@$currencyLocations[0] == $currencyCode) {  
        //Output error 2200 - Currency code not found for update 
        conductErrorMessage(2200);  
    }
}
function getCurrencyName($countries, $currencyCode){
    //Getting the currency name from the XML data file
    $currencyName = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='" . $currencyCode . "']/CcyNm");
    return $currencyName[0];
}
function getCurrencyLocationsFormatted($countries, $currencyCode){
    //Getting the currency name from the XML data file
    $currencyLocations = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='" . $currencyCode . "']/CtryNm");
    //Formatted the locations to put them into a string and also capitalization the first letter within a word
    $currencyLocationsFormatted = ucwords(strtolower(implode(", ",$currencyLocations)));
    return $currencyLocationsFormatted;
}
//Display the currency conversion to the user and do the calulation to get value.
function conductConvMessage($countryFrom, $countryTo, $amount, $format){
    //Getting contries information from the file stored locally.
    $countries = getCountries();
    //Getting contries information from the file stored locally.
    $rates = getRates();
    //Getting the currency rate from the XML data file
    $rateTo = $rates->xpath("/currencies/currency[@code='". $countryTo ."']/@rate");
    $ts = $rates->xpath("/currencies/@ts");

    $amountCalculation = round($rateTo[0] * $amount, 2);
    
    $fromArray = array("code"=> $countryFrom, "curr"=> (string) getCurrencyName($countries, $countryFrom), "loc"=> getCurrencyLocationsFormatted($countries, $countryFrom), "amnt"=> (float) $amount);
    $toArray = array("code"=> $countryTo, "curr"=> (string) getCurrencyName($countries, $countryTo), "loc"=> getCurrencyLocationsFormatted($countries, $countryTo), "amnt"=> $amountCalculation);
    $dataArray = array("at"=> date('d M Y H:i', (int) $ts[0]), "rate"=> (float) $rateTo[0], "from"=> $fromArray, "to"=> $toArray);

    $outputNode = constructOutputArray($dataArray, "conv");

    convertArrayToFormatForOutput($outputNode);
}
//Get currency rate in the rates API.
function getNewCurrencyRate($cur){
    //Getting currencies information from the APi.
    $currencies = getCurrencies();
    
    //Setting the rates of all currency rates to the a varible.
    return ($currencies["rates"][$cur] / $currencies["rates"]["GBP"]);
}
//Update currency rate in the rates file.
function updateCurrencyRate($cur){
    
    //Setting the rates of all currency rates to the a varible.
    return ($currencies["rates"][$cur] / $currencies["rates"]["GBP"]);
}
//Display the currency conversion to the user and do the calulation to get value.
function conductPostMessage($action, $cur){
    //Getting contries information from the file stored locally.
    $countries = getCountries();
    //Getting contries information from the file stored locally.
    $rates = getRates();
    //Update currency rate in the rates file.
    $currencies = getNewCurrencyRate($cur);

    $ts = $rates->xpath("/currencies/@ts");

    //Getting the currency rate from the XML data file
    $rate = $rates->xpath("/currencies/currency[@code='". $cur ."']/@rate");
    $oldRate = (float) $rate[0]->rate;
    $rate[0]->rate = $currencies; 
    //echo $rates->asXml();
    // save the values in a new xml
    file_put_contents('./data/rates.xml',$rates->saveXML());
    
    $curArray = array("code"=> $cur, "name"=> (string) getCurrencyName($countries, $cur), "loc"=> getCurrencyLocationsFormatted($countries, $cur));
    $dataArray = array("at"=> date('d M Y H:i', (int) $ts[0]), "rate"=> (float) $currencies, "old_rate"=> $oldRate, "curr"=> $curArray);

    $outputNode = constructOutputArray($dataArray, "action");

    convertArrayToFormatForOutput($outputNode);
}
?>