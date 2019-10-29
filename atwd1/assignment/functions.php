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
    if($type === "error")
    {
        $errorNode = array("error"=>$dataNode);
        $outputNode = array("conv"=>$errorNode);
    }
    else if($type === "conv")
    {
        $outputNode = array("conv"=>$dataNode);
    }
    else if($type === "action")
    {
        $outputNode = array("conv"=>$dataNode);
    }
    return $outputNode;
}
//Function defination to convert array to xml
//source: https://www.codexworld.com/convert-array-to-xml-in-php/
function arrayToXML($array, &$xml_user_info) {
    foreach($array as $key => $value) {
        if(is_array($value)) {
            if(!is_numeric($key)){
                $subnode = $xml_user_info->addChild("$key");
                arrayToXML($value, $subnode);
            }else{
                $subnode = $xml_user_info->addChild("item$key");
                arrayToXML($value, $subnode);
            }
        }else {
            $xml_user_info->addChild("$key",htmlspecialchars("$value"));
        }
    }
}
//This is converts the PHP array to XML or JSON depending on request  
function convertArrayToFormatForOutput($outputNode) {
    //Default to XML if json isn't specified  
    if (@$_REQUEST["format"] === "json")
    {
        $outputJSON = json_encode($outputNode);
        displayJSON($outputJSON);
    }
    else
    { 
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
    if ($message == null)
    {
        $message = searchForError($errorCode);
    }
        
    $dataArray = array("code"=>$errorCode, "msg"=>(string) $message);

    $outputNode = constructOutputArray($dataArray, "error");

    convertArrayToFormatForOutput($outputNode);
}
//Get list of the rates.
function getRates(){
    //Getting rates information from the file stored locally. @ is suppress wearning so we can handle myself.
    $xml = @simplexml_load_file('./data/rates.xml');
    //Check if we don't have valid XML file
    if ($xml === false)
    {
        //Send error message to user and then kill the service
        conductErrorMessage(1500, "Error in service");
        exit();
    }
    return $xml;
}
//Get list of the errors.
function getErrors(){
    //Getting errors information from the file stored locally. @ is suppress wearning so we can handle myself.
    $xml = @simplexml_load_file('./data/errors.xml');
    //Check if we don't have valid XML file
    if ($xml === false)
    {
        //Send error message to user and then kill the service
        conductErrorMessage(1500, "Error in service");
        exit();
    }
    return $xml;
}
//Get list of the contries that support all of the currencies. Also declaring the return to SimpleXMLElement in this function.
function getCountries(){
    //Getting contries information from the file stored locally. @ is suppress wearning so we can handle myself.
    $xml = @simplexml_load_file('./data/countries.xml');
    //Check if we don't have valid XML file
    if ($xml === false)
    {
        //Send error message to user and then kill the service
        conductErrorMessage(1500, "Error in service");
        exit();
    }
    return $xml;
}
//Get list of the currencies and their rates at current time.
function getCurrencies($currenciesAPIKey) {
    //Getting current currencies information from the API. @ is suppress wearning so we can handle myself.
    $json = @file_get_contents('http://data.fixer.io/api/latest?access_key='. $currenciesAPIKey);
    $jsonFormatted = json_decode($json, true);
    //Check if we don't have valid XML file
    if ($json === false || $jsonFormatted["success"] === false)
    {
        //Send error message to user and then kill the service
        conductErrorMessage(1500, "Error in service");
        exit();
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
    for ($i = 0; $i < sizeof($currenciesISOCodes); $i++)
    {
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
function requestDataFromAPI($currenciesISOCodes, $baseCurrency, $xmlFileName, $currenciesAPIKey) {
    //Call function to get current currencies information from the API
    $currentCurrencies = getCurrencies($currenciesAPIKey);
    //Setting the rates of all currency rates to the a varible.
    $currencies = $currentCurrencies["rates"];
    //Call function to create new rates file
    initializeRatesXML($currenciesISOCodes, $baseCurrency, $xmlFileName, $currencies);
}
//Check if the parameters in the GET method are correct to the ones pre defined
function checkRequestKeys($amountOfGetKeys, $amountOfGetParameters, $getPreDefinedParameters) {
    //Check if there is the wrong amount of parameters
    if ($amountOfGetKeys == $amountOfGetParameters)
    {
        //Cycle through all of the HTTP GET Request keys
        for($i = 0; $i < $amountOfGetKeys; $i++) {
            //Cycle through all of the pre-defined expected parameters 
            for($j = 0; $j < $amountOfGetParameters; $j++) {
                //Check if the HTTP GET Request key equals to the expected parameter
                if (array_keys($_REQUEST)[$i] == $getPreDefinedParameters[$j])
                {
                    //If it does then break out of the first for loop to move on to the next HTTP GET Request key
                    break;
                }
                else
                {
                    //If not then check if it has cycled through all of the pre-definedexpected parameters 
                    if ($j >= $amountOfGetParameters - 1)
                    {
                        //Output error 1100 - Parameter not recognized
                        conductErrorMessage(1100); 
                        //Terminate the current script 
                        exit();
                    }
                }
            }
        }
    }
    else if ($amountOfGetKeys > $amountOfGetParameters)
    {
        //Output error 1100 - Parameter not recognized
        conductErrorMessage(1100); 
        //Terminate the current script 
        exit();
    }
    else if ($amountOfGetKeys < $amountOfGetParameters)
    {
        //Output error 1000 - Required parameter is missing
        conductErrorMessage(1000); 
        //Terminate the current script 
        exit();
    }
}
//Checking if they have given me a format that is allowed.
function checkFormatGetValue() {
    if (!(@$_REQUEST["format"] == "xml" || @$_REQUEST["format"] == "json"))
    {
        //Output error 1400 - Format must be xml or json 
        conductErrorMessage(1400);
        //Terminate the current script 
        exit();
    }
}
//Checking if the amount given is a float. 
function checkAmountIsFloat() {
    if (!(is_numeric(@$_REQUEST["amnt"] ) || floor(@$_REQUEST["amnt"] ) != $_REQUEST["amnt"]))
    {
        //Output error 1300 - Currency amount must be a decimal number 
        conductErrorMessage(1300); 
        //Terminate the current script 
        exit();
    }
}
//check if the currency code is valid to rates.
function checkCurrencyCode($currencyCode) {
    //Getting contries information from the file stored locally.
    $rates = getRates();
    //Getting the currency code from the XML data file
    $ratesCode = $rates->xpath("/currencies/currency[@code='". $currencyCode ."']/@code");
    //$currencyLocations = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='". $currencyCode ."']");
    //If the Xpath returned false then show error
    if (!$ratesCode[0] === $currencyCode)
    {   
        //Output error 1200 - Currency type not recognized 
        conductErrorMessage(1200);  
        //Terminate the current script 
        exit();
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
//check if the currency code is valid to rates.
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

    //var_dump($outputArray);

    $outputNode = constructOutputArray($dataArray, "conv");

    convertArrayToFormatForOutput($outputNode);
}
?>