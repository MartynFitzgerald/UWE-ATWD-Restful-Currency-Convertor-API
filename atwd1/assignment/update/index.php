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

//Checking if the to and from values are a currency type recognized
checkCurrencyCodeToXML($cur);
//Check the parameters are the ones that are expected
checkParametersAreRecognized(PRE_DEFINED_GET_PARAMETERS_UPDATE);
if ($cur == BASE)
{
    //Output error 2400 - Cannot update base currency
    outputErrorMessageResponse(2400); 
}

//Setting value outside the ifstatement to allow us to access rates below the if statement.
$rates = null;
//Check if file doesn't exists and then create file or read that file.
if (!file_exists(RATES_PATH_DIRECTORY))
{
    //Create rates file
    initializeDataFromAPI();
    //Request the rates information form file
    $rates = getRatesFromDataFile();
} else {
    //Request the rates information form file
    $rates = getRatesFromDataFile();
    //Getting the currency name from the XML data file
    $timeStamp = $rates->xpath("/currencies/@ts");
    //Formatting the timestamp to the date format
    $formatTimeStamp = date('d F Y H:i',  substr($timeStamp[0], 0, 10));
    //Checking to if the current time is above 2 hours from the time stamp stored.
    if ($timeStamp[0] <= strtotime("-2 hours")) {
        //Rename XML file to inlcude date.
        archiveRatesFile($timeStamp[0]);
        //Request data from APIs and create rates.xml file.
        updateDataFromAPI($rates);
    }
}
//Check what actions is given
if ($action === "post") {
    conductPostMessage($action, $cur, $rates);
}
else if ($action === "put") {
    conductPutMessage($action, $cur, $rates);
}
else if ($action === "del") {
    conductDeleteCurrency($action, $cur, $rates);
}
else {
    //Output error 2000 - Action not recognized or is missing
    outputErrorMessageResponse(2000); 
}
//Display the new currency rate to the user and do the calulation to get value.
function conductPostMessage($action, $cur, $rates){
    //Getting contries information from the file stored locally.
    $countries = getCountriesFromDataFile();
    //Update currency rate in the rates file.
    $currencyRate = getNewCurrencyRate($cur);
    //Get timestamp from rates file.
    $ts = $rates->xpath("/currencies/@ts");
    //Getting the currency rate from the XML data file
    $rate = $rates->xpath("/currencies/currency[@code='". $cur ."']/@rate");
    //Set old rate to varible to store.
    $oldRate = (float) $rate[0]->rate;
    //Make node attrube @rate equal to the new rate for that currency
    $rate[0]->rate = $currencyRate; 
    //Save the values in the rates.xml
    file_put_contents(RATES_PATH_DIRECTORY, $rates->saveXML());
    //Contruct arrays with the data above
    $curArray = array("code"=> $cur, "name"=> (string) getCountryNameForCurrencyCode($countries, $cur), "loc"=> getCountryLocationForCurrencyCode($countries, $cur));
    $dataArray = array("at"=> date('d M Y H:i', (int) $ts[0]), "rate"=> (float) $currencyRate, "old_rate"=> $oldRate, "curr"=> $curArray);
    $outputNode = array("action"=>$dataArray);
    //Convert array to the formatted out put, default xml.
    convertArrayToFormatForOutput($outputNode, "xml", $action);
}
//Display the new currency rate to the user and do the calulation to get value.
function conductPutMessage($action, $cur, $rates){
    //Getting contries information from the file stored locally.
    $countries = getCountriesFromDataFile();
    //Update currency rate in the rates file.
    $currencyRate = getNewCurrencyRate($cur);
    //Get timestamp from rates file.
    $ts = $rates->xpath("/currencies/@ts");

    $currency = $rates->xpath("/currencies/currency[@code='". $cur ."']");
    if ($currency == false)
    {
        //Getting the currency rate from the XML data file
        $currencies = $rates->xpath("/currencies/currency");

        $artributeArray = array("code"=> $cur, "rate"=> $currencyRate, "isAvailable"=> "1");
        array_push($currencies, $artributeArray);
        
        $array = new SimpleXMLElement($currencies[0]);


        var_dump($array);
        var_dump($currencies);
        
        //$currencies->addChild('title', 'PHP2: More Parser Stories');

        //$currency[0]"currency"]["code"] = $cur;
        //$currency[0]"currency"]["rate"] = $currencyRate;
        //$currency[0]"currency"]["isAvailable"] = "test";
        //$currencies[0] => $artributeArray;
    }
    else {
        $currency[0]["isAvailable"] = "1";
    }
    //Save the values in the rates.xml
    file_put_contents(RATES_PATH_DIRECTORY, $rates->saveXML());
    //Contruct arrays with the data above
    $curArray = array("code"=> $cur, "name"=> (string) getCountryNameForCurrencyCode($countries, $cur), "loc"=> getCountryLocationForCurrencyCode($countries, $cur));
    $dataArray = array("at"=> date('d M Y H:i', (int) $ts[0]), "rate"=> (float) $currencyRate, "curr"=> $curArray);
    $outputNode = array("action"=>$dataArray);
    //Convert array to the formatted out put, default xml.
    convertArrayToFormatForOutput($outputNode, "xml", $action);
}
//Display the deleted currency to the user.
function conductDeleteCurrency($action, $cur, $rates){
    //Get timestamp from rates file.
    $ts = $rates->xpath("/currencies/@ts");
    //Getting the currency from the XML data file
    $currency = $rates->xpath("/currencies/currency[@code='". $cur ."']/@isAvailable");
    if($isAvailable == true) {
        //Unset curreny specified from the xml
        $currency[0]->isAvailable = "0";
        //Save the values in the rates.xml
        file_put_contents(RATES_PATH_DIRECTORY, $rates->saveXML());
        //Contruct arrays with the data above
        $dataArray = array("at"=> date('d M Y H:i', (int) $ts[0]), "code"=> $cur);
        $outputNode = array("action"=>$dataArray);
        //Convert array to the formatted out put, default xml.
        convertArrayToFormatForOutput($outputNode, "xml", $action);
    }
    else {
        //Output error 2300 - No rate listed for this currency
        outputErrorMessageResponse(2300); 
    }
}
?>