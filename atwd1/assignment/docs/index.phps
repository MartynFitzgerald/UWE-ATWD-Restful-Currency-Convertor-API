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
require_once('config.php');
//Functions used throught this service is located.
require_once('functions.php');
//Checking to see if the get methods are set and define global values
define('FROM', isset($_REQUEST["from"]) ? strtoupper($_REQUEST["from"]) : null);
define('TO', isset($_REQUEST["to"]) ? strtoupper($_REQUEST["to"]) : null);
define('AMOUNT', isset($_REQUEST["amnt"]) ? $_REQUEST["amnt"] : null);
define('FORMAT', isset($_REQUEST["format"]) ? strtolower($_REQUEST["format"]) : null);
//If the values are null then output error message.
if (!FROM || !TO || !AMOUNT) {
    // maybe make a function "productParametersMissingError"
    outputErrorMessageResponse(1000);
}
//Check format is XML, JSON or null
if (!in_array(FORMAT, FORMATS)) {
    //Output error 1400	- Format must be xml or json
    outputErrorMessageResponse(1400);  
} 
//This should check to see if the value is a decimal and not a float
checkAmountIsFloat(AMOUNT);
//Check the parameters are the ones that are expected, error 1100 - Parameter not recognized
checkParametersAreRecognized(PRE_DEFINED_GET_PARAMETERS_CONVERSION, 1100);
//Setting value outside the ifstatement to allow us to access rates below the if statement.
$rates = null;
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
    $timeStamp = $rates->xpath("/currencies/@ts")[0];
    //Formatting the timestamp to the date format
    $formatTimeStamp = date('d F Y H:i',  substr($timeStamp, 0, 10));
    //Checking to if the current time is above 2 hours from the time stamp stored.
    if ($timeStamp <= strtotime("-2 hours")) {
        //Request data from APIs and create rates.xml file.
        updateDataFromAPI($rates, $timeStamp);
    }
}
//Checking currency code to the rates.xml file.
checkCurrencyCode($rates, FROM, 1200);
checkCurrencyCode($rates, TO, 1200);
//Request the countries information form file.
$countries = getCountriesFromDataFile();
//Produce conversion message to user.
conductConvMessage($countries, $rates);
//Display the currency conversion to the user and do the calulation to get value.
function conductConvMessage($countries, $rates){
    //Getting the currency rate from the XML data file
    $currencyTo = $rates->xpath("/currencies/currency[@code='". TO ."']")[0];
    $currencyFrom = $rates->xpath("/currencies/currency[@code='". FROM ."']")[0];
    if ($currencyTo["isAvailable"] == 1 && $currencyFrom["isAvailable"] == 1) {
        //Set rate value
        $rate = round(((float) $currencyTo["rate"] / (float) $currencyFrom["rate"]), 2);
        $amountFormatted = round((float) AMOUNT, 2);
        //Calculating the conversion.
        $amountCalculation = round($rate * (float) AMOUNT, 2);
        //Getting timestamp from document
        $ts = $rates->xpath("/currencies/@ts");
        //Build the PHP array so we can convert it too xml or json.
        $fromArray = array("code"=> FROM, "curr"=> (string) getCountryNameForCurrencyCode($countries, FROM), "loc"=> getCountryLocationForCurrencyCode($countries, FROM), "amnt"=> $amountFormatted);
        $toArray = array("code"=> TO, "curr"=> (string) getCountryNameForCurrencyCode($countries, TO), "loc"=> getCountryLocationForCurrencyCode($countries, TO), "amnt"=> $amountCalculation);
        $dataArray = array("at"=> date('d M Y H:i', (int) $ts[0]), "rate"=> $rate, "from"=> $fromArray, "to"=> $toArray);
        $outputNode = array("conv"=>$dataArray);
        //Convert array to format and output
        convertArrayToFormatForOutput($outputNode);
    }
    else
    {
        //Output error 1200	- Currency type not recognised
        outputErrorMessageResponse(1200);  
    }
}
?>