<?php
/*=============================================================================
|      Editors:  Martyn Fitzgerald - 16025948
|
|  Module Code:  UFCFV4-30-2
| Module Title:  Data, Schemas & Applications
|                
|   Instructor:  Prakash Chatterjee / Glyn Watkins
|     Due Date:  14/03/2019
|
|  Description:  Creating a functions used in the index.php file
|
*===========================================================================*/

function displayJSON($json) {
    header('Content-Type: application/json');
    echo $json;
}

function displayXML($dom) {
    header('Content-Type: text/xml');
    echo $dom->asXML();
}

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

//function defination to convert array to xml
//source: https://www.codexworld.com/convert-array-to-xml-in-php/
function array_to_xml($array, &$xml_user_info) {
    foreach($array as $key => $value) {
        if(is_array($value)) {
            if(!is_numeric($key)){
                $subnode = $xml_user_info->addChild("$key");
                array_to_xml($value, $subnode);
            }else{
                $subnode = $xml_user_info->addChild("item$key");
                array_to_xml($value, $subnode);
            }
        }else {
            $xml_user_info->addChild("$key",htmlspecialchars("$value"));
        }
    }
}

function convertArrayToFormatForOutput($outputNode) {

    if ($_REQUEST["format"] == "json")
    {
        $outputJSON = json_encode($outputNode);
        displayJSON($outputJSON);
    }
    else
    {
        //Default to xml if nothing is specified   
        $firstNodeKey = array_keys($outputNode)[0];
        $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><" . $firstNodeKey . "></" . $firstNodeKey . ">");
        
        array_to_xml($outputNode[$firstNodeKey],$xml);
        displayXML($xml);
    } 

}

function conductErrorMessage($errorCode){
    $message = searchForError($errorCode);
        
    $dataArray = array("code"=>$errorCode, "msg"=>(string) $message);

    $outputNode = constructOutputArray($dataArray, "error");

    convertArrayToFormatForOutput($outputNode);
}

//Get list of the contries that support all of the currencies. Also declaring the return to SimpleXMLElement in this function.
function getCountries(){
    //Getting contries information from the file stored locally.
    $xml = simplexml_load_file('./data/countries.xml') or die("Error in service");
    return $xml;
}
//Get list of the currencies and their rates at current time.
function getCurrencies($currenciesAPIKey) {
    //Getting current currencies information from the API
    return json_decode(file_get_contents('http://data.fixer.io/api/latest?access_key='. $currenciesAPIKey),true);
}

//Get list of the errors.
function getErrors(){
    //Getting errors information from the file stored locally.
    $xml = simplexml_load_file('./data/errors.xml') or die("Error in service");
    return $xml;
}

//Search list of the errors with the code to get the message.
function searchForError($errorCode){
    //Getting errors information from the file stored locally.
    $errors = getErrors();

    //Getting the error message from the XML data file
    $errorMessage = $errors->xpath("/errors/error[code='" . $errorCode . "']/message");

    return $errorMessage[0];
}

function requestDataFromAPI($currenciesISOCodes, $baseCurrency, $xmlFileName, $currenciesAPIKey) {

    //Call function to get current currencies information from the API
    $currentCurrencies = getCurrencies($currenciesAPIKey);
    
    //Setting the rates of all currency rates to the a varible.
    $currencies = $currentCurrencies["rates"];

    //Getting contries information from the file stored locally.
    $countries = getCountries();

    initializeRatesXML($currenciesISOCodes, $baseCurrency, $xmlFileName, $countries, $currencies);
}

function initializeRatesXML($currenciesISOCodes, $baseCurrency, $xmlFileName, $countries, $currencies) {
    //Create XML Document
    $dom = new DOMDocument("1.0");

    //Creating "currencies" Node
    $root = $dom->createElement("currencies");

    //Adding attribute "base" to "currencies" Node
    $root->setAttributeNode(new DOMAttr("base", $baseCurrency));

    //Setting root to the XML document
    $dom->appendChild($root);

    //This loop cycles through the predefined rates.
    for ($i = 0; $i < sizeof($currenciesISOCodes); $i++)
    {
        //Creating timestamp for when created in XML document
        $timeStamp =  time();
        //Calculating the rate, since the API base currency isnt GBP
        $currencyRate = $currencies[$currenciesISOCodes[$i]] / $currencies[$baseCurrency];
        //Setting the ISO code for this currency
        $currencyCode = $currenciesISOCodes[$i];
        //Getting the currency name from the XML data file
        $currencyName = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='" . $currenciesISOCodes[$i] . "']/CcyNm");
        //Getting the currency locations from the XML data file
        $currencyLocations = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='" . $currenciesISOCodes[$i] . "']/CtryNm");
        //Formatted the locations to put them into a string and also capitalization the first letter within a word
        $currencyLocationsFormatted = ucwords(strtolower(implode(", ",$currencyLocations)));
        
        //Print this out to the screen now
        //echo $timeStamp ." - " . $currencyRate . " - " . $currencyCode . " - " . $currencyName[0] . " - " . $currencyLocationsFormatted . "</br>";  

        //Create main currency node for each currency pre-defined
        $itemNode = $dom->createElement("currency");
        //Create few child nodes for to add the data to the currency node above
        $itemNode->appendChild($dom->createElement("at", $timeStamp));
        $itemNode->appendChild($dom->createElement("rate", $currencyRate));
        $currNode = $itemNode->appendChild($dom->createElement("curr"));
            $currNode->appendChild($dom->createElement("code", $currencyCode));
            $currNode->appendChild($dom->createElement("name", $currencyName[0]));
            $currNode->appendChild($dom->createElement("loc", $currencyLocationsFormatted));
        //Attach the new child nodes to the main currency node 
        $itemNode->appendChild($currNode);
        //Attach the main currency node to the main node in the XML document
        $root->appendChild($itemNode);
    }

    //Saving XML document to the filename defined above
    $dom->save('./data/'. $xmlFileName);
}

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

function checkFormatGetValue() {
    if (!($_REQUEST["format"] == "xml" || $_REQUEST["format"] == "json"))
    {
        //Output error 1400 - Format must be xml or json 
        conductErrorMessage(1400);
        //Terminate the current script 
        exit();
    }
}

function checkAmountIsFloat() {
    if (!(is_numeric( $_REQUEST["amnt"] ) || floor( $_REQUEST["amnt"] ) != $_REQUEST["amnt"]))
    {
        //Output error 1300 - Currency amount must be a decimal number 
        conductErrorMessage(1300); 
        //Terminate the current script 
        exit();
    }
}

function checkCurrencyCode($currencyCode) {
    //Getting contries information from the file stored locally.
    $countries = getCountries();
    //Getting the currency code from the XML data file
    $currencyLocations = $countries->xpath("/ISO_4217/CcyTbl/CcyNtry[Ccy='". $currencyCode ."']");
    //If the Xpath returned false then show error
    if (!$currencyLocations)
    {
        
        //Output error 1200 - Currency type not recognized 
        conductErrorMessage(1200);  
        //Terminate the current script 
        exit();
    }
}
?>