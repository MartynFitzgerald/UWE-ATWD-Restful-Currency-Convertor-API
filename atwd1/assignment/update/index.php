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
|  Description:  This file gets the sets all the predfined arrays and creates
|                the rates file if none exist and gets the infromation to
|                display for the conversion.
|
*===========================================================================*/
//Config used to hold predefined values
require_once('../config.php');
//Functions used throught this service is located.
require_once('../functions.php');
//Checking to see if the get methods are set
$cur = isset($_REQUEST["cur"]) ? strtoupper($_REQUEST["cur"]) : null;
$action = isset($_REQUEST["action"]) ? strtolower($_REQUEST["action"]) : null;
//Check format and if its missing then produce a error
if (!is_string($cur) || $cur == null){
    //Output error 2100 - Currency code in wrong format or is missing
    outputErrorMessageResponse(2100); 
}
//Checking if the to and from values are a currency type recognized
checkCurrencyCodeToXML($cur);
//Check the parameters are the ones that are expected
checkParametersAreRecognized(PRE_DEFINED_GET_PARAMETERS_UPDATE);
if ($cur == BASE) {
    //Output error 2400 - Cannot update base currency
    outputErrorMessageResponse(2400); 
}
//Setting value outside the ifstatement to allow us to access rates below the if statement.
$rates = null;
$oldRate = null;
//Check if file doesn't exists and then create file or read that file.
if (!file_exists(RATES_PATH_DIRECTORY)) {
    //Create rates file
    initializeDataFromAPI();
    //Request the rates information form file
    $rates = getRatesFromDataFile();
} else {
    //Request the rates information form file
    $rates = getRatesFromDataFile();
    //Getting the currency name from the XML data file
    $timeStamp = $rates->xpath("//@ts")[0];
    //Getting the currency rate from the XML data file
    if ($action === "post") {
        //Check if the currency is within rates.xml
        checkCurrencyCode($rates, $cur, 2200);
        $oldRate = (float) $rates->xpath("/currencies/currency[@code='". $cur ."']/@rate")[0]->rate;
    }
    //Formatting the timestamp to the date format
    $formatTimeStamp = date('d F Y H:i',  substr($timeStamp, 0, 10));
    //Checking to if the current time is above 2 hours from the time stamp stored.
    if ($timeStamp <= strtotime("-2 hours")) {
        //Request data from APIs and create rates.xml file.
        updateDataFromAPI($rates, $timeStamp);
    }
}
//Check what actions is given
if ($action === "post") {
    if ($oldRate == null) {
        //Check if the currency is within rates.xml
        checkCurrencyCode($rates, $cur, 2200);
        conductPostMessage($action, $cur, $rates);
    } else {
        conductPostMessage($action, $cur, $rates, $oldRate);
    }
} else if ($action === "put") {
    conductPutMessage($action, $cur, $rates);
} else if ($action === "del") {
    conductDeleteCurrency($action, $cur, $rates);
} else {
    //Output error 2000 - Action not recognized or is missing
    outputErrorMessageResponse(2000); 
}
//Display the new currency rate to the user and do the calulation to get value.
function conductPostMessage($action, $cur, $rates, $oldRate = null) {
    //Getting contries information from the file stored locally.
    $countries = getCountriesFromDataFile();
    //Get timestamp from rates file.
    $oldTmeStamp = (int) $rates->xpath("/currencies/@ts")[0];
    //if we haven't set old rate
    if ($oldRate == null) {
        //Getting the currency rate from the XML data file
        $oldRate = (float) $rates->xpath("/currencies/currency[@code='". $cur ."']/@rate")[0]->rate;
    } 
    //Update currency rates in the rates.xml file.
    updateDataFromAPI($rates, $oldTmeStamp);
    //Request the new rates information form file
    $rates = getRatesFromDataFile();
    //Get timestamp from rates file.
    $timeStamp = (int) $rates->xpath("/currencies/@ts")[0];
    //Getting the currency rate from the XML data file
    $rate = (float) $rates->xpath("/currencies/currency[@code='". $cur ."']/@rate")[0]->rate;
    //Contruct arrays with the data above
    $curArray = array("code"=> $cur, "name"=> (string) getCountryNameForCurrencyCode($countries, $cur), "loc"=> getCountryLocationForCurrencyCode($countries, $cur));
    $dataArray = array("at"=> date('d M Y H:i', $timeStamp), "rate"=> $rate, "old_rate"=> $oldRate, "curr"=> $curArray);
    $outputNode = array("action"=>$dataArray);
    //Convert array to the formatted out put, default xml.
    convertArrayToFormatForOutput($outputNode, "xml", $action);
}
//Display the new currency rate to the user and do the calulation to get value.
function conductPutMessage($action, $cur, $rates){
    //Getting contries information from the file stored locally.
    $countries = getCountriesFromDataFile();
    //Get timestamp from rates file.
    $oldTmeStamp = (int) $rates->xpath("/currencies/@ts")[0];
    //Check currency's code is within rates file.
    $currency = $rates->xpath("/currencies/currency[@code='". $cur ."']/@isAvailable");
    //If currency isnt within the file then add it otherwise change isAvailable number
    if ($currency == false) {
        //Update currency rates in the rates.xml file.
        updateDataFromAPI($rates, $oldTmeStamp, $cur);
    } else {
        //Set currency specified from the xml
        $currency[0]->isAvailable = "1";
        //Update currency rates in the rates.xml file.
        updateDataFromAPI($rates, $oldTmeStamp);
    }
    //Request the new rates information form file
    $rates = getRatesFromDataFile();
    //Get timestamp from rates file.
    $timeStamp = (int) $rates->xpath("/currencies/@ts")[0];
    //Getting the currency rate from the XML data file
    $rate = (float) $rates->xpath("/currencies/currency[@code='". $cur ."']/@rate")[0]->rate;
    //Contruct arrays with the data above
    $curArray = array("code"=> $cur, "name"=> (string) getCountryNameForCurrencyCode($countries, $cur), "loc"=> getCountryLocationForCurrencyCode($countries, $cur));
    $dataArray = array("at"=> date('d M Y H:i', $timeStamp), "rate"=> $rate, "curr"=> $curArray);
    $outputNode = array("action"=>$dataArray);
    //Convert array to the formatted out put, default xml.
    convertArrayToFormatForOutput($outputNode, "xml", $action);
}
//Display the deleted currency to the user.
function conductDeleteCurrency($action, $cur, $rates){
    //Get timestamp from rates file.
    $oldTmeStamp = (int) $rates->xpath("/currencies/@ts")[0];
    //Getting the currency from the XML data file
    $currency = $rates->xpath("/currencies/currency[@code='". $cur ."']/@isAvailable");
    if($currency == true) {
        //Unset currency specified from the xml
        $currency[0]->isAvailable = "0";
        //Save the values in the rates.xml
        file_put_contents(RATES_PATH_DIRECTORY, $rates->saveXML());
        
        //Update currency rates in the rates.xml file.
        updateDataFromAPI($rates, $oldTmeStamp);
        //Request the new rates information form file
        $rates = getRatesFromDataFile();
        //Get timestamp from rates file.
        $timeStamp = (int) $rates->xpath("/currencies/@ts")[0];
        //Contruct arrays with the data above
        $dataArray = array("at"=> date('d M Y H:i', $timeStamp), "code"=> $cur);
        $outputNode = array("action"=>$dataArray);
        //Convert array to the formatted out put, default xml.
        convertArrayToFormatForOutput($outputNode, "xml", $action);
    } else {
        //Output error 2300 - No rate listed for this currency
        outputErrorMessageResponse(2300); 
    }
}
?>